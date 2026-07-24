<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * Canonical site URL. Must exactly match the domain submitted to
     * Google Publisher Center — see Config\SiteIdentity for the identity
     * fields (name/logo) that must stay consistent with this URL.
     */
    public string $baseURL = '';

    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $defaultLocale = 'en';

    public bool $negotiateLocale = false;

    public array $supportedLocales = ['en'];

    public string $appTimezone = 'UTC';

    public string $charset = 'UTF-8';

    /**
     * Force HTTPS site-wide — required for News/Search eligibility and
     * enforced additionally at the Apache vhost level and by
     * Filters\ForceHttpsFilter as defense in depth.
     */
    public bool $forceGlobalSecureRequests = true;

    public array $proxyIPs = [];

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public bool $CSPEnabled = false;
}
