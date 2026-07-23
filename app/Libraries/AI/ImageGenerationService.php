<?php

namespace App\Libraries\AI;

use App\Entities\AiImageJob;
use App\Entities\Article;
use App\Entities\User;
use App\Models\AiImageJobModel;
use Config\AIPipeline;
use Config\Media as MediaConfig;
use Config\Services;
use DateTimeImmutable;
use LogicException;

/**
 * Generates a candidate featured image for an article via the configured
 * image provider and stores the raw bytes on disk.
 *
 * IMPORTANT: this class deliberately stops at "generate bytes, save to
 * disk, mark the job completed." It NEVER creates a `media` row and NEVER
 * touches `articles.featured_media_id` — attaching a generated image to a
 * live article only happens when a human editor explicitly approves the
 * job, which is implemented in the Admin `ImageJobsController` (written by
 * a different agent). Keeping that step out of this class is what
 * guarantees an AI-generated image can never appear on a live article
 * without an explicit human approval.
 */
class ImageGenerationService
{
    public function __construct(
        private ?AiImageJobModel $imageJobs = null,
    ) {
        $this->imageJobs ??= model(AiImageJobModel::class);
    }

    public function requestImage(Article $article, string $prompt, User $requestedBy, DateTimeImmutable $now): AiImageJob
    {
        /** @var AIPipeline $config */
        $config = config(AIPipeline::class);

        $id = $this->imageJobs->insert([
            'article_id'   => $article->id,
            'provider'     => $config->imageProvider,
            'prompt'       => $prompt,
            'status'       => 'pending',
            'requested_by' => $requestedBy->id,
            'created_at'   => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->imageJobs->find($id);
    }

    /**
     * @throws LogicException if the job is not currently `processing`.
     */
    public function process(AiImageJob $job, DateTimeImmutable $now): AiImageJob
    {
        if ($job->status !== 'processing') {
            throw new LogicException(
                "AiImageJob #{$job->id} must be claimed (status=processing) before processing; got status={$job->status}."
            );
        }

        try {
            $result = Services::imageProvider()->generateImage(new ImageRequest($job->prompt, 1200, 630));
        } catch (AiProviderException $e) {
            log_message('error', 'ImageGenerationService: job #{jobId} failed: {message}', [
                'jobId'   => $job->id,
                'message' => $e->getMessage(),
            ]);

            $this->imageJobs->update($job->id, [
                'status'       => 'failed',
                'completed_at' => $now->format('Y-m-d H:i:s'),
            ]);

            return $this->imageJobs->find($job->id);
        }

        /** @var MediaConfig $mediaConfig */
        $mediaConfig = config(MediaConfig::class);

        $directory = rtrim($mediaConfig->uploadPath, '/') . '/ai/';

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = $directory . $job->id . '.png';

        file_put_contents($path, $result->binaryData);

        $this->imageJobs->update($job->id, [
            'status'         => 'completed',
            'generated_path' => $path,
            'width'          => $result->width,
            'height'         => $result->height,
            'cost_usd'       => $result->costUsd,
            'completed_at'   => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->imageJobs->find($job->id);
    }
}
