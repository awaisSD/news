<?php
/**
 * Fetches the category tree directly rather than requiring every single
 * Front controller to thread it through as view data — CategoryModel::getTree()
 * is already cached for 3600s (see Config\Cache::$ttls['category_tree']), so
 * this costs nothing extra beyond a cache read on every request.
 */
$publisher     = config(\Config\Publisher::class);
$navCategories = model(\App\Models\CategoryModel::class)->getTree();
?>
<header class="site-header">
    <div class="site-header__bar">
        <a class="site-header__brand" href="<?= esc(site_url(), 'attr') ?>"><?= esc($publisher->name) ?></a>

        <form class="site-header__search" action="<?= esc(site_url('search'), 'attr') ?>" method="get" role="search">
            <label class="sr-only" for="site-search-q">Search</label>
            <input type="search" id="site-search-q" name="q" placeholder="Search articles&hellip;" value="<?= esc($query ?? '') ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (! empty($navCategories)): ?>
        <nav class="site-nav" aria-label="Primary">
            <ul class="site-nav__list">
                <?php foreach ($navCategories as $navCategory): ?>
                    <li class="site-nav__item">
                        <a href="<?= esc(site_url($navCategory->slug), 'attr') ?>"><?= esc($navCategory->name) ?></a>
                        <?php $children = $navCategory->getChildren(); ?>
                        <?php if (! empty($children)): ?>
                            <ul class="site-nav__sublist">
                                <?php foreach ($children as $child): ?>
                                    <li><a href="<?= esc(site_url($child->slug), 'attr') ?>"><?= esc($child->name) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    <?php endif; ?>
</header>
