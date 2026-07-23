<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a class="btn" href="<?= site_url('admin/tags/new') ?>">New tag</a>
</div>

<div class="card">
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($tags as $tag): ?>
            <tr>
                <td><?= esc($tag->name) ?></td>
                <td><?= esc($tag->slug) ?></td>
                <td>
                    <a href="<?= site_url('admin/tags/' . $tag->id . '/edit') ?>">Edit</a>
                    &nbsp;
                    <?= form_open('admin/tags/' . $tag->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this tag?');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($tags === []): ?>
            <tr><td colspan="3" class="muted">No tags yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
