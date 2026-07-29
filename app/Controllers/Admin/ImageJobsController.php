<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AI\AltTextService;
use App\Libraries\AI\ImageGenerationService;
use App\Models\AiImageJobModel;
use App\Models\ArticleModel;
use App\Models\AuditLogModel;
use App\Models\MediaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;

/**
 * Editorial gate for AI-generated featured images. requestImage() only
 * ever queues a generation job (processed async by the ai:process-queue
 * cron worker) — approve() is the ONE place in the codebase where a
 * `media` row is created from an AI image job and attached to an
 * article's featured_media_id, and it only runs on a human-clicked
 * approval of an already-completed job.
 */
class ImageJobsController extends BaseController
{
    public function index()
    {
        $jobs = model(AiImageJobModel::class)
            ->where('status', 'completed')
            ->orderBy('completed_at', 'DESC')
            ->findAll();

        if ($jobs === []) {
            return view('admin/images/index', ['jobs' => [], 'articlesById' => []]);
        }

        $jobIds = array_map(static fn ($job) => $job->id, $jobs);

        $attachedJobIds = [];
        foreach (model(MediaModel::class)->whereIn('ai_image_job_id', $jobIds)->findAll() as $media) {
            $attachedJobIds[$media->ai_image_job_id] = true;
        }

        $pending = array_values(array_filter(
            $jobs,
            static fn ($job) => ! isset($attachedJobIds[$job->id])
        ));

        $articleModel = model(ArticleModel::class);
        $articlesById = [];
        foreach ($pending as $job) {
            if ($job->article_id !== null && ! isset($articlesById[$job->article_id])) {
                $articlesById[$job->article_id] = $articleModel->find($job->article_id);
            }
        }

        return view('admin/images/index', [
            'jobs'         => $pending,
            'articlesById' => $articlesById,
        ]);
    }

    public function request(int $articleId)
    {
        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $prompt = trim((string) $this->request->getPost('prompt'));
        if ($prompt === '') {
            $prompt = 'Editorial photo illustrating: ' . $article->headline;
        }

        $returnTo = trim((string) $this->request->getPost('return_to'));

        $job = (new ImageGenerationService())->requestImage(
            $article,
            $prompt,
            $this->currentUser(),
            new DateTimeImmutable()
        );

        $this->audit('image_job.requested', $job->id, null, [
            'article_id' => $articleId,
            'prompt'     => $prompt,
        ]);

        return redirect()->to($returnTo !== '' ? $returnTo : site_url('admin/articles/' . $articleId . '/edit'))
            ->with('success', 'Image generation queued.');
    }

    public function approve(int $jobId)
    {
        $jobModel = model(AiImageJobModel::class);
        $job      = $jobModel->find($jobId);

        if ($job === null) {
            throw PageNotFoundException::forPageNotFound('No such image job.');
        }

        if ($job->status !== 'completed') {
            return redirect()->back()->with('error', 'This image job is not ready to approve yet.');
        }

        $article = model(ArticleModel::class)->find($job->article_id);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $altText = (new AltTextService())->generate($job, $article);

        // media.path is stored relative to Config\Media::$uploadPath (same
        // convention as manual uploads in MediaController::create()) — not
        // the absolute filesystem path in $job->generated_path — since
        // Media::getUrl() builds a URL by appending this to base_url('uploads/').
        $mediaConfig    = config(\Config\Media::class);
        $relativePath   = ltrim(str_replace(rtrim($mediaConfig->uploadPath, '/'), '', $job->generated_path), '/');

        $mediaModel = model(MediaModel::class);
        $mediaId    = $mediaModel->insert([
            'uuid'            => generate_uuid4(),
            'disk'            => 'local',
            'path'            => $relativePath,
            'width'           => $job->width,
            'height'          => $job->height,
            'mime_type'       => 'image/png',
            'alt_text'        => $altText,
            'alt_text_source' => 'ai',
            'source'          => 'ai_generated',
            'generated_by'    => 'ai',
            'ai_image_job_id' => $job->id,
            'uploaded_by'     => $this->currentUser()->id,
            'created_at'      => date('Y-m-d H:i:s'),
        ], true);

        // Attaching the featured image is metadata, not a content/body
        // change, so it's a direct ArticleModel update rather than going
        // through CorrectionsService/EditorialReviewService.
        model(ArticleModel::class)->update($article->id, ['featured_media_id' => $mediaId]);

        $this->audit('image_job.approved', $job->id, null, [
            'article_id' => $article->id,
            'media_id'   => $mediaId,
        ]);

        $returnTo = trim((string) $this->request->getPost('return_to'));

        return redirect()->to($returnTo !== '' ? $returnTo : site_url('admin/articles/' . $article->id . '/edit'))
            ->with('success', 'Image approved and set as the featured image.');
    }

    public function reject(int $jobId)
    {
        $jobModel = model(AiImageJobModel::class);
        $job      = $jobModel->find($jobId);

        if ($job === null) {
            throw PageNotFoundException::forPageNotFound('No such image job.');
        }

        $jobModel->update($job->id, ['status' => 'cancelled']);

        $this->audit('image_job.rejected', $job->id, null, ['article_id' => $job->article_id]);

        $returnTo = trim((string) $this->request->getPost('return_to'));

        return redirect()->to($returnTo !== '' ? $returnTo : site_url('admin/image-jobs'))
            ->with('success', 'Image rejected. No media was created.');
    }

    public function regenerate(int $jobId)
    {
        $jobModel = model(AiImageJobModel::class);
        $job      = $jobModel->find($jobId);

        if ($job === null) {
            throw PageNotFoundException::forPageNotFound('No such image job.');
        }

        $article = model(ArticleModel::class)->find($job->article_id);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $newJob = (new ImageGenerationService())->requestImage(
            $article,
            $job->prompt,
            $this->currentUser(),
            new DateTimeImmutable()
        );

        $this->audit('image_job.regenerated', $newJob->id, ['previous_job_id' => $job->id], [
            'article_id' => $article->id,
        ]);

        $returnTo = trim((string) $this->request->getPost('return_to'));

        return redirect()->to($returnTo !== '' ? $returnTo : site_url('admin/articles/' . $article->id . '/edit'))
            ->with('success', 'Regeneration queued.');
    }

    // generate_uuid4() comes from app/Helpers/uuid_helper.php (preloaded by BaseController).

    private function audit(string $action, int $subjectId, ?array $before, ?array $after): void
    {
        $user = $this->currentUser();

        if ($user === null) {
            return;
        }

        model(AuditLogModel::class)->record(
            $user->id,
            $action,
            'ai_image_job',
            $subjectId,
            $before,
            $after,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );
    }
}
