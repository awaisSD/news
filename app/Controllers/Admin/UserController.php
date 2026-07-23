<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\User;
use App\Models\AuditLogModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class UserController extends BaseController
{
    public function index()
    {
        return view('admin/users/index', [
            'title' => 'Users',
            'users' => model(UserModel::class)->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function new()
    {
        return view('admin/users/edit', ['title' => 'New user', 'user' => new User()]);
    }

    public function create()
    {
        $rules = [
            'name'     => 'required|max_length[150]',
            'email'    => 'required|valid_email|max_length[191]|is_unique[users.email]',
            'role'     => 'required|in_list[writer,editor,admin]',
            'password' => 'required|min_length[8]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = $this->collectPostData();
        $data['uuid']          = generate_uuid4();
        $data['password_hash'] = password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT);

        $id = model(UserModel::class)->insert($data, true);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'created', 'user', (int) $id, null,
            ['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role']],
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/users')->with('success', 'User created.');
    }

    public function edit($id)
    {
        $user = model(UserModel::class)->find((int) $id);

        if ($user === null) {
            throw new PageNotFoundException("User #{$id} not found.");
        }

        return view('admin/users/edit', ['title' => 'Edit user', 'user' => $user]);
    }

    public function update($id)
    {
        $user = model(UserModel::class)->find((int) $id);

        if ($user === null) {
            throw new PageNotFoundException("User #{$id} not found.");
        }

        $rules = [
            'name'  => 'required|max_length[150]',
            'email' => "required|valid_email|max_length[191]|is_unique[users.email,id,{$id}]",
            'role'  => 'required|in_list[writer,editor,admin]',
        ];

        $newPassword = trim((string) $this->request->getPost('password'));
        if ($newPassword !== '') {
            $rules['password'] = 'min_length[8]';
        }

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $before = ['name' => $user->name, 'email' => $user->email, 'role' => $user->role, 'is_active' => $user->is_active];

        $data = $this->collectPostData();

        // Only rehash the password if a new one was actually submitted —
        // otherwise leave the existing password_hash column untouched.
        if ($newPassword !== '') {
            $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        model(UserModel::class)->update((int) $id, $data);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'updated', 'user', (int) $id, $before,
            ['name' => $data['name'], 'email' => $data['email'], 'role' => $data['role'], 'is_active' => $data['is_active']],
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/users')->with('success', 'User updated.');
    }

    public function show($id)
    {
        return redirect()->to('/admin/users/' . $id . '/edit');
    }

    public function delete($id)
    {
        $user = model(UserModel::class)->find((int) $id);

        if ($user === null) {
            throw new PageNotFoundException("User #{$id} not found.");
        }

        // UserModel has no soft-delete column, so this permanently removes
        // the row. Deactivating (is_active = 0) via the edit form is the
        // reversible alternative for "remove someone's access".
        model(UserModel::class)->delete((int) $id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'deleted', 'user', (int) $id,
            ['name' => $user->name, 'email' => $user->email], null,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/users')->with('success', 'User deleted.');
    }

    private function collectPostData(): array
    {
        return [
            'name'            => (string) $this->request->getPost('name'),
            'email'           => (string) $this->request->getPost('email'),
            'role'            => (string) $this->request->getPost('role'),
            'bio'             => $this->request->getPost('bio') ?: null,
            'credentials'     => $this->request->getPost('credentials') ?: null,
            'twitter_handle'  => $this->request->getPost('twitter_handle') ?: null,
            'linkedin_url'    => $this->request->getPost('linkedin_url') ?: null,
            'is_active'       => (bool) $this->request->getPost('is_active'),
        ];
    }

    // generate_uuid4() comes from app/Helpers/uuid_helper.php (preloaded by BaseController).
}
