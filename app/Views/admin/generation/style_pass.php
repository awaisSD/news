<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    .style-pass-columns { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .style-pass-columns > div { flex: 1 1 300px; min-width: 0; border: 1px solid #ddd; padding: 0.75rem; }
</style>

<h1>Style / readability pass — <?= esc($article->headline) ?></h1>
<p><em>This is a suggested style/readability pass, not an automatic rewrite. Nothing here changes the live article until you click Accept below.</em></p>

<?php if ($completedJob !== null): ?>
    <?php $suggestion = $completedJob->response_metadata ?? []; ?>

    <?php if (! in_array($completedJob->status, ['completed', 'failed', 'cancelled'], true)): ?>
        <meta http-equiv="refresh" content="5">
    <?php endif ?>

    <div class="style-pass-columns">
        <div>
            <h2>Current</h2>
            <h3><?= esc($article->headline) ?></h3>
            <div><?= $article->body_html ?></div>
        </div>
        <div>
            <h2>Suggested</h2>
            <h3><?= esc($suggestion['headline'] ?? $article->headline) ?></h3>
            <div><?= $suggestion['body_html'] ?? '<em>No suggested body returned.</em>' ?></div>
        </div>
    </div>

    <form method="post" action="<?= site_url('admin/articles/' . $article->id . '/style-pass/accept') ?>" style="display:inline-block; margin-right: 1rem;">
        <?= csrf_field() ?>
        <button type="submit">Accept suggestion</button>
    </form>

    <form method="post" action="<?= site_url('admin/articles/' . $article->id . '/style-pass/reject') ?>" style="display:inline-block;">
        <?= csrf_field() ?>
        <button type="submit">Reject suggestion</button>
    </form>

    <h2>Run another pass</h2>
<?php elseif ($job !== null): ?>
    <meta http-equiv="refresh" content="5">
    <p>
        Status: <span class="badge badge-<?= esc($job->status, 'attr') ?>"><?= esc(str_replace('_', ' ', $job->status)) ?></span>
        <?php if (in_array($job->status, ['pending', 'processing'], true)): ?>
            — waiting on the background worker. This page refreshes automatically.
        <?php endif ?>
        <?php if ($job->status === 'failed'): ?>
            — <?= esc($job->error_message ?? 'Unknown error.') ?>
        <?php endif ?>
    </p>
<?php endif ?>

<?= form_open('admin/articles/' . $article->id . '/style-pass') ?>
    <?= csrf_field() ?>
    <p>
        <label for="house_style_notes">House style notes</label><br>
        <textarea id="house_style_notes" name="house_style_notes" rows="3" style="width:100%;"><?= esc(old('house_style_notes', $defaultHouseStyleNotes)) ?></textarea>
    </p>
    <button type="submit">Queue style pass</button>
<?= form_close() ?>

<p><a href="<?= site_url('admin/review-queue/' . $article->id) ?>">Back to review</a></p>

<?= $this->endSection() ?>
