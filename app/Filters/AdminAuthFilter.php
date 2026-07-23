<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Guards the admin/* route group.
 *
 * Applied as a route-group filter (see Config\Filters::$filters['adminauth']['before'] = ['admin/*']).
 * Because admin/login and admin/logout live inside the same admin/* group, they must be
 * explicitly excluded here or nobody would ever be able to reach the login page.
 */
class AdminAuthFilter implements FilterInterface
{
    /**
     * @param array|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $path = trim($request->getUri()->getPath(), '/');

        // Allow the login page (GET the form, POST the credentials) and logout through
        // without requiring an existing session, otherwise nobody could ever log in.
        if (in_array($path, ['admin/login', 'admin/logout'], true)) {
            return;
        }

        $userId = session('user_id');

        if ($userId === null) {
            return redirect()->to('/admin/login')->with('error', 'Please log in.');
        }

        $user = model(UserModel::class)->find($userId);

        if ($user === null || ! $user->is_active) {
            session()->destroy();

            return redirect()->to('/admin/login')->with('error', 'Please log in.');
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
