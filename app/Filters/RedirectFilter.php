<?php

namespace App\Filters;

use App\Models\RedirectModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Global `before` filter (see Config\Filters::$filters['before'] = ['forcehttps', 'redirects']).
 *
 * Runs on every request, ahead of routing, so old URLs can be 301/302'd to their new
 * location before the router ever gets a chance to 404 them.
 */
class RedirectFilter implements FilterInterface
{
    /**
     * @param array|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');

        try {
            $redirect = model(RedirectModel::class)->findByOldPath($path);
        } catch (Throwable $e) {
            // A DB error here (e.g. migrations not yet run in a fresh environment) must
            // never take the whole site down. Log it and let the request continue
            // normally to the router.
            log_message('error', 'RedirectFilter lookup failed: {message}', ['message' => $e->getMessage()]);

            return;
        }

        if ($redirect === null) {
            return;
        }

        return redirect()->to($redirect->new_path)->setStatusCode($redirect->redirect_type ?? 301);
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
