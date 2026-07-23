<?php
/**
 * @var \App\Entities\User $author
 * @var array              $articles
 * @var array<int, \App\Entities\Media> $mediaMap
 * @var array<int, string> $categorySlugMap
 */
?>
<?= $this->extend('front/layout/main') ?>

<?= $this->section('content') ?>
<section class="author-bio">
    <h1><?= esc($author->name) ?></h1>

    <?php if (! empty($author->getDisplayCredentials())): ?>
        <p class="author-bio__credentials"><?= esc($author->getDisplayCredentials()) ?></p>
    <?php endif; ?>

    <?php if (! empty($author->bio)): ?>
        <p class="author-bio__text"><?= esc($author->bio) ?></p>
    <?php endif; ?>

    <?php if (! empty($author->twitter_handle) || ! empty($author->linkedin_url)): ?>
        <p class="author-bio__social">
            <?php if (! empty($author->twitter_handle)): ?>
                <a href="<?= esc('https://twitter.com/' . ltrim($author->twitter_handle, '@'), 'attr') ?>" rel="me noopener" target="_blank">@<?= esc(ltrim($author->twitter_handle, '@')) ?></a>
            <?php endif; ?>
            <?php if (! empty($author->linkedin_url)): ?>
                <a href="<?= esc($author->linkedin_url, 'attr') ?>" rel="me noopener" target="_blank">LinkedIn</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</section>

<h2>Recent articles</h2>
<?php if (empty($articles)): ?>
    <p>This author has not published any articles yet.</p>
<?php else: ?>
    <section class="card-grid">
        <?php foreach ($articles as $item): ?>
            <?php $this->setVar('article', $item); ?>
            <?= $this->include('front/_article_card') ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?= $this->endSection() ?>
