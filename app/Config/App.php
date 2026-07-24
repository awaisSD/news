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

    /**
     * Read unconditionally by SiteURIFactory::getValidHost() when
     * validating the incoming request's Host header against $baseURL —
     * omitting this entirely (rather than just leaving it empty) throws.
     * Add extra accepted hostnames here only if this site is legitimately
     * reachable under more than one domain.
     *
     * @var list<string>
     */
    public array $allowedHostnames = [];

    public string $indexPage = '';

    public string $uriProtocol = 'REQUEST_URI';

    public string $defaultLocale = 'en';

    public bool $negotiateLocale = false;

    public array $supportedLocales = ['en'];

    public string $appTimezone = 'UTC';

    public string $charset = 'UTF-8';

    /**
     * Force HTTPS site-wide — required for News/Search eligibility.
     *
     * FALSE here on purpose when running behind an AWS ALB/CloudFront that
     * terminates TLS: the ALB only ever forwards plain HTTP to this app, so
     * CI4 can never see the original request as secure without proxyIPs
     * trust configured (see below) — redirecting HTTPS->HTTPS in a loop is
     * the classic symptom of leaving this true in that setup. Enforce the
     * redirect at the ALB's HTTP:80 listener instead (a built-in "redirect
     * to HTTPS" action, no app code involved). If this app is ever deployed
     * WITHOUT a TLS-terminating proxy in front (plain Apache+mod_ssl on the
     * box itself), set this back to true.
     */
    public bool $forceGlobalSecureRequests = false;

    /**
     * Trust X-Forwarded-For from the VPC's private IP ranges so
     * $request->getIPAddress() and audit_log entries record the real
     * visitor IP rather than the ALB's internal address. Narrow this to
     * your actual VPC CIDR if you know it, rather than all three RFC1918
     * ranges, once you've confirmed it.
     *
     * @var array<string, string>
     */
    public array $proxyIPs = [
        '10.0.0.0/8'     => 'X-Forwarded-For',
        '172.16.0.0/12'  => 'X-Forwarded-For',
        '192.168.0.0/16' => 'X-Forwarded-For',
    ];

    public string $permittedURIChars = 'a-z 0-9~%.:_\-';

    public bool $CSPEnabled = false;
}
