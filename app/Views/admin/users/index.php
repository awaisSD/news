<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a class="btn" href="<?= site_url('admin/users/new') ?>">New user</a>
</div>

<div class="card">
    <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= esc($user->name) ?></td>
                <td><?= esc($user->email) ?></td>
                <td><span class="badge"><?= esc($user->role) ?></span></td>
                <td><?= $user->is_active ? 'Yes' : 'No' ?></td>
                <td><?= $user->last_login_at ? esc((string) $user->last_login_at) : '—' ?></td>
                <td>
                    <a href="<?= site_url('admin/users/' . $user->id . '/edit') ?>">Edit</a>
                    &nbsp;
                    <?= form_open('admin/users/' . $user->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this user? Consider deactivating instead.');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($users === []): ?>
            <tr><td colspan="6" class="muted">No users yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
