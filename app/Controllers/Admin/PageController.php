<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Page;
use App\Models\AuditLogModel;
use App\Models\PageModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class PageController extends BaseController
{
    public function index()
    {
        return view('admin/pages/index', [
            'title' => 'Pages',
            'pages' => model(PageModel::class)->orderBy('slug', 'ASC')->findAll(),
        ]);
    }

    public function new()
    {
        return view('admin/pages/edit', ['title' => 'New page', 'page' => new Page()]);
    }

    public function create()
    {
        $rules = [
            'slug'  => 'required|max_length[150]|is_unique[pages.slug]',
            'title' => 'required|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $data = $this->collectPostData();
        $id   = model(PageModel::class)->insert($data, true);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'created', 'page', (int) $id, null, $data,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/pages')->with('success', 'Page created.');
    }

    public function edit($id)
    {
        $page = model(PageModel::class)->find((int) $id);

        if ($page === null) {
            throw new PageNotFoundException("Page #{$id} not found.");
        }

        return view('admin/pages/edit', ['title' => 'Edit page', 'page' => $page]);
    }

    public function update($id)
    {
        $page = model(PageModel::class)->find((int) $id);

        if ($page === null) {
            throw new PageNotFoundException("Page #{$id} not found.");
        }

        $rules = ['title' => 'required|max_length[255]'];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $before = ['title' => $page->title, 'is_published' => $page->is_published];

        // slug is intentionally NOT taken from POST here — these pages are
        // seeded with fixed slugs that front-end static routes depend on
        // (see Routes.php $staticPages); the edit form renders it readonly.
        $data = [
            'title'             => (string) $this->request->getPost('title'),
            'body_html'         => (string) $this->request->getPost('body_html'),
            'meta_description'  => $this->request->getPost('meta_description') ?: null,
            'is_published'      => (bool) $this->request->getPost('is_published'),
            'updated_by'        => $this->currentUser()->id,
        ];

        model(PageModel::class)->update((int) $id, $data);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'updated', 'page', (int) $id, $before,
            ['title' => $data['title'], 'is_published' => $data['is_published']],
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/pages')->with('success', 'Page updated.');
    }

    public function show($id)
    {
        return redirect()->to('/admin/pages/' . $id . '/edit');
    }

    public function delete($id)
    {
        $page = model(PageModel::class)->find((int) $id);

        if ($page === null) {
            throw new PageNotFoundException("Page #{$id} not found.");
        }

        model(PageModel::class)->delete((int) $id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'deleted', 'page', (int) $id,
            ['slug' => $page->slug], null, $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/pages')->with('success', 'Page deleted.');
    }

    private function collectPostData(): array
    {
        return [
            'slug'              => (string) $this->request->getPost('slug'),
            'title'             => (string) $this->request->getPost('title'),
            'body_html'         => (string) $this->request->getPost('body_html'),
            'meta_description'  => $this->request->getPost('meta_description') ?: null,
            'is_published'      => (bool) $this->request->getPost('is_published'),
            'updated_by'        => $this->currentUser()->id,
        ];
    }
}
