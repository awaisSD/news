<?php

namespace App\Commands;

use App\Libraries\AI\ArticleGenerationService;
use App\Libraries\AI\EditorialAssistService;
use App\Libraries\AI\ImageGenerationService;
use App\Models\AiGenerationJobModel;
use App\Models\AiImageJobModel;
use App\Models\ArticleModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/**
 * php spark ai:process-queue
 *
 * Claims and processes pending `ai_generation_jobs` (article drafts +
 * style-pass suggestions) and `ai_image_jobs`, one worker "tick" at a time.
 * Intended to be run on a short interval (e.g. cron every minute) so a
 * single tick never needs to run indefinitely — each queue is capped at
 * MAX_ITERATIONS_PER_QUEUE claims per run.
 *
 * This command NEVER publishes anything — every job it processes leaves
 * its output as a suggestion (a new `in_review` article, or a job row
 * carrying a style-pass suggestion / generated image) for a human editor to
 * review through the normal admin UI.
 */
class ProcessAiQueue extends BaseCommand
{
    protected $group       = 'AI Pipeline';
    protected $name        = 'ai:process-queue';
    protected $description = 'Claims and processes pending AI generation and image jobs.';

    /**
     * House style notes applied to every style_pass job processed by this
     * command run.
     *
     * TODO(product): make this configurable (e.g. via ai_settings) instead
     * of a hardcoded default once house style needs to vary by
     * section/editor.
     */
    private const DEFAULT_HOUSE_STYLE_NOTES = 'Write in clear, concise AP-style prose. '
        . 'Avoid hype and clickbait phrasing. Keep sentences under ~25 words where natural.';

    /**
     * Bounds how many jobs a single command invocation will claim per
     * queue, so one cron tick can never run unboundedly long.
     */
    private const MAX_ITERATIONS_PER_QUEUE = 50;

    public function run(array $params)
    {
        $now      = new DateTimeImmutable();
        $workerId = (string) getmypid();

        $processedGenerationJobs = $this->processGenerationJobs($workerId, $now);
        $processedImageJobs      = $this->processImageJobs($workerId, $now);

        CLI::write(
            "Processed {$processedGenerationJobs} generation job(s) and {$processedImageJobs} image job(s).",
            'green'
        );
    }

    private function processGenerationJobs(string $workerId, DateTimeImmutable $now): int
    {
        $generationJobs = model(AiGenerationJobModel::class);
        $articles       = model(ArticleModel::class);
        $articleService = new ArticleGenerationService();
        $assistService  = new EditorialAssistService();

        $processed = 0;

        for ($i = 0; $i < self::MAX_ITERATIONS_PER_QUEUE; $i++) {
            $job = $generationJobs->claimNextPending($workerId, $now->format('Y-m-d H:i:s'));

            if ($job === null) {
                break;
            }

            $generationJobs->update($job->id, ['started_at' => $now->format('Y-m-d H:i:s')]);

            try {
                if ($job->job_type === 'article') {
                    $articleService->process($job, $now);
                } elseif ($job->job_type === 'style_pass') {
                    $article = $articles->find($job->article_id);

                    if ($article === null) {
                        throw new RuntimeException(
                            "style_pass job #{$job->id} references a missing article #{$job->article_id}."
                        );
                    }

                    $assistService->process($job, $article, self::DEFAULT_HOUSE_STYLE_NOTES, $now);
                } else {
                    throw new RuntimeException("Unknown job_type '{$job->job_type}' for job #{$job->id}.");
                }

                $processed++;
            } catch (Throwable $e) {
                CLI::error("Job #{$job->id} ({$job->job_type}) failed: {$e->getMessage()}");
            }
        }

        return $processed;
    }

    private function processImageJobs(string $workerId, DateTimeImmutable $now): int
    {
        $imageJobs    = model(AiImageJobModel::class);
        $imageService = new ImageGenerationService();

        $processed = 0;

        for ($i = 0; $i < self::MAX_ITERATIONS_PER_QUEUE; $i++) {
            $job = $imageJobs->claimNextPending($workerId, $now->format('Y-m-d H:i:s'));

            if ($job === null) {
                break;
            }

            try {
                $imageService->process($job, $now);
                $processed++;
            } catch (Throwable $e) {
                CLI::error("Image job #{$job->id} failed: {$e->getMessage()}");
            }
        }

        return $processed;
    }
}
