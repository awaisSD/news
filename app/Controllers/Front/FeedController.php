<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Cache as CacheConfig;
use Config\Publisher;

class FeedController extends BaseController
{
    private const LIMIT = 20;

    public function all()
    {
        $ttl = config(CacheConfig::class)->ttls['rss_feed'];

        $xml = cache()->remember('rss_feed_all', $ttl, function (): string {
            return $this->buildFeed(null, 'Latest');
        });

        return $this->response->setContentType('application/rss+xml')->setBody($xml);
    }

    public function category(string $categorySlug)
    {
        $category = model(CategoryModel::class)
            ->where('slug', $categorySlug)
            ->where('is_active', 1)
            ->first();

        if ($category === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ttl      = config(CacheConfig::class)->ttls['rss_feed'];
        $cacheKey = 'rss_feed_category_' . $category->id;

        $xml = cache()->remember($cacheKey, $ttl, function () use ($category): string {
            return $this->buildFeed($category->id, $category->name);
        });

        return $this->response->setContentType('application/rss+xml')->setBody($xml);
    }

    private function buildFeed(?int $categoryId, string $channelTitle): string
    {
        // Selects category slug via join so the feed view can build each
        // item's canonical /{category}/{slug} link without an extra query
        // per row (Article entities happily carry extra joined columns as
        // dynamic attributes — see CodeIgniter\Entity\Entity::__set()).
        $builder = model(ArticleModel::class)
            ->select('articles.id, articles.headline, articles.slug, articles.excerpt, articles.published_at, categories.slug AS category_slug')
            ->join('categories', 'categories.id = articles.primary_category_id')
            ->where('articles.status', 'published');

        if ($categoryId !== null) {
            $builder->where('articles.primary_category_id', $categoryId);
        }

        $articles = $builder
            ->orderBy('articles.published_at', 'DESC')
            ->limit(self::LIMIT)
            ->find();

        /** @var Publisher $publisher */
        $publisher = config(Publisher::class);

        return view('front/feeds/rss', [
            'articles'     => $articles,
            'channelTitle' => $publisher->name . ' — ' . $channelTitle,
            'publisher'    => $publisher,
        ]);
    }
}
