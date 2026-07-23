<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="grid-2">
    <div class="card">
        <img src="<?= esc($media->getUrl()) ?>" alt="<?= esc($media->alt_text ?? '') ?>" style="max-width:100%;border-radius:6px;">
        <p class="muted">
            <?= (int) $media->width ?>&times;<?= (int) $media->height ?> &middot; <?= esc($media->mime_type) ?>
            &middot; source: <?= esc($media->source) ?> / <?= esc($media->generated_by) ?>
        </p>
    </div>
    <div class="card">
        <?= form_open('admin/media/' . $media->id, ['method' => 'put']) ?>
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="alt_text">Alt text (required)</label>
                <textarea id="alt_text" name="alt_text" required><?= esc($media->alt_text ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="caption">Caption</label>
                <input type="text" id="caption" name="caption" value="<?= esc($media->caption ?? '', 'attr') ?>">
            </div>

            <div class="form-group">
                <label for="credit">Credit</label>
                <input type="text" id="credit" name="credit" value="<?= esc($media->credit ?? '', 'attr') ?>">
            </div>

            <button type="submit" class="btn">Save changes</button>
        <?= form_close() ?>
    </div>
</div>

<?= $this->endSection() ?>
