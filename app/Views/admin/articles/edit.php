<?php
/** @var App\Entities\Article $article */
$isNew       = $article->id === null;
$isPublished = $article->status === 'published';
$canReview   = in_array($currentRole, ['editor', 'admin'], true);
?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="grid-2">
    <div>
        <div class="card">
            <h2 class="section-title">Content</h2>

            <?php if ($isPublished): ?>
                <p class="flash flash-error">
                    This article is published. Headline/body are read-only here — use
                    <strong>Record Correction</strong> (right column) to change them, so every
                    change to a live article stays in the audit trail.
                </p>
            <?php endif; ?>

            <?= form_open($isNew ? 'admin/articles' : 'admin/articles/' . $article->id) ?>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="headline">Headline</label>
                    <input type="text" id="headline" name="headline" required maxlength="255"
                           value="<?= esc($article->headline ?? '', 'attr') ?>" <?= $isPublished ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="subheadline">Subheadline</label>
                    <input type="text" id="subheadline" name="subheadline" maxlength="255"
                           value="<?= esc($article->subheadline ?? '', 'attr') ?>" <?= $isPublished ? 'readonly' : '' ?>>
                </div>

                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" <?= $isPublished ? 'readonly' : '' ?>><?= esc($article->excerpt ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="body_html">Body (HTML)</label>
                    <textarea id="body_html" name="body_html" class="body-html" required <?= $isPublished ? 'readonly' : '' ?>><?= esc($article->body_html ?? '') ?></textarea>
                    <p class="hint">Raw HTML. A rich WYSIWYG editor can be layered on later without changing this form's field name.</p>
                </div>

                <div class="form-group">
                    <label for="featured_media_id">Featured image</label>
                    <select id="featured_media_id" name="featured_media_id" <?= $isPublished ? 'disabled' : '' ?>>
                        <option value="">— none —</option>
                        <?php foreach ($media as $item): ?>
                            <option value="<?= (int) $item->id ?>" <?= (int) $article->featured_media_id === (int) $item->id ? 'selected' : '' ?>>
                                #<?= (int) $item->id ?> — <?= esc($item->alt_text ?? '(no alt text)') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><input type="checkbox" name="is_breaking" value="1" <?= $article->is_breaking ? 'checked' : '' ?> <?= $isPublished ? 'disabled' : '' ?>> Breaking news</label>
                </div>

                <h2 class="section-title">Taxonomy</h2>

                <div class="form-group">
                    <label for="primary_category_id">Primary category</label>
                    <select id="primary_category_id" name="primary_category_id" required <?= $isPublished ? 'disabled' : '' ?>>
                        <option value="">— select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= (int) $article->primary_category_id === (int) $cat['id'] ? 'selected' : '' ?>><?= esc($cat['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="category_ids">Secondary categories</label>
                    <select id="category_ids" name="category_ids[]" multiple size="5" <?= $isPublished ? 'disabled' : '' ?>>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= in_array((int) $cat['id'], $selectedCategoryIds, true) ? 'selected' : '' ?>><?= esc($cat['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tag_ids">Tags</label>
                    <select id="tag_ids" name="tag_ids[]" multiple size="5" <?= $isPublished ? 'disabled' : '' ?>>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?= (int) $tag->id ?>" <?= in_array((int) $tag->id, $selectedTagIds, true) ? 'selected' : '' ?>><?= esc($tag->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="assigned_editor_id">Assigned editor</label>
                    <select id="assigned_editor_id" name="assigned_editor_id" <?= $isPublished ? 'disabled' : '' ?>>
                        <option value="">— none —</option>
                        <?php foreach ($editors as $editor): ?>
                            <option value="<?= (int) $editor->id ?>" <?= (int) $article->assigned_editor_id === (int) $editor->id ? 'selected' : '' ?>><?= esc($editor->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <h2 class="section-title">SEO</h2>

                <div class="form-group">
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" maxlength="255" value="<?= esc($article->meta_title ?? '', 'attr') ?>">
                </div>

                <div class="form-group">
                    <label for="meta_description">Meta description</label>
                    <textarea id="meta_description" name="meta_description"><?= esc($article->meta_description ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="canonical_url">Canonical URL</label>
                    <input type="url" id="canonical_url" name="canonical_url" value="<?= esc($article->canonical_url ?? '', 'attr') ?>">
                </div>

                <?php if (! $isPublished): ?>
                    <button type="submit" class="btn"><?= $isNew ? 'Create article' : 'Save changes' ?></button>
                <?php endif; ?>
            <?= form_close() ?>
        </div>
    </div>

    <div>
        <div class="card">
            <h2 class="section-title">Publishing</h2>
            <p>Status: <span class="badge badge-<?= esc($article->status ?? 'draft') ?>"><?= esc(str_replace('_', ' ', $article->status ?? 'draft')) ?></span></p>

            <?php if ($isNew): ?>
                <p class="muted">Save the article as a draft first; workflow actions appear here afterwards.</p>
            <?php else: ?>

                <?php if (in_array($article->status, ['draft', 'changes_requested'], true)): ?>
                    <?= form_open('admin/articles/' . $article->id . '/submit-review', ['class' => 'inline-form']) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-small">Submit for review</button>
                    <?= form_close() ?>
                <?php endif; ?>

                <?php if ($article->status === 'in_review' && $canReview): ?>
                    <?= form_open('admin/articles/' . $article->id . '/request-changes', ['class' => 'form-group']) ?>
                        <?= csrf_field() ?>
                        <label for="request_changes_note">Request changes — note</label>
                        <textarea id="request_changes_note" name="note" required></textarea>
                        <button type="submit" class="btn btn-warning btn-small">Request changes</button>
                    <?= form_close() ?>

                    <?= form_open('admin/articles/' . $article->id . '/reject', ['class' => 'form-group']) ?>
                        <?= csrf_field() ?>
                        <label for="reject_note">Reject — note</label>
                        <textarea id="reject_note" name="note" required></textarea>
                        <button type="submit" class="btn btn-danger btn-small">Reject</button>
                    <?= form_close() ?>

                    <?= form_open('admin/articles/' . $article->id . '/approve', ['class' => 'inline-form']) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success btn-small">Approve</button>
                    <?= form_close() ?>
                <?php endif; ?>

                <?php if ($article->status === 'approved' && $canReview): ?>
                    <?= form_open('admin/articles/' . $article->id . '/publish', ['class' => 'inline-form']) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success btn-small">Publish</button>
                    <?= form_close() ?>
                <?php endif; ?>

                <?php if (in_array($article->status, ['in_review', 'changes_requested', 'approved', 'published'], true)): ?>
                    <p><a href="<?= site_url('admin/articles/' . $article->id . '/preview') ?>">Preview &rarr;</a></p>
                <?php endif; ?>

                <p><a href="<?= site_url('admin/revisions/' . $article->id) ?>">View revision history &rarr;</a></p>

                <?php if ($currentRole === 'admin'): ?>
                    <?= form_open('admin/articles/' . $article->id . '/delete', ['method' => 'post', 'class' => 'inline-form', 'onsubmit' => "return confirm('Delete this article?');"]) ?>
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    <?= form_close() ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($isPublished && $canReview): ?>
            <div class="card">
                <h2 class="section-title">Record correction</h2>
                <p class="muted">The only way to change a published article's headline/body. Substantive corrections require a public-facing note.</p>
                <?= form_open('admin/articles/' . $article->id . '/correct') ?>
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="correction_headline">Headline</label>
                        <input type="text" id="correction_headline" name="headline" required maxlength="255" value="<?= esc($article->headline, 'attr') ?>">
                    </div>
                    <div class="form-group">
                        <label for="correction_body_html">Body (HTML)</label>
                        <textarea id="correction_body_html" name="body_html" class="body-html" required><?= esc($article->body_html) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="is_substantive" value="1" id="is_substantive"> This is a substantive correction (requires a public correction note)</label>
                    </div>
                    <div class="form-group">
                        <label for="correction_note">Correction note</label>
                        <textarea id="correction_note" name="correction_note"></textarea>
                    </div>
                    <button type="submit" class="btn">Record correction</button>
                <?= form_close() ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
