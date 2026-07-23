<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Session handling. Starts file-based for a single Apache node.
 *
 * MANDATORY once load-balanced across more than one app server: switch
 * $driver to CodeIgniter\Session\Handlers\RedisHandler and $savePath to
 * the shared Redis DSN below — otherwise editors get logged out or see
 * inconsistent admin state depending on which node they hit.
 */
class Session extends BaseConfig
{
    public string $driver = 'CodeIgniter\Session\Handlers\FileHandler';

    public string $cookieName = 'newsweb_session';

    public int $expiration = 7200;

    public ?string $savePath = null;

    public bool $matchIP = false;

    public int $timeToUpdate = 300;

    public bool $regenerateDestroy = false;

    public function __construct()
    {
        parent::__construct();

        $this->driver   = env('session.driver', $this->driver);
        $this->savePath = env('session.savePath', $this->savePath) ?: WRITEPATH . 'session';
    }
}
