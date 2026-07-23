<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Tag;
use App\Models\AuditLogModel;
use App\Models\TagModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TagController extends BaseController
{
    public function index()
    {
        return view('admin/tags/index', [
            'title' => 'Tags',
            'tags'  => model(TagModel::class)->orderBy('name', 'ASC')->findAll(),
        ]);
    }

    public function new()
    {
        return view('admin/tags/edit', ['title' => 'New tag', 'tag' => new Tag()]);
    }

    public function create()
    {
        $rules = [
            'name' => 'required|max_length[100]',
            'slug' => 'required|max_length[120]|is_unique[tags.slug]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = [
            'name' => (string) $this->request->getPost('name'),
            'slug' => (string) $this->request->getPost('slug'),
        ];

        $id = model(TagModel::class)->insert($data, true);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'created', 'tag', (int) $id, null, $data,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/tags')->with('success', 'Tag created.');
    }

    public function edit($id)
    {
        $tag = model(TagModel::class)->find((int) $id);

        if ($tag === null) {
            throw new PageNotFoundException("Tag #{$id} not found.");
        }

        return view('admin/tags/edit', ['title' => 'Edit tag', 'tag' => $tag]);
    }

    public function update($id)
    {
        $tag = model(TagModel::class)->find((int) $id);

        if ($tag === null) {
            throw new PageNotFoundException("Tag #{$id} not found.");
        }

        $rules = [
            'name' => 'required|max_length[100]',
            'slug' => "required|max_length[120]|is_unique[tags.slug,id,{$id}]",
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $before = ['name' => $tag->name, 'slug' => $tag->slug];
        $data   = [
            'name' => (string) $this->request->getPost('name'),
            'slug' => (string) $this->request->getPost('slug'),
        ];

        model(TagModel::class)->update((int) $id, $data);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'updated', 'tag', (int) $id, $before, $data,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/tags')->with('success', 'Tag updated.');
    }

    public function show($id)
    {
        return redirect()->to('/admin/tags/' . $id . '/edit');
    }

    public function delete($id)
    {
        $tag = model(TagModel::class)->find((int) $id);

        if ($tag === null) {
            throw new PageNotFoundException("Tag #{$id} not found.");
        }

        model(TagModel::class)->delete((int) $id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'deleted', 'tag', (int) $id,
            ['name' => $tag->name, 'slug' => $tag->slug], null,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/tags')->with('success', 'Tag deleted.');
    }
}
