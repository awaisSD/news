<?php

namespace App\Libraries\AI;

use App\Entities\AiImageJob;
use App\Entities\Article;

/**
 * Generates alt text for an AI-generated (or any) image job.
 *
 * TODO(product): replace with a real vision/text AI call for richer alt
 * text once budget/latency tradeoffs are decided. Both AIProviderInterface
 * methods are shaped for a full article draft (headline/body_html/excerpt),
 * which is the wrong contract for "describe this image in one sentence" —
 * reusing it here would mean repurposing article fields to mean something
 * else. A deterministic, non-AI fallback derived from the article's
 * headline is a reasonable and intentional choice for MVP; it always
 * produces a usable, non-empty alt text with zero added latency or cost.
 */
class AltTextService
{
    private const MAX_HEADLINE_LENGTH = 100;

    public function generate(AiImageJob $job, Article $article): string
    {
        $headline = mb_substr(strip_tags((string) $article->headline), 0, self::MAX_HEADLINE_LENGTH);

        return 'Photo illustrating: ' . $headline;
    }
}
