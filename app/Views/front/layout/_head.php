<?php
/**
 * @var string|null $pageTitle
 * @var string|null $metaDescription
 * @var string|null $canonicalUrl
 * @var array<int, string>|null $jsonLd pre-rendered render_jsonld() script tags
 *      (e.g. the sitewide Organization node on the home/static pages).
 *      Article pages instead embed their NewsArticle/BreadcrumbList JSON-LD
 *      inline in the content region via front/article/_jsonld.php.
 */
$publisher = config(\Config\SiteIdentity::class);
$title     = $pageTitle ?? $publisher->name;
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?><?= $title !== $publisher->name ? ' | ' . esc($publisher->name) : '' ?></title>
<meta name="description" content="<?= meta_description_or_default($metaDescription ?? null, $publisher->name) ?>">
<link rel="canonical" href="<?= esc($canonicalUrl ?? current_url(), 'attr') ?>">
<link rel="alternate" type="application/rss+xml" title="<?= esc($publisher->name) ?> — Latest" href="<?= esc(site_url('feed'), 'attr') ?>">
<link rel="stylesheet" href="<?= esc(base_url('assets/site.css'), 'attr') ?>">
<link rel="icon" type="image/png" href="<?= esc(base_url('assets/tech-acts-transparent.png'), 'attr') ?>">
<link rel="apple-touch-icon" href="<?= esc(base_url('assets/tech-acts-news.png'), 'attr') ?>">
<?php if (! empty($jsonLd)): ?>
    <?php foreach ($jsonLd as $block): ?>
<?= $block ?>

    <?php endforeach; ?>
<?php endif; ?>
