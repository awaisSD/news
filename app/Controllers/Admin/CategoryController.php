<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Category;
use App\Models\AuditLogModel;
use App\Models\CategoryModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class CategoryController extends BaseController
{
    /**
     * CI4 4.5 RouteCollection::resource() default action map used by
     * Routes.php for this controller: index (GET), new (GET), create
     * (POST), edit (GET), update (PUT/PATCH), show (GET), delete (DELETE).
     * Plain HTML forms can't submit PUT/PATCH/DELETE directly, so the
     * edit/delete views below use form_open(..., ['method' => 'put'/'delete'])
     * which relies on CodeIgniter's form-helper _method spoofing to reach
     * those routes without any JS.
     */
    public function index()
    {
        $categories = model(CategoryModel::class)->orderBy('parent_id', 'ASC')->orderBy('sort_order', 'ASC')->findAll();

        $namesById = [];
        foreach ($categories as $c) {
            $namesById[$c->id] = $c->name;
        }

        return view('admin/categories/index', [
            'title'      => 'Categories',
            'categories' => $categories,
            'namesById'  => $namesById,
        ]);
    }

    public function new()
    {
        return view('admin/categories/edit', $this->formData(new Category()));
    }

    public function create()
    {
        $rules = [
            'name' => 'required|max_length[100]',
            'slug' => 'required|max_length[120]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = model(CategoryModel::class);
        $slug  = (string) $this->request->getPost('slug');

        if ($model->isSlugReserved($slug)) {
            return redirect()->back()->withInput()->with('error', "The slug \"{$slug}\" is reserved and cannot be used.");
        }

        $data = $this->collectPostData();

        try {
            $id = $model->insert($data, true);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'created',
            'category',
            (int) $id,
            null,
            $data,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/categories')->with('success', 'Category created.');
    }

    public function edit($id)
    {
        $category = model(CategoryModel::class)->find((int) $id);

        if ($category === null) {
            throw new PageNotFoundException("Category #{$id} not found.");
        }

        return view('admin/categories/edit', $this->formData($category));
    }

    public function update($id)
    {
        $category = model(CategoryModel::class)->find((int) $id);

        if ($category === null) {
            throw new PageNotFoundException("Category #{$id} not found.");
        }

        $rules = [
            'name' => 'required|max_length[100]',
            'slug' => 'required|max_length[120]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = model(CategoryModel::class);
        $slug  = (string) $this->request->getPost('slug');

        if ($model->isSlugReserved($slug)) {
            return redirect()->back()->withInput()->with('error', "The slug \"{$slug}\" is reserved and cannot be used.");
        }

        $before = ['name' => $category->name, 'slug' => $category->slug];
        $data   = $this->collectPostData();

        try {
            $model->update((int) $id, $data);
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'updated',
            'category',
            (int) $id,
            $before,
            $data,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/categories')->with('success', 'Category updated.');
    }

    public function show($id)
    {
        return redirect()->to('/admin/categories/' . $id . '/edit');
    }

    public function delete($id)
    {
        $category = model(CategoryModel::class)->find((int) $id);

        if ($category === null) {
            throw new PageNotFoundException("Category #{$id} not found.");
        }

        model(CategoryModel::class)->delete((int) $id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'deleted',
            'category',
            (int) $id,
            ['name' => $category->name, 'slug' => $category->slug],
            null,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/categories')->with('success', 'Category deleted.');
    }

    /**
     * Only top-level (parent_id === null) categories are offered as a
     * possible parent, so nesting can never go deeper than one level.
     */
    private function formData(Category $category): array
    {
        $topLevel = model(CategoryModel::class)->where('parent_id', null)->orderBy('name', 'ASC')->findAll();

        // A category can't be its own parent.
        $topLevel = array_filter($topLevel, static fn ($c) => $c->id !== $category->id);

        return [
            'title'         => $category->id ? 'Edit category' : 'New category',
            'category'      => $category,
            'topLevelCategories' => $topLevel,
        ];
    }

    private function collectPostData(): array
    {
        return [
            'name'        => (string) $this->request->getPost('name'),
            'slug'        => (string) $this->request->getPost('slug'),
            'parent_id'   => $this->request->getPost('parent_id') ?: null,
            'description' => $this->request->getPost('description') ?: null,
            'sort_order'  => (int) ($this->request->getPost('sort_order') ?: 0),
            'is_active'   => (bool) $this->request->getPost('is_active'),
        ];
    }
}
