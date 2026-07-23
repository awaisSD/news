<?php
/**
 * @var \App\Entities\Page $page
 */
?>
<?= $this->extend('front/layout/main') ?>

<?= $this->section('content') ?>
<article class="static-page">
    <h1><?= esc($page->title) ?></h1>

    <?php // $page->body_html is trusted, editor-authored CMS content — safe to output raw. ?>
    <div class="static-page__body"><?= $page->body_html ?></div>
</article>
<?= $this->endSection() ?>
