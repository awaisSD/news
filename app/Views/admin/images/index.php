<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    .image-job-grid { display: flex; flex-wrap: wrap; gap: 1.5rem; }
    .image-job-card { border: 1px solid #ddd; padding: 0.75rem; width: 280px; }
    .image-job-card img { max-width: 100%; height: auto; display: block; margin-bottom: 0.5rem; }
</style>

<h1>Image jobs awaiting review</h1>
<p>Completed AI image generations that have not yet been approved (attached as a featured image) or rejected.</p>

<?php if ($jobs === []): ?>
    <p><em>Nothing waiting — all completed image jobs have been reviewed.</em></p>
<?php else: ?>
    <div class="image-job-grid">
        <?php foreach ($jobs as $job): ?>
            <?php $article = $articlesById[$job->article_id] ?? null; ?>
            <div class="image-job-card">
                <?php if (! empty($job->generated_path)): ?>
                    <img src="<?= esc($job->generated_path, 'attr') ?>" alt="Generated image preview for job #<?= (int) $job->id ?>">
                <?php endif ?>
                <p>
                    <strong><?= $article !== null ? esc($article->headline) : 'Article #' . (int) $job->article_id ?></strong>
                </p>
                <p><small><?= esc($job->prompt) ?></small></p>

                <form method="post" action="<?= site_url('admin/image-jobs/' . $job->id . '/approve') ?>" style="display:inline-block;">
                    <?= csrf_field() ?>
                    <?php if ($article !== null): ?>
                        <input type="hidden" name="return_to" value="<?= site_url('admin/articles/' . $article->id . '/edit') ?>">
                    <?php endif ?>
                    <button type="submit">Approve</button>
                </form>

                <form method="post" action="<?= site_url('admin/image-jobs/' . $job->id . '/reject') ?>" style="display:inline-block;">
                    <?= csrf_field() ?>
                    <button type="submit">Reject</button>
                </form>

                <form method="post" action="<?= site_url('admin/image-jobs/' . $job->id . '/regenerate') ?>" style="display:inline-block;">
                    <?= csrf_field() ?>
                    <button type="submit">Regenerate</button>
                </form>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>

<?= $this->endSection() ?>
