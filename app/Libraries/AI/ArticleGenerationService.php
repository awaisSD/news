<?php

namespace App\Libraries\AI;

use App\Entities\AiGenerationJob;
use App\Entities\Article;
use App\Entities\Topic;
use App\Entities\User;
use App\Libraries\Publishing\SlugGenerator;
use App\Models\AiGenerationJobModel;
use App\Models\AiSettingModel;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\TopicModel;
use Config\AIPipeline;
use Config\Services;
use DateTimeImmutable;
use LogicException;

/**
 * Turns an assigned Topic into a queued AI generation job, and turns a
 * completed job's provider output into a brand-new, NOT-YET-PUBLISHED
 * `articles` row (`status = 'in_review'`). This class never sets
 * `articles.status = 'published'` and never bypasses editorial review —
 * every AI-drafted article still has to go through the normal
 * EditorialReviewService approve/publish flow like any human-written draft.
 */
class ArticleGenerationService
{
    /**
     * Fallback primary_category_id used only when a caller does not supply
     * one to process(). Assumes a "General/News" category exists at id 1 —
     * callers should generally always pass an explicit category.
     */
    private const FALLBACK_CATEGORY_ID = 1;

    private SlugGenerator $slugGenerator;

    public function __construct(
        private ?AiGenerationJobModel $generationJobs = null,
        private ?AiSettingModel $aiSettings = null,
        private ?TopicModel $topics = null,
        private ?ArticleModel $articles = null,
        private ?CategoryModel $categories = null,
    ) {
        $this->generationJobs ??= model(AiGenerationJobModel::class);
        $this->aiSettings ??= model(AiSettingModel::class);
        $this->topics ??= model(TopicModel::class);
        $this->articles ??= model(ArticleModel::class);
        $this->categories ??= model(CategoryModel::class);
        $this->slugGenerator = new SlugGenerator($this->articles);
    }

    /**
     * Whether today's job count (across all job types) has already reached
     * the configured daily cap. The ai_settings-table override (admin-
     * editable at runtime) takes precedence over the deploy-time
     * Config\AIPipeline default.
     */
    public function dailyCapReached(DateTimeImmutable $now): bool
    {
        /** @var AIPipeline $config */
        $config = config(AIPipeline::class);

        $cap = (int) $this->aiSettings->getValue('daily_generation_cap') ?: $config->dailyGenerationCap;

        $count = $this->generationJobs
            ->where('created_at >=', $now->format('Y-m-d') . ' 00:00:00')
            ->countAllResults();

        return $count >= $cap;
    }

    /**
     * Queues a new article-generation job for an assigned topic. If the
     * daily cap has already been reached, the job is still recorded (with
     * status `blocked_by_cap`) so it shows up on an admin dashboard, but no
     * provider is ever called for it and the topic's own status is left
     * untouched.
     */
    public function createJob(Topic $topic, User $requestedBy, DateTimeImmutable $now): AiGenerationJob
    {
        /** @var AIPipeline $config */
        $config = config(AIPipeline::class);

        $model = match ($config->textProvider) {
            'anthropic' => $config->anthropicModel,
            'openai'    => $config->openAiModel,
            default     => $config->anthropicModel,
        };

        if ($this->dailyCapReached($now)) {
            log_message(
                'warning',
                'ArticleGenerationService: daily generation cap reached; blocking new job for topic #{topicId}.',
                ['topicId' => $topic->id]
            );

            $id = $this->generationJobs->insert([
                'topic_id'     => $topic->id,
                'job_type'     => 'article',
                'provider'     => $config->textProvider,
                'model'        => $model,
                'status'       => 'blocked_by_cap',
                'requested_by' => $requestedBy->id,
                'created_at'   => $now->format('Y-m-d H:i:s'),
            ]);

            return $this->generationJobs->find($id);
        }

        $id = $this->generationJobs->insert([
            'topic_id'     => $topic->id,
            'job_type'     => 'article',
            'provider'     => $config->textProvider,
            'model'        => $model,
            'status'       => 'pending',
            'requested_by' => $requestedBy->id,
            'created_at'   => $now->format('Y-m-d H:i:s'),
        ]);

        $this->topics->update($topic->id, ['status' => 'in_generation']);

        return $this->generationJobs->find($id);
    }

    /**
     * Calls the configured AI text provider for a `processing` job and, on
     * success, creates a brand-new `articles` row with `status = 'in_review'`
     * — never `published`, and never overwriting an existing article. A
     * human editor still has to review/approve/publish it like any other
     * draft.
     *
     * @param int|null $categoryId primary_category_id for the new article.
     *                             Callers should generally always pass this
     *                             explicitly (it should usually come from
     *                             whatever category the topic/editor
     *                             intended); omitting it falls back to a
     *                             "General/News" category (id 1).
     *
     * @throws LogicException if the job is not currently `processing`.
     * @throws AiProviderException if the provider call fails (the job is
     *                              marked `failed` first, then rethrown).
     */
    public function process(AiGenerationJob $job, DateTimeImmutable $now, ?int $categoryId = null): Article
    {
        if ($job->status !== 'processing') {
            throw new LogicException(
                "AiGenerationJob #{$job->id} must be claimed (status=processing) before processing; got status={$job->status}."
            );
        }

        $topic = $this->topics->find($job->topic_id);

        if ($topic === null) {
            throw new LogicException("AiGenerationJob #{$job->id} references a missing topic #{$job->topic_id}.");
        }

        $effectiveCategoryId = $categoryId ?? self::FALLBACK_CATEGORY_ID;
        $categorySlug        = $this->resolveCategorySlug($effectiveCategoryId);

        $request = new GenerationRequest(
            topicTitle: $topic->title,
            brief: $topic->brief,
            angleNotes: $topic->angle_notes,
            targetCategorySlug: $categorySlug,
            mode: 'article',
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

        $requestedById = $job->requested_by;

        if ($requestedById === null) {
            throw new LogicException("AiGenerationJob #{$job->id} has no requested_by; cannot attribute authorship.");
        }

        $slug = $this->slugGenerator->generate($result->headline, $effectiveCategoryId);

        $articleId = $this->articles->insert([
            'uuid'                 => generate_uuid4(),
            'headline'             => $result->headline,
            'slug'                 => $slug,
            'excerpt'              => $result->excerpt,
            'body_html'            => $result->bodyHtml,
            'body_format'          => 'html',
            'primary_category_id' => $effectiveCategoryId,
            'author_id'            => $requestedById,
            'status'               => 'in_review',
            'ai_assisted'          => true,
            'ai_generation_job_id' => $job->id,
            'word_count'           => $result->wordCount,
            'reading_time_minutes' => (int) ceil($result->wordCount / 200),
        ]);

        $this->generationJobs->update($job->id, [
            'status'             => 'completed',
            'article_id'         => $articleId,
            'response_metadata'  => json_encode($result->rawResponseMetadata),
            'cost_usd'           => $result->costUsd,
            'completed_at'       => $now->format('Y-m-d H:i:s'),
        ]);

        $this->topics->update($topic->id, ['status' => 'used']);

        return $this->articles->find($articleId);
    }

    private function resolveCategorySlug(int $categoryId): string
    {
        $category = $this->categories->find($categoryId);

        return $category === null ? 'general' : (string) $category->slug;
    }

}
