<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<h1>Topics</h1>
<p><a href="<?= site_url('admin/topics/new') ?>">+ New topic</a></p>

<form method="get" action="<?= site_url('admin/topics') ?>">
    <label for="status">Status</label>
    <select id="status" name="status" onchange="this.form.submit()">
        <option value="">All</option>
        <?php foreach ($statuses as $status): ?>
            <option value="<?= esc($status, 'attr') ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= esc(str_replace('_', ' ', $status)) ?></option>
        <?php endforeach ?>
    </select>
</form>

<?php if ($topics === []): ?>
    <p><em>No topics found.</em></p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Created via</th>
                <th>Status</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($topics as $topic): ?>
                <tr>
                    <td><?= esc($topic->title) ?></td>
                    <td><?= esc($topic->created_via) ?></td>
                    <td><span class="badge badge-<?= esc($topic->status, 'attr') ?>"><?= esc(str_replace('_', ' ', $topic->status)) ?></span></td>
                    <td><?= esc((string) $topic->created_at) ?></td>
                    <td>
                        <a href="<?= site_url('admin/topics/' . $topic->id) ?>">View</a>
                        &nbsp;|&nbsp;
                        <a href="<?= site_url('admin/topics/' . $topic->id . '/edit') ?>">Edit</a>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>

<?= $this->endSection() ?>
