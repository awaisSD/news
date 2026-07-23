<?php
/**
 * Deliberately does NOT extend admin/layout/main — the layout's sidebar/
 * topbar assume a logged-in user, and this page is reachable precisely
 * when there isn't one yet.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?= view('admin/layout/_head', ['title' => 'Log in']) ?>
</head>
<body>
<div style="max-width:360px;margin:80px auto;">
    <div class="card">
        <h1 class="section-title">News Admin</h1>
        <?= view('admin/layout/_flash') ?>
        <?= form_open('admin/login') ?>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus value="<?= esc(old('email') ?? '', 'attr') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log in</button>
        <?= form_close() ?>
    </div>
</div>
</body>
</html>
