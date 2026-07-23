<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card">
    <?= form_open('admin/audit-log', ['method' => 'get', 'class' => 'filters-bar']) ?>
        <div class="form-group">
            <label for="subject_type">Subject type</label>
            <input type="text" id="subject_type" name="subject_type" placeholder="article, category, user…" value="<?= esc($filters['subject_type'] ?? '', 'attr') ?>">
        </div>
        <div class="form-group">
            <label for="user_id">Actor</label>
            <select id="user_id" name="user_id">
                <option value="">All</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user->id ?>" <?= (string) ($filters['user_id'] ?? '') === (string) $user->id ? 'selected' : '' ?>><?= esc($user->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    <?= form_close() ?>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
        <tr><th>When</th><th>Actor</th><th>Action</th><th>Subject</th><th>Before</th><th>After</th></tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $entry): ?>
            <tr>
                <td><?= esc((string) $entry->created_at) ?></td>
                <td><?= esc($actorNames[$entry->user_id] ?? ('#' . $entry->user_id)) ?></td>
                <td><?= esc($entry->action) ?></td>
                <td><?= esc($entry->subject_type) ?> #<?= (int) $entry->subject_id ?></td>
                <td><?php if ($entry->before_json): ?><pre class="json-block"><?= esc(json_encode($entry->before_json, JSON_PRETTY_PRINT)) ?></pre><?php else: ?>—<?php endif; ?></td>
                <td><?php if ($entry->after_json): ?><pre class="json-block"><?= esc(json_encode($entry->after_json, JSON_PRETTY_PRINT)) ?></pre><?php else: ?>—<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if ($entries === []): ?>
            <tr><td colspan="6" class="muted">No audit log entries match these filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pager">
        <?= $pager->links() ?>
    </div>
</div>

<?= $this->endSection() ?>
