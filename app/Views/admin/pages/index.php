<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a class="btn" href="<?= site_url('admin/pages/new') ?>">New page</a>
</div>

<div class="card">
    <table class="admin-table">
        <thead><tr><th>Title</th><th>Slug</th><th>Published</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pages as $page): ?>
            <tr>
                <td><?= esc($page->title) ?></td>
                <td><?= esc($page->slug) ?></td>
                <td><?= $page->is_published ? 'Yes' : 'No' ?></td>
                <td>
                    <a href="<?= site_url('admin/pages/' . $page->id . '/edit') ?>">Edit</a>
                    &nbsp;
                    <?= form_open('admin/pages/' . $page->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this page?');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($pages === []): ?>
            <tr><td colspan="4" class="muted">No pages yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
