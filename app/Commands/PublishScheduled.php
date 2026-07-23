<?php

namespace App\Commands;

use App\Libraries\EditorialReviewService;
use App\Models\ArticleModel;
use App\Models\UserModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use DateTimeImmutable;

/**
 * php spark articles:publish-scheduled
 *
 * Flips articles from `approved` to `published` once their `publish_at`
 * timestamp has arrived.
 *
 * IMPORTANT: this command does NOT perform editorial review. An article can
 * only reach `approved` status via EditorialReviewService::approve(), which
 * is only ever called by a human editor/admin through the admin UI (see the
 * governance note on that method). This command only flips the clock-gated
 * `approved -> published` transition for articles a human has already
 * signed off on — it never moves an article into `approved` itself, so it
 * cannot be used to bypass the human-review requirement.
 */
class PublishScheduled extends BaseCommand
{
    protected $group       = 'Publishing';
    protected $name        = 'articles:publish-scheduled';
    protected $description = 'Publishes approved articles whose publish_at has arrived.';

    public function run(array $params)
    {
        // The CLI command layer is the correct boundary for resolving "now"
        // — the services themselves never call new \DateTime()/date() etc.
        $now = new DateTimeImmutable();

        $articles = model(ArticleModel::class)
            ->where('status', 'approved')
            ->where('publish_at IS NOT NULL')
            ->where('publish_at <=', $now->format('Y-m-d H:i:s'))
            ->findAll();

        if ($articles === []) {
            CLI::write('No approved articles are due for publishing.', 'yellow');

            return;
        }

        $userModel = model(UserModel::class);
        $service   = new EditorialReviewService();

        foreach ($articles as $article) {
            // Attribute the (already-approved) publish action in the audit
            // log to the editor responsible for the article: prefer the
            // assigned editor, then whoever last acted as editor_id, then
            // fall back to the author as a last resort so the log always
            // has a valid reviewer_id.
            $actingUserId = $article->assigned_editor_id
                ?? $article->editor_id
                ?? $article->author_id;

            $actingUser = $userModel->find($actingUserId);

            if ($actingUser === null) {
                CLI::write(
                    "Skipping article #{$article->id} ({$article->headline}): no valid acting user found for id {$actingUserId}.",
                    'red'
                );

                continue;
            }

            $service->publish($article, $actingUser, $now);

            CLI::write(
                "Published article #{$article->id}: {$article->headline}",
                'green'
            );
        }
    }
}
