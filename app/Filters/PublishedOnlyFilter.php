<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Lightweight defensive placeholder for front-end routes that resolve an article by
 * ID/slug outside the normal published-only Model query (e.g. an admin "preview" link
 * shared externally, or any route where visibility could otherwise be tampered with via
 * the query string).
 *
 * IMPORTANT: this filter does NOT and cannot perform the real access-control decision —
 * it has no route-specific context about which article is being requested or what its
 * status is. The actual "only published articles are publicly visible" logic lives in
 * ArticleModel::findPublishedByCategoryAndSlug(), which scopes the query itself. This
 * filter exists purely as a documented safety net against a very specific and cheap
 * attack: appending something like `?status=published` to a URL in an attempt to
 * override visibility logic further down the stack. If present, that parameter is
 * simply neutralized (ignored) here and logged; the request is never 404'd purely for
 * carrying it, since a stray query param is not itself evidence of malicious intent.
 */
class PublishedOnlyFilter implements FilterInterface
{
    /**
     * @param array|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if ($request->getGet('status') !== null) {
            log_message(
                'warning',
                'Ignored client-supplied "status" query parameter on {path} — visibility is controlled ' .
                'solely by ArticleModel::findPublishedByCategoryAndSlug().',
                ['path' => $request->getUri()->getPath()]
            );

            // The param has no effect on anything downstream; nothing further to do here.
        }
    }

    /**
     * @param array|null $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }
}
