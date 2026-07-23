<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        if (session('user_id') !== null) {
            return redirect()->to('/admin/');
        }

        return view('admin/auth/login', ['title' => 'Log in']);
    }

    public function attemptLogin()
    {
        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        if (trim($email) === '' || trim($password) === '') {
            return redirect()->back()->withInput()->with('error', 'Email and password are required.');
        }

        $user = model(UserModel::class)->where('email', $email)->first();

        // Timing-attack mitigation: always run password_verify(), even when
        // no user was found, against a syntactically valid dummy bcrypt hash
        // so a missing-account response takes the same time as a
        // wrong-password response.
        $hashToCheck = $user->password_hash ?? '$2y$10$invalidhashinvalidhashinvalidha';
        $verified    = password_verify($password, $hashToCheck);

        if ($user === null || ! $verified || ! $user->is_active) {
            return redirect()->back()->withInput()->with('error', 'Invalid credentials.');
        }

        session()->set([
            'user_id'   => $user->id,
            'user_role' => $user->role,
        ]);

        // skipValidation(true): narrow, single-field side-effect write, not
        // a form submit — no need to re-run UserModel's full validation
        // ruleset for a last_login_at timestamp bump.
        model(UserModel::class)->skipValidation(true)->update($user->id, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/admin/login');
    }
}
