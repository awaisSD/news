<?php
/**
 * Self-contained internal preview, deliberately NOT reusing the front-end
 * article template — that view expects JSON-LD/breadcrumb variables built
 * by Front\ArticleController that don't exist in this admin context. This
 * is just a clearly-labeled, best-effort render of headline/byline/dates/
 * featured-image/body_html/status so an editor can sanity-check content
 * before it goes live. Reachable only behind adminauth (no public route).
 */
?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:800px;margin:0 auto;">
    <p>
        <span class="badge badge-<?= esc($article->status) ?>">Preview — <?= esc(str_replace('_', ' ', $article->status)) ?></span>
        <span class="muted">This is an internal preview, not the live public page.</span>
    </p>

    <?php if ($media !== null): ?>
        <p><img src="<?= esc($media->getUrl()) ?>" alt="<?= esc($media->alt_text ?? '') ?>" style="max-width:100%;border-radius:6px;"></p>
        <?php if ($media->caption): ?><p class="muted"><?= esc($media->caption) ?></p><?php endif; ?>
    <?php endif; ?>

    <h1><?= esc($article->headline) ?></h1>
    <?php if ($article->subheadline): ?><p style="font-size:18px;color:#4b5563;"><?= esc($article->subheadline) ?></p><?php endif; ?>

    <p class="muted">
        By <?= $author ? esc($author->name) : 'Unknown author' ?>
        <?php if ($article->published_at): ?> &middot; Published <?= esc((string) $article->published_at) ?><?php endif; ?>
        <?php if ($article->updated_at_content): ?> &middot; Updated <?= esc((string) $article->updated_at_content) ?><?php endif; ?>
        &middot; <?= (int) $article->reading_time_minutes ?> min read
    </p>

    <hr>

    <div class="article-body">
        <?= $article->body_html ?>
    </div>
</div>

<?= $this->endSection() ?>
