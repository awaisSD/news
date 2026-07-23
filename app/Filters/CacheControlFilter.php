<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Global `after` filter (see Config\Filters::$filters['after'] = [..., 'cachecontrol']).
 *
 * Anonymous, successful GET responses are safe to cache briefly at the edge/CDN. Any
 * response served to a logged-in editor/admin must never be cached or shared, since it
 * may contain draft content, admin UI, or otherwise sensitive editorial data.
 */
class CacheControlFilter implements FilterInterface
{
    /**
     * @param array|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Only registered as an `after` filter; nothing to do before the request runs.
    }

    /**
     * @param array|null $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $isLoggedIn = session('user_id') !== null;

        if ($isLoggedIn) {
            $response->setHeader('Cache-Control', 'private, no-store');
        } elseif ($response->getStatusCode() === 200 && strtoupper($request->getMethod()) === 'GET') {
            $response->setHeader('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
        }

        $body = $response->getBody();

        if ($response->getStatusCode() === 200 && is_string($body)) {
            $response->setHeader('ETag', '"' . md5($body) . '"');
        }
    }
}
