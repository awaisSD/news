<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * RSS/Atom feed sources for `php spark ai:ingest-topics`.
 *
 * Empty by default — manual topic entry in the admin panel is expected to
 * be the majority workflow (see TopicDiscoveryService::manualTopic()); feed
 * ingestion is a secondary discovery aid editors can opt into by adding
 * URLs here.
 */
class NewsFeeds extends BaseConfig
{
    /**
     * @var string[]
     *
     * Example shape:
     * public array $feedUrls = [
     *     'https://example.com/rss',
     *     'https://another-source.example.com/feed.xml',
     * ];
     */
    public array $feedUrls = [];
}
