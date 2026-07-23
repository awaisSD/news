<!DOCTYPE html>
<html lang="en">
<head>
<?= view('admin/layout/_head', ['title' => $title ?? null]) ?>
</head>
<body>
<div class="admin-shell">
    <?= view('admin/layout/_sidebar') ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <div>
                <?= isset($title) ? '<strong>' . esc($title) . '</strong>' : '' ?>
            </div>
            <div>
                <?php
                // Looked up directly here (rather than requiring every single
                // admin controller to remember to pass a currentUser variable)
                // so the top bar always renders correctly regardless of which
                // controller/agent authored the page.
                $topbarUser = session('user_id') ? model(\App\Models\UserModel::class)->find(session('user_id')) : null;
                ?>
                <?php if ($topbarUser !== null): ?>
                    <span class="muted"><?= esc($topbarUser->name) ?> (<?= esc($topbarUser->role) ?>)</span>
                    &nbsp;|&nbsp;
                    <a href="<?= site_url('admin/logout') ?>">Log out</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="admin-content">
            <?= view('admin/layout/_flash') ?>
            <?= $this->renderSection('content') ?>
        </div>
    </div>
</div>
</body>
</html>
