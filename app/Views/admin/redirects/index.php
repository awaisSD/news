<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a class="btn" href="<?= site_url('admin/redirects/new') ?>">New redirect</a>
</div>

<div class="card">
    <table class="admin-table">
        <thead><tr><th>Old path</th><th>New path</th><th>Type</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($redirects as $redirect): ?>
            <tr>
                <td><?= esc($redirect->old_path) ?></td>
                <td><?= esc($redirect->new_path) ?></td>
                <td><?= (int) $redirect->redirect_type ?></td>
                <td>
                    <a href="<?= site_url('admin/redirects/' . $redirect->id . '/edit') ?>">Edit</a>
                    &nbsp;
                    <?= form_open('admin/redirects/' . $redirect->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this redirect?');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($redirects === []): ?>
            <tr><td colspan="4" class="muted">No redirects yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
