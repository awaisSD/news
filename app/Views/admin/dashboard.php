<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="grid-2">
    <div>
        <div class="card">
            <h2 class="section-title">Articles by status</h2>
            <table class="admin-table">
                <thead><tr><th>Status</th><th>Count</th></tr></thead>
                <tbody>
                <?php foreach ($statusCounts as $status => $count): ?>
                    <tr>
                        <td><span class="badge badge-<?= esc($status) ?>"><?= esc(str_replace('_', ' ', $status)) ?></span></td>
                        <td><?= (int) $count ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h2 class="section-title">Recent audit log</h2>
            <?php if ($recentAuditLog === []): ?>
                <p class="muted">No audit log entries yet.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead><tr><th>When</th><th>Actor</th><th>Action</th><th>Subject</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentAuditLog as $entry): ?>
                        <tr>
                            <td><?= esc((string) $entry->created_at) ?></td>
                            <td><?= esc($actorNames[$entry->user_id] ?? ('#' . $entry->user_id)) ?></td>
                            <td><?= esc($entry->action) ?></td>
                            <td><?= esc($entry->subject_type) ?> #<?= (int) $entry->subject_id ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <p><a href="<?= site_url('admin/audit-log') ?>">View full audit log &rarr;</a></p>
        </div>
    </div>

    <div>
        <div class="card">
            <h2 class="section-title">Review queue</h2>
            <p style="font-size:28px;font-weight:700;margin:0;"><?= (int) $reviewQueueSize ?></p>
            <p class="muted">Articles awaiting editorial review or with changes requested.</p>
            <a class="btn btn-secondary" href="<?= site_url('admin/review-queue') ?>">Open review queue</a>
        </div>

        <div class="card">
            <h2 class="section-title">AI generation today</h2>
            <p style="font-size:28px;font-weight:700;margin:0;"><?= (int) $jobsToday ?> / <?= (int) $dailyCap ?></p>
            <p class="muted">Generation jobs created today vs. the daily cap.</p>
            <a class="btn btn-secondary" href="<?= site_url('admin/generate') ?>">Generate article</a>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
