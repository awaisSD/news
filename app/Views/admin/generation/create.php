<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<h1>Generate a draft</h1>

<p>
    <strong><?= (int) $usedToday ?> / <?= (int) $cap ?></strong> generations used today.
    <?php if ($capReached): ?>
        <br><strong style="color:#a30000;">Daily generation cap reached.</strong>
        This exists so AI drafting volume never outpaces genuine human editorial review capacity —
        ask an admin to raise the cap in AI Settings, or wait until tomorrow.
    <?php endif ?>
</p>

<?php if ($topics === []): ?>
    <p><em>No topics are ready for generation (status must be "new" or "assigned"). <a href="<?= site_url('admin/topics/new') ?>">Create a topic</a> first.</em></p>
<?php else: ?>
    <?= form_open('admin/generate') ?>
        <?= csrf_field() ?>

        <p>
            <label for="topic_id">Topic</label><br>
            <select id="topic_id" name="topic_id" required>
                <option value="">— Choose a topic —</option>
                <?php $preselectedTopicId = old('topic_id', (string) (service('request')->getGet('topic_id') ?? '')); ?>
                <?php foreach ($topics as $topic): ?>
                    <option value="<?= (int) $topic->id ?>" <?= $preselectedTopicId !== '' && (int) $preselectedTopicId === $topic->id ? 'selected' : '' ?>>
                        <?= esc($topic->title) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </p>

        <button type="submit" <?= $capReached ? 'disabled' : '' ?>>Queue draft generation</button>
    <?= form_close() ?>
<?php endif ?>

<?= $this->endSection() ?>
