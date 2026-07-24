<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Single source of truth for site/publisher identity.
 *
 * Every place that emits Organization/NewsArticle JSON-LD (Libraries\Seo\JsonLdBuilder),
 * the site header/footer, and any Google Publisher Center-facing metadata
 * must read from here rather than hardcoding the name/logo — Publisher
 * Center requires this identity to be consistent sitewide.
 *
 * NOTE: deliberately NOT named `Config\Publisher` — that class name is
 * load-bearing in the framework itself (CodeIgniter\Publisher\Publisher,
 * the vendor-asset-publishing utility behind `php spark publish`, reads
 * `config(Config\Publisher::class)->restrictions`). Squatting on that name
 * for an unrelated purpose would fatal-error the moment anything touches
 * that utility, the same way a missing/mismatched Config\Exceptions did.
 */
class SiteIdentity extends BaseConfig
{
    public string $name = 'Your News Site';

    public string $legalName = 'Your Publishing Company LLC';

    public string $url = 'https://www.example-news-site.com/';

    public string $logoUrl = 'https://www.example-news-site.com/assets/logo-600x60.png';

    public int $logoWidth = 600;

    public int $logoHeight = 60;

    public string $contactEmail = 'editors@example-news-site.com';

    /**
     * BCP-47 language code, used in NewsArticle JSON-LD and the
     * <news:language> element of news-sitemap.xml.
     */
    public string $newsLanguage = 'en';

    /**
     * Social profile URLs surfaced via sameAs on the Organization node —
     * an E-E-A-T signal. Fill in once real profiles exist.
     *
     * @var array<int, string>
     */
    public array $sameAs = [];

    public function __construct()
    {
        parent::__construct();

        $this->name         = env('publisher.name', $this->name);
        $this->legalName    = env('publisher.legalName', $this->legalName);
        $this->url           = env('publisher.url', $this->url);
        $this->logoUrl       = env('publisher.logoUrl', $this->logoUrl);
        $this->logoWidth     = (int) env('publisher.logoWidth', $this->logoWidth);
        $this->logoHeight    = (int) env('publisher.logoHeight', $this->logoHeight);
        $this->contactEmail  = env('publisher.contactEmail', $this->contactEmail);
        $this->newsLanguage  = env('publisher.newsLanguage', $this->newsLanguage);
    }
}
