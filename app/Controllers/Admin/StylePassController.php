<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AI\EditorialAssistService;
use App\Models\AiGenerationJobModel;
use App\Models\ArticleModel;
use App\Models\AuditLogModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;

/**
 * The style-pass ("readability pass") review screen. This never rewrites
 * an article on its own — run() only queues an AI suggestion job
 * (processed asynchronously by the ai:process-queue cron worker); an
 * editor must explicitly accept() or reject() the suggestion afterwards.
 */
class StylePassController extends BaseController
{
    private const DEFAULT_HOUSE_STYLE_NOTES =
        'Clear, concise AP-style prose. Avoid hype/clickbait phrasing. Sentences under ~25 words where natural.';

    public function show(int $articleId)
    {
        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $job = model(AiGenerationJobModel::class)
            ->where('article_id', $articleId)
            ->where('job_type', 'style_pass')
            ->orderBy('id', 'DESC')
            ->first();

        return view('admin/generation/style_pass', [
            'article'                => $article,
            'job'                    => $job,
            'completedJob'           => ($job !== null && $job->status === 'completed') ? $job : null,
            'defaultHouseStyleNotes' => self::DEFAULT_HOUSE_STYLE_NOTES,
        ]);
    }

    public function run(int $articleId)
    {
        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $notes = trim((string) $this->request->getPost('house_style_notes'));
        if ($notes === '') {
            $notes = self::DEFAULT_HOUSE_STYLE_NOTES;
        }

        $job = (new EditorialAssistService())->suggestRevision(
            $article,
            $this->currentUser(),
            $notes,
            new DateTimeImmutable()
        );

        $this->audit('style_pass.requested', $job->id, null, ['article_id' => $articleId]);

        return redirect()->to(site_url('admin/articles/' . $articleId . '/style-pass'))
            ->with('success', 'Style pass queued — refresh in a moment.');
    }

    public function accept(int $articleId)
    {
        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $job = $this->latestCompletedJob($articleId);

        if ($job === null) {
            return redirect()->to(site_url('admin/articles/' . $articleId . '/style-pass'))
                ->with('error', 'No completed style pass suggestion is available to accept.');
        }

        (new EditorialAssistService())->acceptSuggestion($job, $article, $this->currentUser(), new DateTimeImmutable());

        $this->audit('style_pass.accepted', $job->id, null, ['article_id' => $articleId]);

        return redirect()->to(site_url('admin/articles/' . $articleId . '/edit'))
            ->with('success', 'Style pass suggestion applied to the article.');
    }

    public function reject(int $articleId)
    {
        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $job = $this->latestCompletedJob($articleId);

        if ($job === null) {
            return redirect()->to(site_url('admin/articles/' . $articleId . '/style-pass'))
                ->with('error', 'No completed style pass suggestion is available to reject.');
        }

        (new EditorialAssistService())->rejectSuggestion($job, $article, $this->currentUser(), new DateTimeImmutable());

        $this->audit('style_pass.rejected', $job->id, null, ['article_id' => $articleId]);

        return redirect()->to(site_url('admin/articles/' . $articleId . '/style-pass'))
            ->with('success', 'Style pass suggestion rejected. Article left untouched.');
    }

    private function latestCompletedJob(int $articleId): ?\App\Entities\AiGenerationJob
    {
        return model(AiGenerationJobModel::class)
            ->where('article_id', $articleId)
            ->where('job_type', 'style_pass')
            ->where('status', 'completed')
            ->orderBy('id', 'DESC')
            ->first();
    }

    private function audit(string $action, int $subjectId, ?array $before, ?array $after): void
    {
        $user = $this->currentUser();

        if ($user === null) {
            return;
        }

        model(AuditLogModel::class)->record(
            $user->id,
            $action,
            'ai_generation_job',
            $subjectId,
            $before,
            $after,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );
    }
}
