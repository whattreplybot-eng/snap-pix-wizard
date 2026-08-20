<?php require_once __DIR__ . '/functions.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Yetkisiz Erişim</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body class="bg-light">
  <div class="container py-5 text-center">
    <h1 class="display-5">403</h1>
    <p class="lead">Bu sayfayı görüntüleme yetkiniz bulunmuyor.</p>
    <a class="btn btn-primary" href="<?= e(url('/index.php')) ?>">Ana Sayfa</a>
  </div>
</body>
</html>
