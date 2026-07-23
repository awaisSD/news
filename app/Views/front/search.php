<?php
/**
 * @var string $query
 * @var array  $results
 * @var array<int, \App\Entities\Media> $mediaMap
 * @var array<int, string> $categorySlugMap
 */
?>
<?= $this->extend('front/layout/main') ?>

<?= $this->section('content') ?>
<h1>Search</h1>

<form class="search-form" action="<?= esc(site_url('search'), 'attr') ?>" method="get" role="search">
    <label for="search-page-q">Search articles</label>
    <input type="search" id="search-page-q" name="q" value="<?= esc($query) ?>" placeholder="Type a keyword&hellip;">
    <button type="submit">Search</button>
</form>

<?php if ($query === ''): ?>
    <p>Enter a keyword above to search headlines and article summaries.</p>
<?php elseif (empty($results)): ?>
    <p>No results for &ldquo;<?= esc($query) ?>&rdquo;.</p>
<?php else: ?>
    <p><?= esc((string) count($results)) ?> result<?= count($results) === 1 ? '' : 's' ?> for &ldquo;<?= esc($query) ?>&rdquo;</p>
    <section class="card-grid">
        <?php foreach ($results as $item): ?>
            <?php $this->setVar('article', $item); ?>
            <?= $this->include('front/_article_card') ?>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?= $this->endSection() ?>
