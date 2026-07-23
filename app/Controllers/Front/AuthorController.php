<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Controllers\Front\Concerns\BatchesCategorySlugs;
use App\Controllers\Front\Concerns\BatchesFeaturedMedia;
use App\Models\ArticleModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Cache as CacheConfig;

class AuthorController extends BaseController
{
    use BatchesFeaturedMedia;
    use BatchesCategorySlugs;

    private const RECENT_LIMIT = 20;

    /**
     * The `users` table has no dedicated slug/handle column in the finalized
     * schema (checked App\Entities\User and the 2024-01-01-000001 migration:
     * id, uuid, name, email, password_hash, role, bio, credentials,
     * avatar_media_id, twitter_handle, linkedin_url, is_active,
     * last_login_at, created_at, updated_at). App\Libraries\Seo\JsonLdBuilder
     * already links author byline URLs as `author/{id}` for the same reason
     * (see its own TODO comment), so this controller treats the route
     * segment as a numeric user id to match. A future migration adding a
     * friendly author slug should update both this controller and
     * JsonLdBuilder::forArticle() together.
     */
    public function show(string $authorId)
    {
        if (! ctype_digit($authorId)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $author = model(UserModel::class)->find((int) $authorId);

        if ($author === null || ! in_array($author->role, ['writer', 'editor', 'admin'], true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ttl        = config(CacheConfig::class)->ttls['author_page'];
        $cacheKey   = "author_{$author->id}";
        $isLoggedIn = session('user_id') !== null;

        $render = function () use ($author): string {
            $articles = model(ArticleModel::class)
                ->select(['id', 'headline', 'slug', 'excerpt', 'featured_media_id', 'published_at', 'primary_category_id'])
                ->where('author_id', $author->id)
                ->where('status', 'published')
                ->orderBy('published_at', 'DESC')
                ->limit(self::RECENT_LIMIT)
                ->find();

            $mediaMap        = $this->batchFeaturedMedia($articles);
            $categorySlugMap = $this->batchCategorySlugs($articles);

            return view('front/author/show', [
                'author'          => $author,
                'articles'        => $articles,
                'mediaMap'        => $mediaMap,
                'categorySlugMap' => $categorySlugMap,
                'pageTitle'       => $author->name,
                'metaDescription' => $author->bio,
                'canonicalUrl'    => site_url('author/' . $author->id),
            ]);
        };

        if ($isLoggedIn) {
            return $this->response->setBody($render());
        }

        return $this->response->setBody(cache()->remember($cacheKey, $ttl, $render));
    }
}
