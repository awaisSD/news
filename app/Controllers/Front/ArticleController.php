<?php

namespace App\Controllers\Front;

use App\Controllers\BaseController;
use App\Entities\Article;
use App\Libraries\Seo\JsonLdBuilder;
use App\Models\ArticleCorrectionModel;
use App\Models\ArticleModel;
use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Models\TagModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Cache as CacheConfig;

class ArticleController extends BaseController
{
    public function show(string $categorySlug, string $slug)
    {
        $articleModel = model(ArticleModel::class);
        $article      = $articleModel->findPublishedByCategoryAndSlug($categorySlug, $slug);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Fire-and-forget view-count bump. This is a raw `view_count =
        // view_count + 1` SQL increment (BaseBuilder::increment(), not a
        // read-modify-write), so it's safe under concurrent requests and
        // deliberately runs on every request — cached or not, logged-in or
        // not — before any cache short-circuit below.
        $articleModel->where('id', $article->id)->increment('view_count');

        $isLoggedIn = session('user_id') !== null;
        $ttl        = config(CacheConfig::class)->ttls['article_page'];

        // Cache key includes updated_at_content's timestamp so a correction
        // (which touches that column — see App\Libraries\CorrectionsService)
        // naturally busts the cache by changing the key, with no explicit
        // invalidation call needed from the admin side.
        $contentVersion = $article->updated_at_content?->getTimestamp() ?? 0;
        $cacheKey       = "article_{$article->id}_{$contentVersion}";

        $render = fn (): string => $this->renderArticlePage($article, $categorySlug);

        if ($isLoggedIn) {
            return $this->response->setBody($render());
        }

        return $this->response->setBody(cache()->remember($cacheKey, $ttl, $render));
    }

    private function renderArticlePage(Article $article, string $categorySlug): string
    {
        $category = model(CategoryModel::class)->find($article->primary_category_id);
        $author   = model(UserModel::class)->find($article->author_id);

        $featuredMedia = $article->featured_media_id !== null
            ? model(MediaModel::class)->find($article->featured_media_id)
            : null;

        $corrections = model(ArticleCorrectionModel::class)->forArticle($article->id);
        $tags        = model(TagModel::class)->forArticle($article->id);

        $jsonLdBuilder = new JsonLdBuilder();

        // $category/$author are guaranteed non-null in practice —
        // findPublishedByCategoryAndSlug() joins articles to categories on
        // primary_category_id and only matches when categories.slug equals
        // $categorySlug, and author_id is a required NOT NULL FK — but we
        // guard defensively anyway rather than let JsonLdBuilder blow up on
        // a typed-argument mismatch if either row was hard-deleted out from
        // under a still-published article.
        $jsonLdArticle = ($category !== null && $author !== null)
            ? $jsonLdBuilder->forArticle($article, $category, $author, $featuredMedia)
            : null;

        $jsonLdBreadcrumb = $category !== null
            ? $jsonLdBuilder->forBreadcrumb([
                ['name' => 'Home', 'url' => site_url()],
                ['name' => $category->name, 'url' => site_url($category->slug)],
                ['name' => $article->headline, 'url' => site_url($category->slug . '/' . $article->slug)],
            ])
            : null;

        return view('front/article/show', [
            'article'          => $article,
            'category'         => $category,
            'author'           => $author,
            'featuredMedia'    => $featuredMedia,
            'corrections'      => $corrections,
            'jsonLdArticle'    => $jsonLdArticle,
            'jsonLdBreadcrumb' => $jsonLdBreadcrumb,
            'pageTitle'        => $article->meta_title ?: $article->headline,
            'metaDescription'  => $article->meta_description ?: $article->excerpt,
            'canonicalUrl'     => $article->canonical_url ?: site_url($categorySlug . '/' . $article->slug),
            'metaKeywords'     => implode(', ', array_map(static fn ($tag) => $tag->name, $tags)),
        ]);
    }
}
