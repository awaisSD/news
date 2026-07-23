<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Redirect;
use App\Models\AuditLogModel;
use App\Models\RedirectModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class RedirectController extends BaseController
{
    public function index()
    {
        return view('admin/redirects/index', [
            'title'     => 'Redirects',
            'redirects' => model(RedirectModel::class)->orderBy('created_at', 'DESC')->findAll(),
        ]);
    }

    public function new()
    {
        return view('admin/redirects/edit', ['title' => 'New redirect', 'redirect' => new Redirect()]);
    }

    public function create()
    {
        $rules = [
            'old_path'      => 'required|max_length[500]|is_unique[redirects.old_path]',
            'new_path'      => 'required|max_length[500]',
            'redirect_type' => 'required|in_list[301,302]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = $this->collectPostData();
        $id   = model(RedirectModel::class)->insert($data, true);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'created', 'redirect', (int) $id, null, $data,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/redirects')->with('success', 'Redirect created.');
    }

    public function edit($id)
    {
        $item = model(RedirectModel::class)->find((int) $id);

        if ($item === null) {
            throw new PageNotFoundException("Redirect #{$id} not found.");
        }

        return view('admin/redirects/edit', ['title' => 'Edit redirect', 'redirect' => $item]);
    }

    public function update($id)
    {
        $item = model(RedirectModel::class)->find((int) $id);

        if ($item === null) {
            throw new PageNotFoundException("Redirect #{$id} not found.");
        }

        $rules = [
            'old_path'      => "required|max_length[500]|is_unique[redirects.old_path,id,{$id}]",
            'new_path'      => 'required|max_length[500]',
            'redirect_type' => 'required|in_list[301,302]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $before = ['old_path' => $item->old_path, 'new_path' => $item->new_path, 'redirect_type' => $item->redirect_type];
        $data   = $this->collectPostData();

        model(RedirectModel::class)->update((int) $id, $data);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'updated', 'redirect', (int) $id, $before, $data,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/redirects')->with('success', 'Redirect updated.');
    }

    public function show($id)
    {
        return redirect()->to('/admin/redirects/' . $id . '/edit');
    }

    public function delete($id)
    {
        $item = model(RedirectModel::class)->find((int) $id);

        if ($item === null) {
            throw new PageNotFoundException("Redirect #{$id} not found.");
        }

        model(RedirectModel::class)->delete((int) $id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'deleted', 'redirect', (int) $id,
            ['old_path' => $item->old_path], null, $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/redirects')->with('success', 'Redirect deleted.');
    }

    private function collectPostData(): array
    {
        return [
            'old_path'      => (string) $this->request->getPost('old_path'),
            'new_path'      => (string) $this->request->getPost('new_path'),
            'redirect_type' => (int) $this->request->getPost('redirect_type'),
        ];
    }
}
