<?php $isNew = $page->id === null; ?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:800px;">
    <?= form_open($isNew ? 'admin/pages' : 'admin/pages/' . $page->id, ['method' => $isNew ? 'post' : 'put']) ?>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" maxlength="150"
                   value="<?= esc($page->slug ?? '', 'attr') ?>" <?= $isNew ? 'required' : 'readonly' ?>>
            <?php if (! $isNew): ?>
                <p class="hint">Read-only after creation — front-end static routes depend on this slug staying fixed.</p>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" required maxlength="255" value="<?= esc($page->title ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="body_html">Body (HTML)</label>
            <!-- Plain textarea for this pass; a JS WYSIWYG (TinyMCE/Quill)
                 can be layered on later against this same "body_html" field
                 name without any controller changes. -->
            <textarea id="body_html" name="body_html" class="body-html"><?= esc($page->body_html ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="meta_description">Meta description</label>
            <textarea id="meta_description" name="meta_description"><?= esc($page->meta_description ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="is_published" value="1" <?= ($page->is_published ?? false) ? 'checked' : '' ?>> Published</label>
        </div>

        <button type="submit" class="btn"><?= $isNew ? 'Create page' : 'Save changes' ?></button>
    <?= form_close() ?>
</div>

<?= $this->endSection() ?>
