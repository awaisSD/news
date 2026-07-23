<?php

namespace App\Libraries\Seo;

use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\PageModel;
use DateTimeImmutable;

/**
 * Builds the REGULAR sitemap family (sitemap-index.xml, sitemap-articles-N.xml,
 * sitemap-categories.xml, sitemap-pages.xml). Regenerated periodically by the
 * `seo:generate-sitemaps` CLI command and written as static files under
 * public/ for zero-PHP-overhead serving.
 *
 * The Google News sitemap (near-real-time, 2-day rolling window) is a
 * separate concern — see NewsSitemapBuilder, served live by
 * Front\SitemapController::news().
 */
class SitemapBuilder
{
    /**
     * Google's sitemap protocol caps a single sitemap file at 50,000 URLs
     * (and 50MB uncompressed).
     */
    public const MAX_URLS_PER_CHUNK = 50000;

    public function __construct(
        private readonly ArticleModel $articles = new ArticleModel(),
        private readonly CategoryModel $categories = new CategoryModel(),
        private readonly PageModel $pages = new PageModel()
    ) {
    }

    /**
     * Builds the <sitemapindex> pointing at every article chunk plus the
     * categories and pages sitemaps.
     *
     * $now is accepted for cleanliness/testability of the lastmod values,
     * but sitemap generation is inherently "as of generation time" — this
     * class is allowed to default it internally, unlike NewsSitemapBuilder
     * where the 2-day freshness window is the entire point of the class.
     */
    public function buildIndex(int $totalArticleChunks, ?DateTimeImmutable $now = null): string
    {
        $now      = $now ?? new DateTimeImmutable();
        $lastmod  = $this->xmlEscape($now->format(DATE_ATOM));
        $entries  = [];

        for ($i = 1; $i <= max(1, $totalArticleChunks); $i++) {
            $entries[] = $this->sitemapEntry(site_url("sitemap-articles-{$i}.xml"), $lastmod);
        }

        $entries[] = $this->sitemapEntry(site_url('sitemap-categories.xml'), $lastmod);
        $entries[] = $this->sitemapEntry(site_url('sitemap-pages.xml'), $lastmod);

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . implode('', $entries)
            . '</sitemapindex>' . "\n";
    }

    public function countArticleChunksNeeded(): int
    {
        $total = $this->articles->where('status', 'published')->countAllResults();

        return max(1, (int) ceil($total / self::MAX_URLS_PER_CHUNK));
    }

    /**
     * Offset-paginates published articles into a single <urlset> chunk.
     *
     * Uses OFFSET pagination because this runs as an infrequent background
     * job (cron), not a user/crawler-latency-sensitive path — unlike the
     * front-end category listing, which uses keyset pagination in
     * ArticleModel::listPublishedForCategory(). Revisit if article volume
     * grows large enough that offset pagination itself becomes the
     * bottleneck here too.
     */
    public function buildArticlesChunk(int $chunkNumber): string
    {
        $offset = max(0, ($chunkNumber - 1) * self::MAX_URLS_PER_CHUNK);

        $rows = $this->articles
            ->select('articles.slug, articles.published_at, articles.updated_at_content, categories.slug AS category_slug')
            ->join('categories', 'categories.id = articles.primary_category_id')
            ->where('articles.status', 'published')
            ->orderBy('articles.id', 'ASC')
            ->limit(self::MAX_URLS_PER_CHUNK, $offset)
            ->asArray()
            ->findAll();

        $urls = [];

        foreach ($rows as $row) {
            if (empty($row['category_slug']) || empty($row['slug'])) {
                continue;
            }

            $loc     = site_url($row['category_slug'] . '/' . $row['slug']);
            $lastmod = $row['updated_at_content'] ?? $row['published_at'];

            $urls[] = $this->urlEntry($loc, $lastmod);
        }

        return $this->wrapUrlset($urls);
    }

    public function buildCategoriesSitemap(): string
    {
        $categories = $this->categories->where('is_active', 1)->findAll();

        $urls = [];

        foreach ($categories as $category) {
            $urls[] = $this->urlEntry(site_url($category->slug));
        }

        return $this->wrapUrlset($urls);
    }

    public function buildPagesSitemap(): string
    {
        $pages = $this->pages->where('is_published', 1)->findAll();

        $urls = [];

        foreach ($pages as $page) {
            $urls[] = $this->urlEntry(site_url($page->slug));
        }

        return $this->wrapUrlset($urls);
    }

    private function sitemapEntry(string $loc, string $lastmod): string
    {
        return '<sitemap>'
            . '<loc>' . $this->xmlEscape($loc) . '</loc>'
            . '<lastmod>' . $lastmod . '</lastmod>'
            . '</sitemap>' . "\n";
    }

    private function urlEntry(string $loc, mixed $lastmod = null): string
    {
        $xml = '<url><loc>' . $this->xmlEscape($loc) . '</loc>';

        if ($lastmod !== null) {
            $formatted = $this->formatLastmod($lastmod);

            if ($formatted !== null) {
                $xml .= '<lastmod>' . $this->xmlEscape($formatted) . '</lastmod>';
            }
        }

        $xml .= '</url>' . "\n";

        return $xml;
    }

    private function formatLastmod(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_object($value) && method_exists($value, 'format')) {
            return $value->format(DATE_ATOM);
        }

        $timestamp = strtotime((string) $value);

        return $timestamp !== false ? date(DATE_ATOM, $timestamp) : null;
    }

    /**
     * @param string[] $urlEntries pre-rendered <url>...</url> XML fragments
     */
    private function wrapUrlset(array $urlEntries): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
            . implode('', $urlEntries)
            . '</urlset>' . "\n";
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
