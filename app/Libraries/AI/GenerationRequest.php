<?php

namespace App\Libraries\AI;

/**
 * DTO passed to AIProviderInterface::generateArticle().
 *
 * Serves two distinct call shapes, distinguished by $mode:
 *
 * - mode = 'article': a fresh-article draft request. $topicTitle/$brief/
 *   $angleNotes describe the topic to write about, and $wordCountTarget is
 *   the target length.
 *
 * - mode = 'style_pass': a house-style/readability assist request (see
 *   EditorialAssistService). In this mode $brief is repurposed to hold the
 *   CURRENT draft body_html that should be rewritten, $houseStyleNotes
 *   carries the style guidance to apply, and $topicTitle/$angleNotes are
 *   typically unused (may be passed through for context, e.g. the article's
 *   current headline).
 *
 * Kept as a single DTO (rather than two separate request types) because
 * both call shapes are handled by the exact same provider method and the
 * exact same underlying "ask the model for headline/body_html/excerpt JSON"
 * wire contract — only the prompt construction branches on $mode.
 */
class GenerationRequest
{
    public function __construct(
        public readonly string $topicTitle,
        public readonly string $brief,
        public readonly ?string $angleNotes,
        public readonly string $targetCategorySlug,
        public readonly int $wordCountTarget = 700,
        public readonly ?string $houseStyleNotes = null,
        public readonly string $mode = 'article',
    ) {
    }
}
