<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    .diff-block { font-family: monospace; white-space: pre-wrap; padding: 0.5rem; }
    .diff-block del { background: #ffe3e3; color: #a30000; text-decoration: line-through; display: block; }
    .diff-block ins { background: #e3ffe6; color: #036b0a; text-decoration: none; display: block; }
    .not-actionable-banner { background: #fff3cd; border: 1px solid #ffe08a; padding: 0.75rem; margin-bottom: 1rem; }
</style>

<h1><?= esc($article->headline) ?></h1>

<?php if (! $isActionable): ?>
    <div class="not-actionable-banner">
        <strong>Not currently actionable.</strong>
        This article's status is <strong><?= esc(str_replace('_', ' ', $article->status)) ?></strong>, not
        "in review" or "changes requested" — you're viewing it for context only.
    </div>
<?php endif ?>

<p>
    <span class="badge badge-<?= esc($article->status, 'attr') ?>"><?= esc(str_replace('_', ' ', $article->status)) ?></span>
    <?php if ($article->ai_assisted): ?><span class="badge badge-ai">AI-assisted draft</span><?php endif ?>
</p>

<dl>
    <dt>Category</dt>
    <dd><?= $category !== null ? esc($category->name) : '—' ?></dd>
    <dt>Author</dt>
    <dd><?= $author !== null ? esc($author->name) : '—' ?></dd>
    <dt>Assigned editor</dt>
    <dd><?= $assignedEditor !== null ? esc($assignedEditor->name) : '—' ?></dd>
</dl>

<h2>Excerpt</h2>
<p><?= esc($article->excerpt) ?></p>

<h2>Full article</h2>
<article>
    <?= $article->body_html ?>
</article>

<?php if ($previousRevision !== null && ($diff['added'] !== [] || $diff['removed'] !== [])): ?>
    <h2>What changed since the last revision</h2>
    <p><em>Naive line-based diff against the previous revision snapshot (recorded <?= esc((string) $previousRevision->created_at) ?>). TODO: swap in jfcherng/php-diff for real word-level diff highlighting.</em></p>
    <div class="diff-block">
        <?php foreach ($diff['removed'] as $line): ?>
            <del><?= esc($line) ?></del>
        <?php endforeach ?>
        <?php foreach ($diff['added'] as $line): ?>
            <ins><?= esc($line) ?></ins>
        <?php endforeach ?>
    </div>
<?php endif ?>

<h2>Editorial history</h2>
<?php if ($history === []): ?>
    <p><em>No review actions recorded yet.</em></p>
<?php else: ?>
    <ul>
        <?php foreach ($history as $log): ?>
            <li>
                <strong><?= esc(str_replace('_', ' ', $log->action)) ?></strong>
                by <?= isset($reviewersById[$log->reviewer_id]) && $reviewersById[$log->reviewer_id] !== null ? esc($reviewersById[$log->reviewer_id]->name) : 'user #' . (int) $log->reviewer_id ?>
                on <?= esc((string) $log->created_at) ?>
                <?php if (! empty($log->notes)): ?>
                    — <em><?= esc($log->notes) ?></em>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ul>
<?php endif ?>

<h2>AI assist tools</h2>
<p>
    <a href="<?= site_url('admin/articles/' . $article->id . '/style-pass') ?>">Style / readability pass</a>
    &nbsp;|&nbsp;
    <a href="<?= site_url('admin/image-jobs') ?>?article_id=<?= (int) $article->id ?>">Image jobs for this article</a>
</p>

<?php if ($isActionable): ?>
    <h2>Decision</h2>

    <form method="post" action="<?= site_url('admin/articles/' . $article->id . '/approve') ?>" style="display:inline-block; margin-right: 1rem;">
        <?= csrf_field() ?>
        <button type="submit">Approve</button>
    </form>

    <details style="display:inline-block;">
        <summary>Request changes</summary>
        <form method="post" action="<?= site_url('admin/articles/' . $article->id . '/request-changes') ?>">
            <?= csrf_field() ?>
            <label for="request-changes-note">What needs to change (required)</label><br>
            <textarea id="request-changes-note" name="note" rows="3" required></textarea><br>
            <button type="submit">Send back for changes</button>
        </form>
    </details>

    <details style="display:inline-block; margin-left: 1rem;">
        <summary>Reject</summary>
        <form method="post" action="<?= site_url('admin/articles/' . $article->id . '/reject') ?>">
            <?= csrf_field() ?>
            <label for="reject-note">Reason for rejection (required)</label><br>
            <textarea id="reject-note" name="note" rows="3" required></textarea><br>
            <button type="submit">Reject</button>
        </form>
    </details>
<?php endif ?>

<?= $this->endSection() ?>
