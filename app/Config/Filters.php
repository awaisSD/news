<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * @var array<string, class-string|list<class-string>>
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'forcehttps'    => ForceHTTPS::class,
        'cors'          => Cors::class,

        // App-specific filters — classes authored in app/Filters/*.
        'adminauth'     => \App\Filters\AdminAuthFilter::class,
        'role'          => \App\Filters\RoleFilter::class,
        'canapprove'    => \App\Filters\RequireApprovalPermissionFilter::class,
        'publishedonly' => \App\Filters\PublishedOnlyFilter::class,
        'cachecontrol'  => \App\Filters\CacheControlFilter::class,
        'redirects'     => \App\Filters\RedirectFilter::class,
    ];

    /**
     * Filters run on every request, in the order listed.
     * 'redirects' runs before routing 404s so old slugs 301 correctly
     * (see the redirects table / RedirectFilter). 'cachecontrol' runs
     * after every response to set Cache-Control/ETag headers uniformly.
     *
     * @var array<string, list<string>>
     */
    public array $globals = [
        'before' => [
            'forcehttps',
            'redirects',
        ],
        'after' => [
            'secureheaders',
            'cachecontrol',
        ],
    ];

    /**
     * @var array<string, array<string, list<string>>>
     */
    public array $methods = [];

    /**
     * Route-group-scoped filters. Fine-grained role checks (which roles
     * may hit which admin action) are attached per-route in Routes.php
     * via 'filter' => 'role:editor,admin', not duplicated here.
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'adminauth' => [
            'before' => ['admin/*'],
        ],
    ];
}
