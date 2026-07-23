<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Libraries\Seo\NewsSitemapBuilder;
use App\Libraries\Seo\SitemapBuilder;
use Config\Cache as CacheConfig;

/**
 * Regular sitemap family (index/articles-chunk/categories/pages) is
 * "static-file-first": the `seo:generate-sitemaps` cron command periodically
 * writes these as plain files under public/ for zero-PHP-overhead serving
 * directly by the web server in production. Every method here still falls
 * back to building live via SitemapBuilder when the static file hasn't been
 * generated yet (fresh install, before the first cron run), so the routes
 * work correctly even before that job has ever executed.
 *
 * news() is the one exception — the Google News sitemap's entire value is
 * its near-real-time 2-day rolling window, so it is ALWAYS built live
 * (short-TTL cached, never a static file).
 */
class SitemapController extends BaseController
{
    public function index()
    {
        $this->response->setContentType('application/xml');

        $staticPath = FCPATH . 'sitemap-index.xml';

        if (is_file($staticPath)) {
            return $this->response->setBody(file_get_contents($staticPath));
        }

        $builder = new SitemapBuilder();

        return $this->response->setBody($builder->buildIndex($builder->countArticleChunksNeeded()));
    }

    public function articlesChunk(int $n)
    {
        $this->response->setContentType('application/xml');

        $staticPath = FCPATH . "sitemap-articles-{$n}.xml";

        if (is_file($staticPath)) {
            return $this->response->setBody(file_get_contents($staticPath));
        }

        return $this->response->setBody((new SitemapBuilder())->buildArticlesChunk($n));
    }

    public function categories()
    {
        $this->response->setContentType('application/xml');

        $staticPath = FCPATH . 'sitemap-categories.xml';

        if (is_file($staticPath)) {
            return $this->response->setBody(file_get_contents($staticPath));
        }

        return $this->response->setBody((new SitemapBuilder())->buildCategoriesSitemap());
    }

    public function pages()
    {
        $this->response->setContentType('application/xml');

        $staticPath = FCPATH . 'sitemap-pages.xml';

        if (is_file($staticPath)) {
            return $this->response->setBody(file_get_contents($staticPath));
        }

        return $this->response->setBody((new SitemapBuilder())->buildPagesSitemap());
    }

    public function news()
    {
        $this->response->setContentType('application/xml');

        $ttl = config(CacheConfig::class)->ttls['news_sitemap'];

        $xml = cache()->remember('news_sitemap', $ttl, static function (): string {
            return (new NewsSitemapBuilder())->build(new \DateTimeImmutable());
        });

        return $this->response->setBody($xml);
    }

    public function robots()
    {
        $this->response->setContentType('text/plain');

        return "User-agent: *\n"
            . "Disallow: /admin/\n"
            . 'Sitemap: ' . site_url('sitemap-index.xml') . "\n"
            . 'Sitemap: ' . site_url('news-sitemap.xml') . "\n";
    }
}
