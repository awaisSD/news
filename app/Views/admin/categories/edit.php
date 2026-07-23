<?php $isNew = $category->id === null; ?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:600px;">
    <?= form_open($isNew ? 'admin/categories' : 'admin/categories/' . $category->id, ['method' => $isNew ? 'post' : 'put']) ?>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required maxlength="100" value="<?= esc($category->name ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="slug">Slug</label>
            <input type="text" id="slug" name="slug" required maxlength="120" value="<?= esc($category->slug ?? '', 'attr') ?>">
            <p class="hint">Auto-suggested from the name, but editable. Cannot be a reserved word (admin, feed, tag, page, etc).</p>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent category</label>
            <select id="parent_id" name="parent_id">
                <option value="">— top level —</option>
                <?php foreach ($topLevelCategories as $top): ?>
                    <option value="<?= (int) $top->id ?>" <?= (int) ($category->parent_id ?? 0) === (int) $top->id ? 'selected' : '' ?>><?= esc($top->name) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="hint">Only top-level categories are offered here, so nesting never goes deeper than one level.</p>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= esc($category->description ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= (int) ($category->sort_order ?? 0) ?>">
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= ($category->is_active ?? true) ? 'checked' : '' ?>> Active</label>
        </div>

        <button type="submit" class="btn"><?= $isNew ? 'Create category' : 'Save changes' ?></button>
    <?= form_close() ?>
</div>

<script>
// Small progressive-enhancement niceties only — the form works fully
// without JS via native HTML5 validation; this just auto-fills the slug
// field from the name for a new category until the user edits it by hand.
(function () {
    var nameInput = document.getElementById('name');
    var slugInput = document.getElementById('slug');
    var slugEdited = <?= $isNew ? 'false' : 'true' ?>;

    if (!nameInput || !slugInput) { return; }

    slugInput.addEventListener('input', function () { slugEdited = true; });

    nameInput.addEventListener('input', function () {
        if (slugEdited) { return; }
        slugInput.value = nameInput.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
})();
</script>

<?= $this->endSection() ?>
