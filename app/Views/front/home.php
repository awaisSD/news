<?= $this->extend('front/layout/main') ?>

<?= $this->section('content') ?>
<h1 class="visually-hidden">Latest News</h1>

<?php if (empty($latestArticles)): ?>
    <p>No articles have been published yet.</p>
<?php else: ?>
    <?php $hero = array_shift($latestArticles); ?>

    <?php
    $heroMedia   = ! empty($hero->featured_media_id) ? ($mediaMap[$hero->featured_media_id] ?? null) : null;
    $heroCatSlug = $categorySlugMap[$hero->primary_category_id] ?? null;
    $heroHref    = $heroCatSlug !== null ? site_url($heroCatSlug . '/' . $hero->slug) : null;
    ?>
    <section class="hero" aria-label="Top story">
        <?php if ($heroMedia !== null): ?>
            <?php // Above-the-fold hero image: NOT lazy-loaded, per CWV guidance. ?>
            <?php if ($heroHref !== null): ?><a href="<?= esc($heroHref, 'attr') ?>"><?php endif; ?>
            <img
                src="<?= esc(media_url($heroMedia, 1200), 'attr') ?>"
                srcset="<?= esc(media_srcset($heroMedia), 'attr') ?>"
                sizes="100vw"
                width="<?= esc((string) $heroMedia->width) ?>"
                height="<?= esc((string) $heroMedia->height) ?>"
                alt="<?= esc(image_alt($heroMedia)) ?>"
                decoding="async"
            >
            <?php if ($heroHref !== null): ?></a><?php endif; ?>
        <?php endif; ?>

        <h2 class="hero__title">
            <?php if ($heroHref !== null): ?>
                <a href="<?= esc($heroHref, 'attr') ?>"><?= esc($hero->headline) ?></a>
            <?php else: ?>
                <?= esc($hero->headline) ?>
            <?php endif; ?>
        </h2>
        <?php if (! empty($hero->excerpt)): ?><p class="hero__excerpt"><?= esc($hero->excerpt) ?></p><?php endif; ?>
    </section>

    <?php if (! empty($latestArticles)): ?>
        <section class="card-grid" aria-label="More stories">
            <?php foreach ($latestArticles as $item): ?>
                <?php $this->setVar('article', $item); ?>
                <?= $this->include('front/_article_card') ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
