<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Shared base for every Front and Admin controller.
 *
 * @property IncomingRequest $request
 */
abstract class BaseController extends Controller
{
    /**
     * @var list<string>
     */
    protected $helpers = ['url', 'form', 'text', 'seo', 'image', 'uuid'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * The currently authenticated admin user, or null on the public front-end.
     * Session key 'user_id' is set by Admin\AuthController on login.
     */
    protected function currentUser(): ?\App\Entities\User
    {
        $userId = session('user_id');

        if ($userId === null) {
            return null;
        }

        return model(\App\Models\UserModel::class)->find($userId);
    }

    protected function requireRole(array $roles): bool
    {
        $user = $this->currentUser();

        return $user !== null && in_array($user->role, $roles, true);
    }
}
