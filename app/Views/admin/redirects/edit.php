<?php $isNew = $redirect->id === null; ?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:600px;">
    <?php // 'put' isn't a valid HTML form method; form_open() prints it verbatim and
    // browsers silently fall back to GET, which never reaches update(). Routes use
    // 'websafe' => true, so update() is already registered as plain POST. ?>
    <?= form_open($isNew ? 'admin/redirects' : 'admin/redirects/' . $redirect->id, ['method' => 'post']) ?>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="old_path">Old path</label>
            <input type="text" id="old_path" name="old_path" required maxlength="500" value="<?= esc($redirect->old_path ?? '', 'attr') ?>" placeholder="/old-article-slug">
        </div>

        <div class="form-group">
            <label for="new_path">New path</label>
            <input type="text" id="new_path" name="new_path" required maxlength="500" value="<?= esc($redirect->new_path ?? '', 'attr') ?>" placeholder="/news/new-article-slug">
        </div>

        <div class="form-group">
            <label for="redirect_type">Redirect type</label>
            <select id="redirect_type" name="redirect_type" required>
                <option value="301" <?= (int) ($redirect->redirect_type ?? 301) === 301 ? 'selected' : '' ?>>301 — Permanent</option>
                <option value="302" <?= (int) ($redirect->redirect_type ?? 301) === 302 ? 'selected' : '' ?>>302 — Temporary</option>
            </select>
        </div>

        <button type="submit" class="btn"><?= $isNew ? 'Create redirect' : 'Save changes' ?></button>
    <?= form_close() ?>
</div>

<?= $this->endSection() ?>
