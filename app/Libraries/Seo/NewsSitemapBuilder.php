<?php

namespace App\Libraries\Seo;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use Config\Publisher;
use DateTimeImmutable;

/**
 * Builds news-sitemap.xml per the Google News sitemap spec: max 1000 URLs,
 * only articles published within the last 48 hours, using the
 * http://www.google.com/schemas/sitemap-news/0.9 namespace alongside the
 * base sitemap namespace.
 *
 * Served live (with a short cache TTL — see Config\Cache::$ttls['news_sitemap'])
 * by Front\SitemapController::news(), NOT written by the periodic
 * `seo:generate-sitemaps` cron — its rolling 2-day window needs much fresher
 * regeneration than that cron affords.
 */
class NewsSitemapBuilder
{
    public function __construct(
        private readonly ArticleModel $articles = new ArticleModel(),
        private readonly CategoryModel $categories = new CategoryModel()
    ) {
    }

    /**
     * $now is a required param (no internal default) since the 2-day
     * freshness window is the entire point of this class and must be driven
     * by the caller (Front\SitemapController or a cron command), not hidden
     * inside the library.
     */
    public function build(DateTimeImmutable $now): string
    {
        $cutoff   = $now->modify('-48 hours');
        $articles = $this->articles->recentForNewsSitemap($cutoff, 1000);

        /** @var Publisher $pub */
        $pub = config(Publisher::class);

        $categorySlugsById = $this->categorySlugsFor($articles);

        $urls = [];

        foreach ($articles as $article) {
            $categorySlug = $categorySlugsById[$article->primary_category_id] ?? null;

            if ($categorySlug === null || $article->published_at === null) {
                continue;
            }

            $loc = site_url($categorySlug . '/' . $article->slug);

            $urls[] = '<url>'
                . '<loc>' . $this->xmlEscape($loc) . '</loc>'
                . '<news:news>'
                . '<news:publication>'
                . '<news:name>' . $this->xmlEscape($pub->name) . '</news:name>'
                . '<news:language>' . $this->xmlEscape($pub->newsLanguage) . '</news:language>'
                . '</news:publication>'
                . '<news:publication_date>' . $this->xmlEscape($article->published_at->format(DATE_ATOM)) . '</news:publication_date>'
                . '<news:title>' . $this->xmlEscape($article->headline) . '</news:title>'
                . '</news:news>'
                . '</url>' . "\n";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
            . 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n"
            . implode('', $urls)
            . '</urlset>' . "\n";
    }

    /**
     * Resolves primary_category_id => slug for exactly the categories
     * referenced by $articles, in a single lookup query.
     *
     * @param \App\Entities\Article[] $articles
     *
     * @return array<int, string>
     */
    private function categorySlugsFor(array $articles): array
    {
        $categoryIds = array_values(array_unique(array_filter(
            array_map(static fn ($article) => $article->primary_category_id, $articles)
        )));

        if ($categoryIds === []) {
            return [];
        }

        $rows = $this->categories
            ->select('id, slug')
            ->whereIn('id', $categoryIds)
            ->asArray()
            ->findAll();

        $map = [];

        foreach ($rows as $row) {
            $map[$row['id']] = $row['slug'];
        }

        return $map;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
