<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Controllers\Front\Concerns\BatchesCategorySlugs;
use App\Controllers\Front\Concerns\BatchesFeaturedMedia;
use App\Models\TagModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TagController extends BaseController
{
    use BatchesFeaturedMedia;
    use BatchesCategorySlugs;

    private const LIMIT = 20;

    /**
     * Reuses front/category/index.php (the same generic listing template
     * CategoryController renders) rather than a dedicated tag view — the
     * view already accepts a nullable $category, a $listTitle, $articles,
     * and an optional $pager, which is exactly what a tag listing needs
     * (tag pages don't paginate here, so $pager is simply omitted).
     * Duplicating a whole listing template for tags would just be
     * near-identical markup drifting out of sync over time.
     */
    public function show(string $tagSlug)
    {
        $tag = model(TagModel::class)->where('slug', $tagSlug)->first();

        if ($tag === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $db = db_connect();

        // Raw pivot join: article_tags has no Model of its own (per the
        // migration/model contract, tags/tag pivots are managed straight
        // through ArticleModel::attachTags() and simple query-builder
        // reads), and we deliberately select only listing-safe columns so
        // this never pulls articles.body_html over the wire.
        $rows = $db->table('article_tags')
            ->select('articles.id, articles.headline, articles.slug, articles.excerpt, articles.featured_media_id, articles.published_at, articles.author_id, articles.primary_category_id')
            ->join('articles', 'articles.id = article_tags.article_id')
            ->where('article_tags.tag_id', $tag->id)
            ->where('articles.status', 'published')
            ->orderBy('articles.published_at', 'DESC')
            ->limit(self::LIMIT)
            ->get()
            ->getResult();

        $mediaMap        = $this->batchFeaturedMedia($rows);
        $categorySlugMap = $this->batchCategorySlugs($rows);

        return view('front/category/index', [
            'category'        => null,
            'listTitle'       => 'Tag: ' . $tag->name,
            'articles'        => $rows,
            'mediaMap'        => $mediaMap,
            'categorySlugMap' => $categorySlugMap,
            'pager'           => null,
            'currentPage'     => 1,
            'baseUrl'         => 'tag/' . $tag->slug,
            'pageTitle'       => 'Tag: ' . $tag->name,
            'metaDescription' => null,
            'canonicalUrl'    => site_url('tag/' . $tag->slug),
        ]);
    }
}
