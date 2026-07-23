<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Controllers\Front\Concerns\BatchesCategorySlugs;
use App\Controllers\Front\Concerns\BatchesFeaturedMedia;
use App\Models\ArticleModel;

class SearchController extends BaseController
{
    use BatchesFeaturedMedia;
    use BatchesCategorySlugs;

    private const LIMIT = 20;

    public function index()
    {
        $query = trim((string) $this->request->getGet('q'));

        if ($query === '') {
            return view('front/search', [
                'query'           => '',
                'results'         => [],
                'mediaMap'        => [],
                'categorySlugMap' => [],
                'pageTitle'       => 'Search',
                'metaDescription' => null,
                'canonicalUrl'    => site_url('search'),
            ]);
        }

        // Simple LIKE-based fallback: safe to get right without being able
        // to test a raw MATCH...AGAINST query string locally. The
        // `ft_headline_excerpt` FULLTEXT index (see the articles migration)
        // already exists, so swapping this for
        // `MATCH(headline, excerpt) AGAINST(? IN NATURAL LANGUAGE MODE)`
        // via a raw/binding query for real relevance ranking is a
        // straightforward future upgrade — it just isn't done here.
        $results = model(ArticleModel::class)
            ->where('status', 'published')
            ->select('id, headline, slug, excerpt, primary_category_id, published_at, featured_media_id, author_id')
            ->like('headline', $query)
            ->orLike('excerpt', $query)
            ->orderBy('published_at', 'DESC')
            ->limit(self::LIMIT)
            ->find();

        $mediaMap        = $this->batchFeaturedMedia($results);
        $categorySlugMap = $this->batchCategorySlugs($results);

        return view('front/search', [
            'query'           => $query,
            'results'         => $results,
            'mediaMap'        => $mediaMap,
            'categorySlugMap' => $categorySlugMap,
            'pageTitle'       => 'Search: ' . $query,
            'metaDescription' => null,
            'canonicalUrl'    => site_url('search'),
        ]);
    }
}
