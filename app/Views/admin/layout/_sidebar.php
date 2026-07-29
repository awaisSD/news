<?php
/**
 * Left nav for the admin shell. Items marked with a route path under
 * admin/review-queue, admin/topics, admin/generate, admin/image-jobs are
 * owned by a different agent's controllers (ReviewQueueController,
 * TopicsController, GenerationController, StylePassController,
 * ImageJobsController) — routes already exist in Routes.php, we just link
 * to them here.
 */
$currentPath = trim(current_url(true)->getPath(), '/');

$navSections = [
    [
        'items' => [
            ['label' => 'Dashboard', 'path' => 'admin'],
        ],
    ],
    [
        'heading' => 'Editorial',
        'items' => [
            ['label' => 'Articles', 'path' => 'admin/articles'],
            ['label' => 'Review Queue', 'path' => 'admin/review-queue'],
            ['label' => 'Topics', 'path' => 'admin/topics'],
            ['label' => 'Generate', 'path' => 'admin/generate'],
            ['label' => 'Image Jobs', 'path' => 'admin/image-jobs'],
        ],
    ],
    [
        'heading' => 'Content',
        'items' => [
            ['label' => 'Categories', 'path' => 'admin/categories'],
            ['label' => 'Tags', 'path' => 'admin/tags'],
            ['label' => 'Media', 'path' => 'admin/media'],
            ['label' => 'Pages', 'path' => 'admin/pages'],
        ],
    ],
    [
        'heading' => 'System',
        'items' => [
            ['label' => 'Users', 'path' => 'admin/users'],
            ['label' => 'Redirects', 'path' => 'admin/redirects'],
            ['label' => 'AI Settings', 'path' => 'admin/settings/ai'],
            ['label' => 'Audit Log', 'path' => 'admin/audit-log'],
        ],
    ],
];
?>
<aside class="admin-sidebar">
    <div class="brand">
        <img src="<?= esc(base_url('assets/tech-acts-new-logo-trimmed.png'), 'attr') ?>" alt="Tech Acts Admin" style="height:72px;width:auto;vertical-align:middle;">
    </div>
    <nav>
        <?php foreach ($navSections as $section): ?>
            <?php foreach ($section['items'] as $item): ?>
                <?php $active = $currentPath === $item['path'] || str_starts_with($currentPath . '/', $item['path'] . '/'); ?>
                <a href="<?= site_url($item['path']) ?>"<?= $active ? ' class="active"' : '' ?>><?= esc($item['label']) ?></a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
</aside>
