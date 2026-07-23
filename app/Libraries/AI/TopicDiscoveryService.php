<?php

namespace App\Libraries\AI;

use App\Entities\Topic;
use App\Models\TopicModel;
use App\Models\TopicSourceModel;
use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Discovers candidate story topics from RSS/Atom feeds and turns them (or a
 * purely manual idea) into a `topics` row an editor can assign for AI-
 * assisted drafting.
 *
 * `topic_sources` is deliberately METADATA-ONLY (source name/url/title/a
 * short summary) — it never stores full scraped article bodies. This is
 * what keeps topic discovery from turning into "scrape a competitor's
 * article and have the model spin it" — the model only ever sees a title
 * and a short summary, never a full source body, when a topic is later
 * promoted and drafted.
 */
class TopicDiscoveryService
{
    /**
     * Cap on how much of a feed item's description/summary is persisted —
     * keeps topic_sources.summary metadata-sized, never a full article body.
     */
    private const SUMMARY_MAX_LENGTH = 1000;

    private Client $http;

    public function __construct(
        private ?TopicModel $topics = null,
        private ?TopicSourceModel $topicSources = null,
    ) {
        $this->topics ??= model(TopicModel::class);
        $this->topicSources ??= model(TopicSourceModel::class);
        $this->http = new Client(['timeout' => 20]);
    }

    /**
     * Fetches and parses each feed URL, inserting one topic_sources row per
     * item. One bad/unreachable feed never aborts the batch — failures are
     * caught per-feed and logged.
     *
     * @param string[] $feedUrls
     */
    public function ingestFeeds(array $feedUrls, DateTimeImmutable $now): int
    {
        $ingested = 0;

        foreach ($feedUrls as $feedUrl) {
            try {
                $ingested += $this->ingestSingleFeed($feedUrl, $now);
            } catch (\Throwable $e) {
                log_message('error', 'TopicDiscoveryService: failed to ingest feed {url}: {message}', [
                    'url'     => $feedUrl,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $ingested;
    }

    private function ingestSingleFeed(string $feedUrl, DateTimeImmutable $now): int
    {
        try {
            $response = $this->http->get($feedUrl);
        } catch (GuzzleException $e) {
            throw new \RuntimeException("Failed to fetch feed: {$e->getMessage()}", previous: $e);
        }

        $body = (string) $response->getBody();

        // Suppress libxml warnings on malformed feeds; we handle failure
        // via the false return value instead of PHP warnings/exceptions.
        $previousSetting = libxml_use_internal_errors(true);
        $xml             = simplexml_load_string($body, \SimpleXMLElement::class, LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previousSetting);

        if ($xml === false) {
            throw new \RuntimeException('Feed body could not be parsed as XML.');
        }

        $items       = $this->extractItems($xml);
        $sourceName  = $this->extractFeedTitle($xml);
        $fetchedAt   = $now->format('Y-m-d H:i:s');
        $ingested    = 0;

        foreach ($items as $item) {
            // skipValidation(true): this table's current validationRules
            // require a `topic_id`, but topic_sources rows are intentionally
            // topic-less at ingest time (the relationship is the other way
            // around — topics.source_ids references topic_sources.id once
            // an editor promotes sources into a topic; see promoteToTopic()
            // below). The topic_sources migration itself has no topic_id
            // column at all, so this insert is correct against the actual
            // schema regardless.
            $this->topicSources->skipValidation(true)->insert([
                'source_name'          => $sourceName,
                'source_url'           => $item['link'],
                'title'                => $item['title'],
                'summary'              => $item['summary'],
                'published_at_source'  => $item['published_at_source'],
                'fetched_at'           => $fetchedAt,
            ]);

            $ingested++;
        }

        return $ingested;
    }

    /**
     * @return array<int, array{title: string, link: string, summary: ?string, published_at_source: ?string}>
     */
    private function extractItems(\SimpleXMLElement $xml): array
    {
        $items = [];

        if (isset($xml->channel->item)) {
            // RSS 2.0
            foreach ($xml->channel->item as $item) {
                $items[] = [
                    'title'               => trim((string) $item->title),
                    'link'                => trim((string) $item->link),
                    'summary'             => $this->truncateSummary((string) $item->description),
                    'published_at_source' => $this->parseDate((string) $item->pubDate),
                ];
            }
        } elseif (isset($xml->entry)) {
            // Atom
            foreach ($xml->entry as $entry) {
                $link = '';

                foreach ($entry->link as $linkNode) {
                    $attributes = $linkNode->attributes();
                    $rel        = (string) ($attributes['rel'] ?? 'alternate');

                    if ($rel === 'alternate' || $link === '') {
                        $link = (string) ($attributes['href'] ?? '');
                    }
                }

                $summarySource = (string) ($entry->summary ?: $entry->content);
                $dateSource    = (string) ($entry->updated ?: $entry->published);

                $items[] = [
                    'title'               => trim((string) $entry->title),
                    'link'                => trim($link),
                    'summary'             => $this->truncateSummary($summarySource),
                    'published_at_source' => $this->parseDate($dateSource),
                ];
            }
        }

        // Metadata-only guard: never let title/link come back empty, since
        // topic_sources requires both.
        return array_values(array_filter(
            $items,
            static fn (array $item): bool => $item['title'] !== '' && $item['link'] !== ''
        ));
    }

    private function extractFeedTitle(\SimpleXMLElement $xml): string
    {
        $title = (string) ($xml->channel->title ?? $xml->title ?? '');
        $title = trim($title);

        return $title !== '' ? mb_substr($title, 0, 150) : 'Untitled feed';
    }

    private function truncateSummary(string $summary): ?string
    {
        $summary = trim(strip_tags($summary));

        if ($summary === '') {
            return null;
        }

        return mb_substr($summary, 0, self::SUMMARY_MAX_LENGTH);
    }

    private function parseDate(string $date): ?string
    {
        if (trim($date) === '') {
            return null;
        }

        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return null;
        }

        return (new DateTimeImmutable('@' . $timestamp))->format('Y-m-d H:i:s');
    }

    /**
     * @param int[] $sourceIds
     */
    public function promoteToTopic(
        array $sourceIds,
        string $title,
        string $brief,
        ?string $angleNotes,
        ?int $assignedEditorId,
        DateTimeImmutable $now
    ): Topic {
        $id = $this->topics->insert([
            'title'              => $title,
            'brief'              => $brief,
            'angle_notes'        => $angleNotes,
            'source_ids'         => json_encode($sourceIds),
            'created_via'        => 'rss',
            'assigned_editor_id' => $assignedEditorId,
            'status'             => $assignedEditorId !== null ? 'assigned' : 'new',
            'created_at'         => $now->format('Y-m-d H:i:s'),
        ], true);

        return $this->topics->find($id);
    }

    /**
     * Manual topic entry. This is expected to be the MAJORITY workflow —
     * most topics originate from an editor typing up an idea directly,
     * with RSS ingestion as a secondary discovery aid.
     */
    public function manualTopic(
        string $title,
        string $brief,
        ?string $angleNotes,
        ?int $assignedEditorId,
        DateTimeImmutable $now
    ): Topic {
        $id = $this->topics->insert([
            'title'              => $title,
            'brief'              => $brief,
            'angle_notes'        => $angleNotes,
            'source_ids'         => null,
            'created_via'        => 'manual',
            'assigned_editor_id' => $assignedEditorId,
            'status'             => $assignedEditorId !== null ? 'assigned' : 'new',
            'created_at'         => $now->format('Y-m-d H:i:s'),
        ], true);

        return $this->topics->find($id);
    }

    /**
     * OPTIONAL AI-assist: suggest 2-3 short angle ideas for a topic.
     *
     * TODO(product): angle-suggestion is a nice-to-have, not required for
     * MVP. AIProviderInterface::generateArticle() is shaped for a full
     * article draft (headline/body_html/excerpt), which is the wrong
     * contract for "give me 2-3 short angle ideas" — reusing it here would
     * mean repurposing article fields to mean something else, or standing
     * up a second AI interface for a minor feature. Neither is worth it for
     * MVP; wire up a dedicated lightweight prompt call here if/when this
     * becomes a priority.
     *
     * @return string[]
     */
    public function suggestAngles(Topic $topic): array
    {
        return [];
    }
}
