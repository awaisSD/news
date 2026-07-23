<?php

namespace App\Commands;

use App\Libraries\Seo\SitemapBuilder;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark seo:generate-sitemaps
 *
 * Regenerates the static sitemap.xml family (sitemap-index.xml,
 * sitemap-articles-N.xml, sitemap-categories.xml, sitemap-pages.xml) and
 * writes them directly under public/ (FCPATH), so Apache can serve them with
 * zero PHP overhead per the project's caching strategy.
 *
 * Recommended to be scheduled every 15-30 minutes via system cron
 * (e.g. `* * * * * ... ` at whatever cadence matches your publishing volume —
 * 15-30 minutes is a good default since these files are not latency-critical
 * the way news-sitemap.xml is).
 *
 * Does NOT generate news-sitemap.xml — that one is intentionally served
 * live (with a short cache TTL) by Front\SitemapController::news(), because
 * its 2-day rolling window needs much fresher regeneration than a 15-30
 * minute cron affords.
 */
class GenerateSitemaps extends BaseCommand
{
    protected $group       = 'SEO';
    protected $name        = 'seo:generate-sitemaps';
    protected $description = 'Regenerates static sitemap.xml family files (not the near-real-time news-sitemap.xml, which is served live/cached).';

    public function run(array $params)
    {
        $builder = new SitemapBuilder();

        $chunks = $builder->countArticleChunksNeeded();

        $written = [];

        $indexPath = FCPATH . 'sitemap-index.xml';
        file_put_contents($indexPath, $builder->buildIndex($chunks));
        $written[] = $indexPath;

        for ($i = 1; $i <= $chunks; $i++) {
            $chunkPath = FCPATH . "sitemap-articles-{$i}.xml";
            file_put_contents($chunkPath, $builder->buildArticlesChunk($i));
            $written[] = $chunkPath;
        }

        $categoriesPath = FCPATH . 'sitemap-categories.xml';
        file_put_contents($categoriesPath, $builder->buildCategoriesSitemap());
        $written[] = $categoriesPath;

        $pagesPath = FCPATH . 'sitemap-pages.xml';
        file_put_contents($pagesPath, $builder->buildPagesSitemap());
        $written[] = $pagesPath;

        CLI::write('Sitemap generation complete.', 'green');
        CLI::write("  Article chunks: {$chunks}");

        foreach ($written as $path) {
            CLI::write('  Wrote ' . str_replace(FCPATH, '', $path));
        }
    }
}
