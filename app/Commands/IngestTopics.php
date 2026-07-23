<?php

namespace App\Commands;

use App\Libraries\AI\TopicDiscoveryService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\NewsFeeds;
use DateTimeImmutable;

/**
 * php spark ai:ingest-topics
 *
 * Fetches every feed URL configured in Config\NewsFeeds and stores
 * metadata-only topic_sources rows for editors to review and (optionally)
 * promote into a topic. Manual topic entry in the admin panel is expected
 * to be the majority workflow — this command is a secondary discovery aid.
 */
class IngestTopics extends BaseCommand
{
    protected $group       = 'AI Pipeline';
    protected $name        = 'ai:ingest-topics';
    protected $description = 'Fetches configured RSS/Atom feeds and stores topic_sources metadata for editors to review.';

    public function run(array $params)
    {
        /** @var NewsFeeds $config */
        $config = config(NewsFeeds::class);

        if ($config->feedUrls === []) {
            CLI::write(
                'No feed sources configured — add URLs to Config\\NewsFeeds::$feedUrls, or use manual topic entry in the admin panel.',
                'yellow'
            );

            return;
        }

        $count = (new TopicDiscoveryService())->ingestFeeds($config->feedUrls, new DateTimeImmutable());

        CLI::write("Ingested {$count} topic source item(s).", 'green');
    }
}
