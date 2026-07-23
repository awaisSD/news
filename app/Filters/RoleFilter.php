<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Fine-grained role check, attached per-route, e.g. ['filter' => 'role:editor,admin'].
 *
 * CodeIgniter splits the colon-separated filter argument string into $arguments
 * automatically, so for 'role:editor,admin' this filter receives $arguments === ['editor', 'admin'].
 */
class RoleFilter implements FilterInterface
{
    /**
     * @param array|null $arguments Allowed role strings for this route, e.g. ['editor', 'admin'].
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = session('user_id');
        $user   = $userId === null ? null : model(UserModel::class)->find($userId);

        $allowedRoles = is_array($arguments) ? $arguments : [];

        if ($user === null || ! in_array($user->role, $allowedRoles, true)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody('Forbidden — insufficient role.');
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
