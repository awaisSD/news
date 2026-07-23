<?php
/**
 * Generic listing template shared by Front\CategoryController::index()
 * (paginated, single category — $category is set, $pager may be set) and
 * Front\TagController::show() (no pagination — $category and $pager are
 * null, $categorySlugMap resolves each card's link since a tag's articles
 * can span multiple categories).
 */
?>
<?= $this->extend('front/layout/main') ?>

<?= $this->section('content') ?>
<h1><?= esc($listTitle) ?></h1>

<?php if (! empty($category) && ! empty($category->description)): ?>
    <p class="category-description"><?= esc($category->description) ?></p>
<?php endif; ?>

<?php if (empty($articles)): ?>
    <p>No articles found.</p>
<?php else: ?>
    <section class="card-grid">
        <?php $this->setVar('cardCategorySlug', $category->slug ?? null); ?>
        <?php foreach ($articles as $item): ?>
            <?php $this->setVar('article', $item); ?>
            <?= $this->include('front/_article_card') ?>
        <?php endforeach; ?>
    </section>

    <?php if (! empty($pager)): ?>
        <?php
        $pageCount  = $pager->getPageCount();
        $current    = $currentPage ?? 1;
        ?>
        <?php if ($pageCount > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php if ($current > 1): ?>
                    <a rel="prev" href="<?= esc(site_url($baseUrl . ($current - 1 > 1 ? '/page/' . ($current - 1) : '')), 'attr') ?>">&laquo; Previous</a>
                <?php endif; ?>

                <span class="pagination__status">Page <?= esc((string) $current) ?> of <?= esc((string) $pageCount) ?></span>

                <?php if ($current < $pageCount): ?>
                    <a rel="next" href="<?= esc(site_url($baseUrl . '/page/' . ($current + 1)), 'attr') ?>">Next &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>
