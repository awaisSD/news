<!doctype html>
<html lang="<?= esc(config(\Config\Publisher::class)->newsLanguage) ?>">
<head>
<?= $this->include('front/layout/_head.php') ?>
</head>
<body>
<?= $this->include('front/layout/_header.php') ?>
<main class="site-main">
<?= $this->renderSection('content') ?>
</main>
<?= $this->include('front/layout/_footer.php') ?>
</body>
</html>
