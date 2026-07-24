<?php
/**
 * RSS 2.0 feed. Deliberately NOT extending the HTML layout — this is raw XML
 * returned with an application/rss+xml content type (see FeedController).
 *
 * @var array                $articles     Article entities carrying a
 *      dynamic `category_slug` attribute from the join in
 *      FeedController::buildFeed() (see App\Entities\Article — extra
 *      selected columns become dynamic attributes via Entity::__set()).
 * @var string                $channelTitle
 * @var \Config\SiteIdentity  $publisher
 */
$buildDate = (new DateTimeImmutable())->format(DATE_RSS);
?>
<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
<title><?= esc($channelTitle) ?></title>
<link><?= esc($publisher->url) ?></link>
<atom:link href="<?= esc(current_url()) ?>" rel="self" type="application/rss+xml" />
<description><?= esc($publisher->name) ?></description>
<language><?= esc($publisher->newsLanguage) ?></language>
<lastBuildDate><?= esc($buildDate) ?></lastBuildDate>
<?php foreach ($articles as $article): ?>
    <?php
    $categorySlug = $article->category_slug ?? null;
    $link         = $categorySlug !== null ? site_url($categorySlug . '/' . $article->slug) : site_url();
    $pubDate      = ! empty($article->published_at) ? $article->published_at->format(DATE_RSS) : null;
    ?>
<item>
<title><?= esc($article->headline) ?></title>
<link><?= esc($link) ?></link>
<guid isPermaLink="true"><?= esc($link) ?></guid>
<?php if (! empty($article->excerpt)): ?>
<description><?= esc($article->excerpt) ?></description>
<?php endif; ?>
<?php if ($pubDate !== null): ?>
<pubDate><?= esc($pubDate) ?></pubDate>
<?php endif; ?>
</item>
<?php endforeach; ?>
</channel>
</rss>
