<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<h1>Review Queue</h1>
<p>Articles waiting for editorial decision, oldest first. Nothing here can reach readers until an editor approves and separately publishes it.</p>

<?php if ($queue === []): ?>
    <p><em>The queue is empty — nothing is waiting on review right now.</em></p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Headline</th>
                <th>Category</th>
                <th>Author</th>
                <th>Assigned editor</th>
                <th>Status</th>
                <th>Waiting</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($queue as $article): ?>
                <tr>
                    <td><?= esc($article->headline) ?></td>
                    <td><?= isset($categoriesById[$article->primary_category_id]) ? esc($categoriesById[$article->primary_category_id]->name) : '—' ?></td>
                    <td><?= isset($usersById[$article->author_id]) ? esc($usersById[$article->author_id]->name) : '—' ?></td>
                    <td><?= isset($usersById[$article->assigned_editor_id]) ? esc($usersById[$article->assigned_editor_id]->name) : '—' ?></td>
                    <td><span class="badge badge-<?= esc($article->status, 'attr') ?>"><?= esc(str_replace('_', ' ', $article->status)) ?></span></td>
                    <td><?= esc($waitingFor[$article->id] ?? '—') ?></td>
                    <td><a href="<?= site_url('admin/review-queue/' . $article->id) ?>">Review</a></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>

<?= $this->endSection() ?>
