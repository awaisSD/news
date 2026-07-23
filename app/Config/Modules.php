<?php

namespace Config;

use CodeIgniter\Modules\Modules as BaseModules;

/**
 * Modules Configuration.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 *       and does not extend BaseConfig.
 */
class Modules extends BaseModules
{
    /**
     * If true, then auto-discovery will happen across all elements listed in
     * $aliases below. If false, no auto-discovery will happen at all,
     * giving a slight performance boost.
     */
    public bool $enabled = true;

    /**
     * If true, then auto-discovery will happen across all namespaces loaded
     * by Composer, as well as the namespaces configured locally.
     */
    public bool $discoverInComposer = true;

    /**
     * The Composer package list for Auto-Discovery. Left empty — none of
     * this project's dependencies (guzzlehttp/guzzle, intervention/image)
     * register CodeIgniter module discovery, so there is nothing to
     * include/exclude here.
     *
     * @var array{only?: list<string>, exclude?: list<string>}
     */
    public array $composerPackages = [];

    /**
     * Aliases list of all discovery classes that will be active and used
     * during the current application request.
     *
     * @var list<string>
     */
    public array $aliases = [
        'events',
        'filters',
        'registrars',
        'routes',
        'services',
    ];
}
