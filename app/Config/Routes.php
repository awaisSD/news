<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 *
 * Route ordering matters: every static/reserved path below is registered
 * BEFORE the catch-all `(:segment)` category route and `(:segment)/(:segment)`
 * article route, so a category slug can never collide with a reserved word.
 * CategoryController::store()/update() must enforce that same reserved-word
 * blacklist (admin, feed, sitemap.xml, news-sitemap.xml, robots.txt, author,
 * search, page slugs) so the two layers of defense stay in sync.
 */
$routes->setDefaultNamespace('App\Controllers');

// ---------------------------------------------------------------------
// Front — home, discovery, syndication
// ---------------------------------------------------------------------
$routes->get('/', 'Front\HomeController::index');

$routes->get('sitemap.xml', 'Front\SitemapController::index');
$routes->get('sitemap-index.xml', 'Front\SitemapController::index');
$routes->get('sitemap-articles-(:num).xml', 'Front\SitemapController::articlesChunk/$1');
$routes->get('sitemap-pages.xml', 'Front\SitemapController::pages');
$routes->get('sitemap-categories.xml', 'Front\SitemapController::categories');
$routes->get('news-sitemap.xml', 'Front\SitemapController::news');
$routes->get('robots.txt', 'Front\SitemapController::robots');

$routes->get('feed', 'Front\FeedController::all');
$routes->get('feed/(:segment)', 'Front\FeedController::category/$1');

$routes->get('author/(:segment)', 'Front\AuthorController::show/$1');

$routes->get('search', 'Front\SearchController::index');

$routes->get('tag/(:segment)', 'Front\TagController::show/$1');

// Static, CMS-managed E-E-A-T / policy pages.
$staticPages = [
    'about-us',
    'contact-us',
    'editorial-policy',
    'corrections-policy',
    'privacy-policy',
    'terms-conditions',
];
foreach ($staticPages as $pageSlug) {
    $routes->get($pageSlug, 'Front\PageController::show/' . $pageSlug);
}
$routes->get('page/(:segment)', 'Front\PageController::show/$1');

// ---------------------------------------------------------------------
// Admin — role-gated, distinct approve permission enforced per-route
// ---------------------------------------------------------------------
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'adminauth'], static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::attemptLogin');
    $routes->get('logout', 'AuthController::logout');

    $routes->get('/', 'DashboardController::index');

    // Articles + editorial workflow
    $routes->get('articles', 'ArticleController::index');
    $routes->get('articles/create', 'ArticleController::create');
    $routes->post('articles', 'ArticleController::store');
    $routes->get('articles/(:num)/edit', 'ArticleController::edit/$1');
    $routes->post('articles/(:num)', 'ArticleController::update/$1');
    $routes->post('articles/(:num)/delete', 'ArticleController::delete/$1', ['filter' => 'role:admin']);

    $routes->post('articles/(:num)/submit-review', 'ArticleController::submitForReview/$1');
    $routes->post('articles/(:num)/request-changes', 'ArticleController::requestChanges/$1', ['filter' => 'role:editor,admin']);
    $routes->post('articles/(:num)/reject', 'ArticleController::reject/$1', ['filter' => 'role:editor,admin']);
    $routes->post('articles/(:num)/approve', 'ArticleController::approve/$1', ['filter' => ['role:editor,admin', 'canapprove']]);
    $routes->post('articles/(:num)/publish', 'ArticleController::publish/$1', ['filter' => ['role:editor,admin', 'canapprove']]);
    $routes->post('articles/(:num)/correct', 'ArticleController::recordCorrection/$1', ['filter' => 'role:editor,admin']);
    $routes->get('articles/(:num)/preview', 'ArticleController::preview/$1');

    $routes->get('review-queue', 'ReviewQueueController::index', ['filter' => 'role:editor,admin']);
    $routes->get('review-queue/(:num)', 'ReviewQueueController::show/$1', ['filter' => 'role:editor,admin']);

    $routes->get('revisions/(:num)', 'RevisionController::forArticle/$1');
    $routes->post('revisions/(:num)/restore', 'RevisionController::restore/$1', ['filter' => 'role:editor,admin']);

    // AI content pipeline
    // 'websafe' => true: plain HTML forms can only submit GET/POST, so this
    // makes update() reachable via POST to topics/(:num) and delete() via
    // POST to topics/(:num)/delete, instead of requiring real PUT/DELETE
    // verbs no <form> can send. Same reasoning applies to every other
    // resource() call below.
    $routes->resource('topics', ['controller' => 'TopicsController', 'websafe' => true]);
    $routes->post('topics/(:num)/suggest-angles', 'TopicsController::suggestAngles/$1');

    $routes->get('generate', 'GenerationController::create');
    $routes->post('generate', 'GenerationController::store');
    $routes->get('generate/jobs/(:num)', 'GenerationController::jobStatus/$1');

    $routes->get('articles/(:num)/style-pass', 'StylePassController::show/$1');
    $routes->post('articles/(:num)/style-pass', 'StylePassController::run/$1');
    $routes->post('articles/(:num)/style-pass/accept', 'StylePassController::accept/$1');
    $routes->post('articles/(:num)/style-pass/reject', 'StylePassController::reject/$1');

    $routes->get('image-jobs', 'ImageJobsController::index');
    $routes->post('articles/(:num)/image-jobs', 'ImageJobsController::request/$1');
    $routes->post('image-jobs/(:num)/approve', 'ImageJobsController::approve/$1');
    $routes->post('image-jobs/(:num)/reject', 'ImageJobsController::reject/$1');
    $routes->post('image-jobs/(:num)/regenerate', 'ImageJobsController::regenerate/$1');

    // Taxonomy, media, pages, users — admin/editor scoped
    $routes->resource('categories', ['controller' => 'CategoryController', 'filter' => 'role:admin', 'websafe' => true]);
    $routes->resource('tags', ['controller' => 'TagController', 'websafe' => true]);
    $routes->resource('media', ['controller' => 'MediaController', 'websafe' => true]);
    $routes->resource('pages', ['controller' => 'PageController', 'websafe' => true]);
    $routes->resource('users', ['controller' => 'UserController', 'filter' => 'role:admin', 'websafe' => true]);
    $routes->resource('redirects', ['controller' => 'RedirectController', 'filter' => 'role:admin', 'websafe' => true]);

    $routes->get('settings/ai', 'AiSettingsController::index', ['filter' => 'role:admin']);
    $routes->post('settings/ai', 'AiSettingsController::update', ['filter' => 'role:admin']);

    $routes->get('audit-log', 'AuditLogController::index', ['filter' => 'role:admin']);
});

// ---------------------------------------------------------------------
// Front — category & article (flat /{category}/{slug} permanent URLs).
// MUST stay last: catches any remaining single/double segment path.
// ---------------------------------------------------------------------
$routes->get('(:segment)/page/(:num)', 'Front\CategoryController::index/$1/$2');
$routes->get('(:segment)/(:segment)', 'Front\ArticleController::show/$1/$2');
$routes->get('(:segment)', 'Front\CategoryController::index/$1');
