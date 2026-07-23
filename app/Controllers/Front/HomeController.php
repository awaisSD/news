<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Controllers\Front\Concerns\BatchesCategorySlugs;
use App\Controllers\Front\Concerns\BatchesFeaturedMedia;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use Config\Cache as CacheConfig;
use Config\Publisher;

class HomeController extends BaseController
{
    use BatchesFeaturedMedia;
    use BatchesCategorySlugs;

    private const LATEST_LIMIT = 10;

    public function index()
    {
        // Anonymous visitors get the whole rendered page memoized under a
        // single fixed key; logged-in editors/admins (who might be
        // previewing something session-specific one day) always get a
        // fresh render. CacheControlFilter already refuses to let any
        // logged-in response carry a shared Cache-Control header, so this
        // mirrors that same anonymous/authenticated split at the app cache
        // layer.
        //
        // Using the global cache() helper (always autoloaded by CI4, see
        // system/Common.php) rather than `service('cache')` directly —
        // they're the same singleton, cache() is just the idiomatic
        // shorthand for it. CacheInterface::remember() (CI4 4.5+) computes
        // and stores the value on a miss and returns the cached value on a
        // hit, which is exactly the "cache the whole rendered response"
        // pattern called for here.
        if (session('user_id') !== null) {
            return $this->response->setBody($this->renderHomePage());
        }

        $ttl = config(CacheConfig::class)->ttls['category_page'];

        return $this->response->setBody(
            cache()->remember('home_page_html', $ttl, fn (): string => $this->renderHomePage())
        );
    }

    private function renderHomePage(): string
    {
        $categoryTree = model(CategoryModel::class)->getTree();

        $latestArticles = model(ArticleModel::class)
            ->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->limit(self::LATEST_LIMIT)
            ->find();

        $mediaMap        = $this->batchFeaturedMedia($latestArticles);
        $categorySlugMap = $this->batchCategorySlugs($latestArticles);

        /** @var Publisher $publisher */
        $publisher = config(Publisher::class);

        return view('front/home', [
            'categoryTree'     => $categoryTree,
            'latestArticles'   => $latestArticles,
            'mediaMap'         => $mediaMap,
            'categorySlugMap'  => $categorySlugMap,
            'pageTitle'        => $publisher->name,
            'metaDescription'  => null,
            'canonicalUrl'     => site_url(),
        ]);
    }
}
