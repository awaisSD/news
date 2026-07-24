<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<h1><?= $isNew ? 'New topic' : 'Edit topic' ?></h1>

<?php if ($isNew): ?>
    <?= form_open('admin/topics') ?>
<?php else: ?>
    <?php // 'put' isn't a valid HTML form method; form_open() prints it verbatim and
    // browsers silently fall back to GET, which never reaches update(). Routes use
    // 'websafe' => true, so update() is already registered as plain POST. ?>
    <?= form_open('admin/topics/' . $topic->id, ['method' => 'post']) ?>
<?php endif ?>
    <?= csrf_field() ?>

    <p>
        <label for="title">Title</label><br>
        <input type="text" id="title" name="title" value="<?= esc(old('title', $topic->title ?? ''), 'attr') ?>" required maxlength="255" style="width: 100%;">
    </p>

    <p>
        <label for="brief">Editorial brief</label><br>
        <small>
            What should this article actually say — the angle, the facts to cover, the point of view.
            This is a brief for a human/AI writer to work from, not a place to paste a competitor's
            article for it to be rewritten or spun. Sourced material belongs in the sources list, not here.
        </small><br>
        <textarea id="brief" name="brief" rows="6" required style="width: 100%;" placeholder="e.g. Explain what changed in the new zoning ordinance, who is affected, and when it takes effect. Include reactions from the two city council members quoted in our source articles."><?= esc(old('brief', $topic->brief ?? '')) ?></textarea>
    </p>

    <p>
        <label for="angle_notes">Angle notes (optional)</label><br>
        <textarea id="angle_notes" name="angle_notes" rows="3" style="width: 100%;"><?= esc(old('angle_notes', $topic->angle_notes ?? '')) ?></textarea>
    </p>

    <p>
        <label for="assigned_editor_id">Assigned editor</label><br>
        <select id="assigned_editor_id" name="assigned_editor_id">
            <option value="">— Unassigned —</option>
            <?php foreach ($editors as $editor): ?>
                <option value="<?= (int) $editor->id ?>" <?= (int) old('assigned_editor_id', (string) ($topic->assigned_editor_id ?? '')) === $editor->id ? 'selected' : '' ?>>
                    <?= esc($editor->name) ?> (<?= esc($editor->role) ?>)
                </option>
            <?php endforeach ?>
        </select>
    </p>

    <?php if (! $isNew): ?>
        <p>
            <label for="status">Status</label><br>
            <select id="status" name="status">
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= esc($status, 'attr') ?>" <?= $topic->status === $status ? 'selected' : '' ?>><?= esc(str_replace('_', ' ', $status)) ?></option>
                <?php endforeach ?>
            </select>
            <small>Manually correcting status (e.g. archiving a topic that's no longer relevant) is allowed here.</small>
        </p>
    <?php endif ?>

    <button type="submit"><?= $isNew ? 'Create topic' : 'Save changes' ?></button>
<?= form_close() ?>

<?= $this->endSection() ?>
