<?php
/**
 * @var array|null $jsonLdArticle    from JsonLdBuilder::forArticle()
 * @var array|null $jsonLdBreadcrumb from JsonLdBuilder::forBreadcrumb()
 *
 * render_jsonld() already produces safely JSON-encoded <script> output —
 * nothing here needs esc().
 */
?>
<?php if (! empty($jsonLdArticle)): ?>
<?= render_jsonld($jsonLdArticle) ?>

<?php endif; ?>
<?php if (! empty($jsonLdBreadcrumb)): ?>
<?= render_jsonld($jsonLdBreadcrumb) ?>

<?php endif; ?>
