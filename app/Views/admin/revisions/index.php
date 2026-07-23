<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<p><a href="<?= site_url('admin/articles/' . $article->id . '/edit') ?>">&larr; Back to article</a></p>

<?php if ($revisions === []): ?>
    <div class="card"><p class="muted">No revisions recorded for this article yet. A revision is created every time a correction is made or a previous version is restored.</p></div>
<?php endif; ?>

<?php foreach ($revisions as $revision): ?>
    <div class="card">
        <p>
            <strong><?= esc($revision->headline) ?></strong>
            &nbsp;<span class="badge badge-<?= esc($revision->status_at_revision) ?>"><?= esc(str_replace('_', ' ', $revision->status_at_revision)) ?></span>
            <?php if ($revision->is_substantive): ?><span class="badge">substantive</span><?php endif; ?>
        </p>
        <p class="muted">
            Snapshot taken <?= esc((string) $revision->created_at) ?>
            by <?= esc($editorNames[$revision->editor_id] ?? ('#' . $revision->editor_id)) ?>
        </p>
        <?php if ($revision->correction_note): ?>
            <p><em><?= esc($revision->correction_note) ?></em></p>
        <?php endif; ?>

        <?php $diff = $diffs[$revision->id] ?? ['added' => [], 'removed' => []]; ?>
        <?php if ($diff['added'] !== [] || $diff['removed'] !== []): ?>
            <details>
                <summary>What changed after this snapshot (naive line diff)</summary>
                <?php if ($diff['removed'] !== []): ?>
                    <p class="muted">Removed:</p>
                    <ul>
                        <?php foreach ($diff['removed'] as $line): ?>
                            <li style="color:#991b1b;">&minus; <?= esc($line) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($diff['added'] !== []): ?>
                    <p class="muted">Added:</p>
                    <ul>
                        <?php foreach ($diff['added'] as $line): ?>
                            <li style="color:#166534;">+ <?= esc($line) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </details>
        <?php endif; ?>

        <details>
            <summary>Full body_html snapshot</summary>
            <pre class="json-block"><?= esc($revision->body_html) ?></pre>
        </details>

        <?= form_open('admin/revisions/' . $revision->id . '/restore', ['onsubmit' => "return confirm('Restore article content to this snapshot?');"]) ?>
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary btn-small">Restore this version</button>
        <?= form_close() ?>
    </div>
<?php endforeach; ?>

<?= $this->endSection() ?>
