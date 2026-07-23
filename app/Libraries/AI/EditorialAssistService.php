<?php

namespace App\Libraries\AI;

use App\Entities\AiGenerationJob;
use App\Entities\Article;
use App\Entities\User;
use App\Libraries\CorrectionsService;
use App\Models\AiGenerationJobModel;
use App\Models\ArticleModel;
use App\Models\EditorialReviewLogModel;
use Config\AIPipeline;
use Config\Services;
use DateTimeImmutable;
use LogicException;

/**
 * House-style / readability "style pass" editorial assist.
 *
 * IMPORTANT FRAMING: this is an editorial house-style assist tool, NOT an
 * AI-detector-evasion / "humanize this text" tool. It never disguises AI
 * origin — it exists to help a draft read more like the publication's own
 * house style before a human editor reviews it. Every suggestion this class
 * produces is stored on the job row only; nothing here ever writes to the
 * live `articles` row until a human editor explicitly calls
 * acceptSuggestion() (which itself still goes through the normal
 * CorrectionsService edit-recording path, not a status change).
 */
class EditorialAssistService
{
    public function __construct(
        private ?AiGenerationJobModel $generationJobs = null,
        private ?ArticleModel $articles = null,
        private ?EditorialReviewLogModel $reviewLog = null,
    ) {
        $this->generationJobs ??= model(AiGenerationJobModel::class);
        $this->articles ??= model(ArticleModel::class);
        $this->reviewLog ??= model(EditorialReviewLogModel::class);
    }

    /**
     * Queues a style-pass job for an existing article. Nothing is sent to
     * the provider yet — that happens when a worker (ProcessAiQueue) claims
     * the job and calls process().
     */
    public function suggestRevision(Article $article, User $requestedBy, string $houseStyleNotes, DateTimeImmutable $now): AiGenerationJob
    {
        /** @var AIPipeline $config */
        $config = config(AIPipeline::class);

        $model = match ($config->textProvider) {
            'anthropic' => $config->anthropicModel,
            'openai'    => $config->openAiModel,
            default     => $config->anthropicModel,
        };

        $id = $this->generationJobs->insert([
            'article_id'   => $article->id,
            'job_type'     => 'style_pass',
            'provider'     => $config->textProvider,
            'model'        => $model,
            'status'       => 'pending',
            'requested_by' => $requestedBy->id,
            'created_at'   => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->generationJobs->find($id);
    }

    /**
     * Calls the configured AI text provider for a `processing` style_pass
     * job and stores the suggestion INSIDE the job's response_metadata.
     * Deliberately does NOT touch the `articles` table — the suggestion is
     * rendered as a diff against the article's current body by the admin
     * StylePassController, and only applied via acceptSuggestion() below.
     *
     * @throws LogicException if the job is not a processing style_pass job.
     */
    public function process(AiGenerationJob $job, Article $article, string $houseStyleNotes, DateTimeImmutable $now): AiGenerationJob
    {
        if ($job->job_type !== 'style_pass' || $job->status !== 'processing') {
            throw new LogicException(
                "AiGenerationJob #{$job->id} must be a claimed (status=processing) style_pass job; got job_type={$job->job_type}, status={$job->status}."
            );
        }

        $request = new GenerationRequest(
            topicTitle: $article->headline,
            brief: $article->body_html,
            angleNotes: null,
            // Unused by provider prompt construction in style_pass mode.
            targetCategorySlug: '',
            houseStyleNotes: $houseStyleNotes,
            mode: 'style_pass',
        );

        try {
            $result = Services::aiProvider()->generateArticle($request);
        } catch (AiProviderException $e) {
            $this->generationJobs->update($job->id, [
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => $now->format('Y-m-d H:i:s'),
            ]);

            throw $e;
        }

        $this->generationJobs->update($job->id, [
            'status'            => 'completed',
            // Keys are deliberately 'headline'/'body_html'/'excerpt' (not
            // prefixed with "suggested_") — the admin StylePassController's
            // view reads $job->response_metadata['headline'] /
            // ['body_html'] directly to render the diff against the
            // article's current content.
            'response_metadata' => json_encode([
                'headline'     => $result->headline,
                'body_html'    => $result->bodyHtml,
                'excerpt'      => $result->excerpt,
                'provider_raw' => $result->rawResponseMetadata,
            ]),
            'cost_usd'     => $result->costUsd,
            'completed_at' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->generationJobs->find($job->id);
    }

    /**
     * Applies a completed style-pass suggestion to the article via
     * CorrectionsService (never a direct write, and never a status change).
     * A style pass is pre-publish editorial polish, not itself a
     * substantive public correction — CorrectionsService's own rules still
     * decide whether a public correction note is warranted when editing an
     * already-published piece (this call always passes isSubstantive=false;
     * if the article is already published, CorrectionsService still bumps
     * updated_at_content per its own rules).
     */
    public function acceptSuggestion(AiGenerationJob $job, Article $article, User $editor, DateTimeImmutable $now): Article
    {
        $metadata = $job->response_metadata ?? [];

        $suggestedHeadline = $metadata['headline'] ?? $article->headline;
        $suggestedBodyHtml = $metadata['body_html'] ?? $article->body_html;

        $updated = (new CorrectionsService())->recordEdit(
            $article,
            $suggestedHeadline,
            $suggestedBodyHtml,
            $editor,
            false,
            null,
            $now
        );

        $this->reviewLog->insert([
            'article_id'  => $article->id,
            'reviewer_id' => $editor->id,
            'action'      => 'style_pass_applied',
            'notes'       => null,
            'created_at'  => $now->format('Y-m-d H:i:s'),
        ]);

        return $updated;
    }

    /**
     * Rejects a style-pass suggestion. The article is left completely
     * untouched — only an audit trail entry is written.
     */
    public function rejectSuggestion(AiGenerationJob $job, Article $article, User $editor, DateTimeImmutable $now): void
    {
        $this->reviewLog->insert([
            'article_id'  => $article->id,
            'reviewer_id' => $editor->id,
            'action'      => 'style_pass_rejected',
            'notes'       => null,
            'created_at'  => $now->format('Y-m-d H:i:s'),
        ]);
    }
}
