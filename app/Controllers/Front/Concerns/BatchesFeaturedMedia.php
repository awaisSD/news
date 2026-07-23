<?php

namespace App\Controllers\Front\Concerns;

use App\Models\MediaModel;

/**
 * Shared by every Front controller that renders a grid/list of article
 * cards (Home, Category, Author, Tag). Listing queries deliberately select
 * only lightweight columns (see ArticleModel's LIST_COLUMNS) and don't join
 * media, so without this a naive view would do one MediaModel::find() per
 * card — an N+1 query per page. Fetching every distinct featured_media_id
 * in a single whereIn() up front keeps listing pages to a constant number
 * of queries regardless of how many cards they render.
 */
trait BatchesFeaturedMedia
{
    /**
     * @param iterable<int, object{featured_media_id?: int|null}> $articles
     *
     * @return array<int, \App\Entities\Media> keyed by media id
     */
    private function batchFeaturedMedia(iterable $articles): array
    {
        $mediaIds = [];

        foreach ($articles as $article) {
            if (! empty($article->featured_media_id)) {
                $mediaIds[$article->featured_media_id] = true;
            }
        }

        if ($mediaIds === []) {
            return [];
        }

        $media = model(MediaModel::class)->whereIn('id', array_keys($mediaIds))->find();

        $map = [];
        foreach ($media as $item) {
            $map[$item->id] = $item;
        }

        return $map;
    }
}
