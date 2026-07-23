<?php

/**
 * Small view-layer SEO helpers. Kept as plain functions (rather than methods
 * on JsonLdBuilder) because they're output-formatting concerns used directly
 * from Views, not part of the pure JSON-LD array-building responsibility.
 */

if (! function_exists('render_jsonld')) {
    /**
     * Renders a JSON-LD array as a <script type="application/ld+json"> tag.
     *
     * Pretty-printed outside production for readability while debugging;
     * compact (no JSON_PRETTY_PRINT) in production to save bytes on every
     * page load.
     */
    function render_jsonld(array $data): string
    {
        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            $flags |= JSON_PRETTY_PRINT;
        }

        return '<script type="application/ld+json">' . json_encode($data, $flags) . '</script>';
    }
}

if (! function_exists('meta_description_or_default')) {
    /**
     * Escapes and truncates a meta description for safe output in a
     * <meta name="description"> tag, falling back to $fallback when
     * $description is null/empty.
     */
    function meta_description_or_default(?string $description, string $fallback = ''): string
    {
        $value = htmlspecialchars($description ?: $fallback, ENT_QUOTES, 'UTF-8');

        return mb_substr($value, 0, 300);
    }
}
