<?php
/**
 * @var \App\Entities\Article        $article
 * @var \App\Entities\Category|null  $category
 * @var \App\Entities\User|null      $author
 * @var \App\Entities\Media|null     $featuredMedia
 * @var \App\Entities\ArticleCorrection[] $corrections
 */
?>
<?= $this->extend('front/layout/main') ?>

<?= $this->section('content') ?>
<?= $this->include('front/article/_jsonld') ?>

<article class="article">
    <?php if ($category !== null): ?>
        <p class="article__eyebrow">
            <a href="<?= esc(site_url($category->slug), 'attr') ?>"><?= esc($category->name) ?></a>
        </p>
    <?php endif; ?>

    <h1 class="article__headline"><?= esc($article->headline) ?></h1>

    <?php if (! empty($article->subheadline)): ?>
        <p class="article__subheadline"><?= esc($article->subheadline) ?></p>
    <?php endif; ?>

    <p class="article__byline">
        <?php if ($author !== null): ?>
            By <a href="<?= esc(site_url('author/' . $author->id), 'attr') ?>"><?= esc($author->name) ?></a>
        <?php endif; ?>
        <?php if (! empty($article->published_at)): ?>
            <time datetime="<?= esc($article->published_at->format(DATE_ATOM), 'attr') ?>">
                Published <?= esc($article->published_at->format('M j, Y g:i A')) ?>
            </time>
        <?php endif; ?>
        <?php if (! empty($article->updated_at_content) && ! empty($article->published_at) && $article->updated_at_content->getTimestamp() !== $article->published_at->getTimestamp()): ?>
            <time datetime="<?= esc($article->updated_at_content->format(DATE_ATOM), 'attr') ?>">
                &middot; Updated <?= esc($article->updated_at_content->format('M j, Y g:i A')) ?>
            </time>
        <?php endif; ?>
    </p>

    <?php if ($featuredMedia !== null): ?>
        <?php // Above-the-fold featured image: NOT lazy-loaded, per CWV guidance. ?>
        <figure class="article__figure">
            <img
                src="<?= esc(media_url($featuredMedia, 1200), 'attr') ?>"
                srcset="<?= esc(media_srcset($featuredMedia), 'attr') ?>"
                sizes="100vw"
                width="<?= esc((string) $featuredMedia->width) ?>"
                height="<?= esc((string) $featuredMedia->height) ?>"
                alt="<?= esc(image_alt($featuredMedia)) ?>"
                decoding="async"
            >
            <?php if (! empty($featuredMedia->caption) || ! empty($featuredMedia->credit)): ?>
                <figcaption>
                    <?= esc($featuredMedia->caption ?? '') ?>
                    <?php if (! empty($featuredMedia->credit)): ?>
                        <span class="article__figure-credit"><?= esc($featuredMedia->credit) ?></span>
                    <?php endif; ?>
                </figcaption>
            <?php endif; ?>
        </figure>
    <?php endif; ?>

    <?php // $article->body_html is trusted, sanitized rich HTML produced by the editorial pipeline — safe to output raw. ?>
    <div class="article__body"><?= $article->body_html ?></div>

    <?php if (! empty($corrections)): ?>
        <?= $this->include('front/article/_corrections') ?>
    <?php endif; ?>

    <?php if ($author !== null): ?>
        <section class="article__author-box" aria-label="About the author">
            <h2>About the author</h2>
            <p class="article__author-name"><?= esc($author->name) ?></p>
            <?php if (! empty($author->getDisplayCredentials())): ?>
                <p class="article__author-credentials"><?= esc($author->getDisplayCredentials()) ?></p>
            <?php endif; ?>
            <?php if (! empty($author->bio)): ?>
                <p class="article__author-bio"><?= esc($author->bio) ?></p>
            <?php endif; ?>
            <p><a href="<?= esc(site_url('author/' . $author->id), 'attr') ?>">View all articles by <?= esc($author->name) ?></a></p>
        </section>
    <?php endif; ?>
</article>
<?= $this->endSection() ?>
