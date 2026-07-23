<?php

namespace App\Libraries\AI;

/**
 * DTO returned by AIProviderInterface::generateArticle(). Represents a
 * SUGGESTION only — nothing in this pipeline writes this content to a live
 * article or flips articles.status without an explicit human accept/approve
 * step (see ArticleGenerationService::process() and
 * EditorialAssistService::acceptSuggestion()).
 */
class GenerationResult
{
    /**
     * @param array<string, mixed> $rawResponseMetadata Full raw provider
     *                                                   response payload
     *                                                   (minus the API key),
     *                                                   stored verbatim in
     *                                                   ai_generation_jobs.response_metadata
     *                                                   for audit purposes.
     */
    public function __construct(
        public readonly string $headline,
        public readonly string $bodyHtml,
        public readonly string $excerpt,
        public readonly int $wordCount,
        public readonly array $rawResponseMetadata,
        public readonly ?float $costUsd = null,
    ) {
    }
}
