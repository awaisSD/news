<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Controllers\Front\Concerns\BatchesFeaturedMedia;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Cache as CacheConfig;

class CategoryController extends BaseController
{
    use BatchesFeaturedMedia;

    private const PER_PAGE = 20;

    /**
     * List-view column set — mirrors ArticleModel::LIST_COLUMNS (private
     * there) so this controller never pulls body_html for a listing page.
     */
    private const LIST_COLUMNS = [
        'id',
        'headline',
        'slug',
        'excerpt',
        'featured_media_id',
        'published_at',
        'author_id',
        'primary_category_id',
    ];

    public function index(string $categorySlug, int $page = 1)
    {
        $categoryModel = model(CategoryModel::class);

        // Defense in depth: Routes.php registers every reserved/static path
        // (admin, feed, tag, page, the six CMS pages, sitemaps, etc.) ahead
        // of this catch-all `(:segment)` route, and CategoryModel refuses to
        // ever save a category with a reserved slug — so in normal operation
        // this branch is unreachable. It's here purely so a future routing
        // regression 404s instead of silently rendering a bogus category.
        if ($categoryModel->isSlugReserved($categorySlug)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $category = $categoryModel
            ->where('slug', $categorySlug)
            ->where('is_active', 1)
            ->first();

        if ($category === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $page = max(1, $page);

        $isLoggedIn = session('user_id') !== null;
        $ttl        = config(CacheConfig::class)->ttls['category_page'];
        $cacheKey   = "category_{$category->id}_page_{$page}";

        $render = fn (): string => $this->renderCategoryPage($category, $page);

        if ($isLoggedIn) {
            return $this->response->setBody($render());
        }

        return $this->response->setBody(cache()->remember($cacheKey, $ttl, $render));
    }

    private function renderCategoryPage(\App\Entities\Category $category, int $page): string
    {
        $articleModel = model(ArticleModel::class);

        // Simple OFFSET-based pagination via CI4's built-in Model::paginate()
        // (Pager), keyed off the page NUMBER in the URL
        // ((:segment)/page/(:num)). ArticleModel::listPublishedForCategory()
        // exposes true keyset (seek) pagination for when this needs to
        // scale, but reconstructing the correct cursor purely from a page
        // number (walking/discarding (page-1)*PER_PAGE rows) without being
        // able to run/test it locally risks a subtle off-by-one bug. The
        // Pager approach is simple, correct, and idiomatic CI4 — revisit and
        // switch to keyset once article volume per category is large enough
        // that OFFSET pagination itself becomes the bottleneck.
        $articles = $articleModel
            ->select(self::LIST_COLUMNS)
            ->where('primary_category_id', $category->id)
            ->where('status', 'published')
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(self::PER_PAGE, 'default', $page);

        $pager    = $articleModel->pager;
        $mediaMap = $this->batchFeaturedMedia($articles);

        return view('front/category/index', [
            'category'    => $category,
            'listTitle'   => $category->name,
            'articles'    => $articles,
            'mediaMap'    => $mediaMap,
            'pager'       => $pager,
            'currentPage' => $page,
            // Base path used by the view to build page links itself
            // (site_url("{$baseUrl}/page/{$n}")) rather than trusting
            // $pager->links(), since the default Pager renderer builds
            // query-string URLs (?page=N) that don't match this app's clean
            // /{category}/page/{n} route.
            'baseUrl'     => $category->slug,
            'pageTitle'   => $category->name,
            'metaDescription' => $category->description,
            'canonicalUrl'    => site_url($category->slug . ($page > 1 ? '/page/' . $page : '')),
        ]);
    }
}
