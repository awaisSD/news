<?php

namespace App\Controllers\Front\Concerns;

use App\Models\CategoryModel;

/**
 * Shared by every Front controller that renders article cards spanning
 * more than one category (Home's sitewide latest list, Author's article
 * list, Search results, Tag listings) — the article-card partial needs
 * each card's category slug to build its /{category}/{slug} link, and the
 * lightweight listing queries these controllers run don't join categories.
 * One whereIn() up front avoids an N+1 CategoryModel::find() per card.
 */
trait BatchesCategorySlugs
{
    /**
     * @param iterable<int, object{primary_category_id?: int|null}> $articles
     *
     * @return array<int, string> category id => slug
     */
    private function batchCategorySlugs(iterable $articles): array
    {
        $categoryIds = [];

        foreach ($articles as $article) {
            if (! empty($article->primary_category_id)) {
                $categoryIds[$article->primary_category_id] = true;
            }
        }

        if ($categoryIds === []) {
            return [];
        }

        $categories = model(CategoryModel::class)->whereIn('id', array_keys($categoryIds))->find();

        $map = [];
        foreach ($categories as $category) {
            $map[$category->id] = $category->slug;
        }

        return $map;
    }
}
