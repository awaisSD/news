<?php $isNew = $tag->id === null; ?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:500px;">
    <?php // 'put' isn't a valid HTML form method; form_open() prints it verbatim and
    // browsers silently fall back to GET, which never reaches update(). Routes use
    // 'websafe' => true, so update() is already registered as plain POST. ?>
    <?= form_open($isNew ? 'admin/tags' : 'admin/tags/' . $tag->id, ['method' => 'post']) ?>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required maxlength="100" value="<?= esc($tag->name ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" required maxlength="120" value="<?= esc($tag->slug ?? '', 'attr') ?>">
        </div>

        <button type="submit" class="btn"><?= $isNew ? 'Create tag' : 'Save changes' ?></button>
    <?= form_close() ?>
</div>

<?= $this->endSection() ?>
