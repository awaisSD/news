<?php
/**
 * Shared article-card partial used by home.php, category/index.php,
 * author/show.php and search.php — kept as one partial rather than
 * duplicated per-page markup so the listing card design only lives in one
 * place.
 *
 * Expected variables (set a fresh copy of $article before each include —
 * see call sites):
 * @var object                          $article          Article entity, or a
 *      plain stdClass row from a raw query (Tag/Feed-style joins) — either
 *      works since both support -> property access.
 * @var array<int, \App\Entities\Media> $mediaMap         keyed by media id
 * @var array<int, string>              $categorySlugMap  keyed by category id,
 *      used when $cardCategorySlug is not given (i.e. cards span categories)
 * @var string|null                     $cardCategorySlug  when set, every
 *      card on the page links through this one category slug (category
 *      listing pages, where every card already belongs to $category)
 */
$media       = ! empty($article->featured_media_id) ? ($mediaMap[$article->featured_media_id] ?? null) : null;
$catSlug     = $cardCategorySlug ?? ($categorySlugMap[$article->primary_category_id] ?? null);
$articleHref = $catSlug !== null ? site_url($catSlug . '/' . $article->slug) : null;

$publishedAt = $article->published_at ?? null;
if (is_string($publishedAt) && $publishedAt !== '') {
    $publishedAt = new DateTimeImmutable($publishedAt);
}
?>
<article class="card">
    <?php if ($media !== null): ?>
        <?php if ($articleHref !== null): ?><a class="card__media" href="<?= esc($articleHref, 'attr') ?>"><?php endif; ?>
        <img
            src="<?= esc(media_url($media, 400), 'attr') ?>"
            srcset="<?= esc(media_srcset($media), 'attr') ?>"
            sizes="(max-width: 640px) 100vw, 400px"
            width="<?= esc((string) $media->width) ?>"
            height="<?= esc((string) $media->height) ?>"
            alt="<?= esc(image_alt($media)) ?>"
            loading="lazy" decoding="async"
        >
        <?php if ($articleHref !== null): ?></a><?php endif; ?>
    <?php endif; ?>

    <h3 class="card__title">
        <?php if ($articleHref !== null): ?>
            <a href="<?= esc($articleHref, 'attr') ?>"><?= esc($article->headline) ?></a>
        <?php else: ?>
            <?= esc($article->headline) ?>
        <?php endif; ?>
    </h3>

    <?php if (! empty($article->excerpt)): ?>
        <p class="card__excerpt"><?= esc($article->excerpt) ?></p>
    <?php endif; ?>

    <?php if ($publishedAt instanceof DateTimeInterface): ?>
        <time class="card__date" datetime="<?= esc($publishedAt->format(DATE_ATOM), 'attr') ?>"><?= esc($publishedAt->format('M j, Y')) ?></time>
    <?php endif; ?>
</article>
