<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php if (! in_array($job->status, ['completed', 'failed', 'cancelled'], true)): ?>
    <meta http-equiv="refresh" content="5">
<?php endif ?>

<h1>Generation job #<?= (int) $job->id ?></h1>

<p>
    Status: <span class="badge badge-<?= esc($job->status, 'attr') ?>"><?= esc(str_replace('_', ' ', $job->status)) ?></span>
</p>

<?php if (in_array($job->status, ['pending', 'processing'], true)): ?>
    <p><em>Waiting on the background worker (<code>php spark ai:process-queue</code>). This page refreshes automatically every 5 seconds.</em></p>
<?php endif ?>

<?php if ($job->status === 'completed' && $job->article_id !== null): ?>
    <p>
        Draft is ready.
        <a href="<?= site_url('admin/review-queue/' . $job->article_id) ?>">Open in Review Queue</a>
        &nbsp;|&nbsp;
        <a href="<?= site_url('admin/articles/' . $job->article_id . '/edit') ?>">Edit article</a>
    </p>
<?php endif ?>

<?php if ($job->status === 'failed'): ?>
    <p style="color:#a30000;"><strong>Generation failed:</strong> <?= esc($job->error_message ?? 'Unknown error.') ?></p>
<?php endif ?>

<?php if ($job->status === 'blocked_by_cap'): ?>
    <p style="color:#a30000;"><strong>Blocked by the daily generation cap.</strong> Ask an admin to raise it in AI Settings, or try again tomorrow.</p>
<?php endif ?>

<?php if ($job->status === 'cancelled'): ?>
    <p><em>This job was cancelled.</em></p>
<?php endif ?>

<p><a href="<?= site_url('admin/generate') ?>">Back to generation</a></p>

<?= $this->endSection() ?>
