<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
    <div></div>
    <a class="btn" href="<?= site_url('admin/articles/create') ?>">New article</a>
</div>

<div class="card">
    <?= form_open('admin/articles', ['method' => 'get', 'class' => 'filters-bar']) ?>
        <div class="form-group">
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="">All</option>
                <?php foreach (['draft', 'in_review', 'changes_requested', 'approved', 'published', 'corrected', 'rejected', 'retracted'] as $s): ?>
                    <option value="<?= esc($s, 'attr') ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= esc(str_replace('_', ' ', $s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="primary_category_id">Category</label>
            <select name="primary_category_id" id="primary_category_id">
                <option value="">All</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= (string) $filters['primary_category_id'] === (string) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['label']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="author_id">Author</label>
            <select name="author_id" id="author_id">
                <option value="">All</option>
                <?php foreach ($authors as $author): ?>
                    <option value="<?= (int) $author->id ?>" <?= (string) $filters['author_id'] === (string) $author->id ? 'selected' : '' ?>><?= esc($author->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-secondary">Filter</button>
        </div>
    <?= form_close() ?>
</div>

<div class="card">
    <table class="admin-table">
        <thead>
        <tr>
            <th>Headline</th>
            <th>Status</th>
            <th>Author</th>
            <th>Updated</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $article): ?>
            <tr>
                <td><?= esc($article->headline) ?></td>
                <td><span class="badge badge-<?= esc($article->status) ?>"><?= esc(str_replace('_', ' ', $article->status)) ?></span></td>
                <td>
                    <?php
                    $author = null;
                    foreach ($authors as $candidate) {
                        if ($candidate->id === $article->author_id) {
                            $author = $candidate;
                            break;
                        }
                    }
                    ?>
                    <?= $author ? esc($author->name) : '—' ?>
                </td>
                <td><?= esc((string) $article->updated_at) ?></td>
                <td>
                    <a href="<?= site_url('admin/articles/' . $article->id . '/edit') ?>">Edit</a>
                    &nbsp;|&nbsp;
                    <a href="<?= site_url('admin/articles/' . $article->id . '/preview') ?>">Preview</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($articles === []): ?>
            <tr><td colspan="5" class="muted">No articles match these filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="pager">
        <?= $pager->links() ?>
    </div>
</div>

<?= $this->endSection() ?>
