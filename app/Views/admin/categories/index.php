<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a class="btn" href="<?= site_url('admin/categories/new') ?>">New category</a>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
        <tr><th>Name</th><th>Slug</th><th>Parent</th><th>Sort</th><th>Active</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr>
                <td><?= esc($category->name) ?></td>
                <td><?= esc($category->slug) ?></td>
                <td><?= $category->parent_id ? esc($namesById[$category->parent_id] ?? '—') : '—' ?></td>
                <td><?= (int) $category->sort_order ?></td>
                <td><?= $category->is_active ? 'Yes' : 'No' ?></td>
                <td>
                    <a href="<?= site_url('admin/categories/' . $category->id . '/edit') ?>">Edit</a>
                    &nbsp;
                    <?= form_open('admin/categories/' . $category->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this category?');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($categories === []): ?>
            <tr><td colspan="6" class="muted">No categories yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
