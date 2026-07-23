<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card">
    <h2 class="section-title">Upload image</h2>
    <?= form_open_multipart('admin/media') ?>
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="image">Image file</label>
            <input type="file" id="image" name="image" accept="image/*" required>
        </div>
        <div class="form-group">
            <label for="alt_text">Alt text</label>
            <input type="text" id="alt_text" name="alt_text" placeholder="Describe the image for screen readers and search">
        </div>
        <div class="form-group">
            <label for="caption">Caption</label>
            <input type="text" id="caption" name="caption">
        </div>
        <div class="form-group">
            <label for="credit">Credit</label>
            <input type="text" id="credit" name="credit">
        </div>
        <button type="submit" class="btn">Upload</button>
    <?= form_close() ?>
</div>

<div class="card">
    <h2 class="section-title">Library</h2>
    <div class="media-grid">
        <?php foreach ($media as $item): ?>
            <figure>
                <img src="<?= esc($item->getUrl()) ?>" alt="<?= esc($item->alt_text ?? '') ?>">
                <figcaption>
                    #<?= (int) $item->id ?><br>
                    <?= esc($item->alt_text ?? '(no alt text)') ?><br>
                    <a href="<?= site_url('admin/media/' . $item->id . '/edit') ?>">Edit</a>
                    &nbsp;
                    <?= form_open('admin/media/' . $item->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this media item?');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
        <?php if ($media === []): ?>
            <p class="muted">No media uploaded yet.</p>
        <?php endif; ?>
    </div>

    <div class="pager">
        <?= $pager->links() ?>
    </div>
</div>

<?= $this->endSection() ?>
