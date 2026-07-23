<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<h1><?= esc($topic->title) ?></h1>

<p>
    <span class="badge badge-<?= esc($topic->status, 'attr') ?>"><?= esc(str_replace('_', ' ', $topic->status)) ?></span>
    <span class="badge">via <?= esc($topic->created_via) ?></span>
</p>

<p><a href="<?= site_url('admin/topics/' . $topic->id . '/edit') ?>">Edit</a></p>

<h2>Editorial brief</h2>
<p><?= nl2br(esc($topic->brief)) ?></p>

<?php if (! empty($topic->angle_notes)): ?>
    <h2>Angle notes</h2>
    <p><?= nl2br(esc($topic->angle_notes)) ?></p>
<?php endif ?>

<p><strong>Assigned editor:</strong> <?= $assignedEditor !== null ? esc($assignedEditor->name) : '— Unassigned —' ?></p>

<h2>Sources</h2>
<?php if ($sources === []): ?>
    <p><em>No sources linked to this topic.</em></p>
<?php else: ?>
    <ul>
        <?php foreach ($sources as $source): ?>
            <li>
                <a href="<?= esc($source->source_url, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($source->title) ?></a>
                — <?= esc($source->source_name) ?>
                <?php if (! empty($source->summary)): ?>
                    <br><small><?= esc($source->summary) ?></small>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>

<?php if (session('suggestedAngles')): ?>
    <h2>Suggested angles</h2>
    <ul>
        <?php foreach (session('suggestedAngles') as $angle): ?>
            <li><?= esc((string) $angle) ?></li>
        <?php endforeach ?>
    </ul>
<?php endif ?>

<form method="post" action="<?= site_url('admin/topics/' . $topic->id . '/suggest-angles') ?>" style="display:inline-block; margin-right: 1rem;">
    <?= csrf_field() ?>
    <button type="submit">Suggest angles</button>
</form>

<?php if (in_array($topic->status, ['new', 'assigned'], true)): ?>
    <a href="<?= site_url('admin/generate') ?>?topic_id=<?= (int) $topic->id ?>"><button type="button">Generate draft from this topic</button></a>
<?php endif ?>

<form method="post" action="<?= site_url('admin/topics/' . $topic->id) ?>" style="display:inline-block; margin-left: 1rem;" onsubmit="return confirm('Delete this topic?');">
    <?= csrf_field() ?>
    <input type="hidden" name="_method" value="DELETE">
    <button type="submit">Delete topic</button>
</form>

<?= $this->endSection() ?>
