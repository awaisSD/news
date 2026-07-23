<?php

namespace App\Libraries\Publishing;

use App\Models\ArticleModel;

/**
 * Generates URL-safe, unique-per-category slugs for articles.
 *
 * Uniqueness is scoped to (primary_category_id, slug) rather than globally,
 * so two different categories may each have their own "/breaking-news"
 * style slug without colliding.
 */
class SlugGenerator
{
    /**
     * Hard cap on generated slug length. Kept comfortably under common
     * URL-path-segment limits while leaving room for a numeric suffix.
     */
    private const MAX_LENGTH = 200;

    public function __construct(private ?ArticleModel $articles = null)
    {
        $this->articles ??= model(ArticleModel::class);
    }

    /**
     * Build a unique slug for the given headline within the given category.
     *
     * @param int|null $excludeArticleId Pass the article's own id when
     *                                   regenerating a slug for an existing
     *                                   article so it doesn't collide with
     *                                   itself.
     */
    public function generate(string $headline, int $categoryId, ?int $excludeArticleId = null): string
    {
        $base = $this->slugify($headline);

        if ($base === '') {
            $base = 'article';
        }

        $candidate = $base;
        $suffix    = 1;

        while ($this->slugExists($candidate, $categoryId, $excludeArticleId)) {
            $suffix++;
            $candidate = $this->withSuffix($base, $suffix);
        }

        return $candidate;
    }

    /**
     * Lowercase, hyphenate, and length-cap a headline into a slug base.
     */
    private function slugify(string $headline): string
    {
        if (function_exists('url_title')) {
            $slug = url_title($headline, '-', true);
        } else {
            $slug = strtolower($headline);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
            $slug = trim($slug, '-');
        }

        return $this->truncateWithoutCuttingWords($slug, self::MAX_LENGTH);
    }

    /**
     * Truncate a hyphen-delimited slug to at most $maxLength characters
     * without cutting a word (hyphen-separated token) in half.
     */
    private function truncateWithoutCuttingWords(string $slug, int $maxLength): string
    {
        if (strlen($slug) <= $maxLength) {
            return $slug;
        }

        $truncated = substr($slug, 0, $maxLength);

        $lastHyphen = strrpos($truncated, '-');

        if ($lastHyphen !== false) {
            $truncated = substr($truncated, 0, $lastHyphen);
        }

        return trim($truncated, '-');
    }

    /**
     * Append a numeric suffix to a slug base, re-trimming length so the
     * result never exceeds MAX_LENGTH even with the suffix attached.
     */
    private function withSuffix(string $base, int $suffix): string
    {
        $suffixString = '-' . $suffix;

        $maxBaseLength = self::MAX_LENGTH - strlen($suffixString);

        if (strlen($base) > $maxBaseLength) {
            $base = $this->truncateWithoutCuttingWords($base, $maxBaseLength);
        }

        return $base . $suffixString;
    }

    /**
     * Whether a slug is already taken within the given category.
     */
    private function slugExists(string $slug, int $categoryId, ?int $excludeArticleId): bool
    {
        $builder = $this->articles
            ->where('primary_category_id', $categoryId)
            ->where('slug', $slug);

        if ($excludeArticleId !== null) {
            $builder = $builder->where('id !=', $excludeArticleId);
        }

        return $builder->countAllResults() > 0;
    }
}
