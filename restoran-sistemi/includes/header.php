<?php
require_once __DIR__ . '/auth.php';
require_login();
$__user = current_user();
$__menu = menu_for_role($__user['role']);
$__title = $__title ?? 'Kafe Yönetim Sistemi';
$__current = $_SERVER['PHP_SELF'] ?? '';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($__title) ?> · <?= e(setting('cafe_name', 'Kafe')) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
  <script>window.BASE_URL = <?= json_encode(BASE_URL) ?>; window.CSRF_TOKEN = <?= json_encode(csrf_token()) ?>;</script>
</head>
<body>
<nav class="navbar navbar-expand-lg app-navbar sticky-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= e(url(home_for_role($__user['role']))) ?>">
      <?= e(setting('cafe_name', 'Kafe')) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto flex-wrap">
        <?php foreach ($__menu as $item): ?>
          <li class="nav-item">
            <a class="nav-link <?= str_ends_with($__current, ltrim($item[1], '/')) ? 'active' : '' ?>"
               href="<?= e(url($item[1])) ?>"><?= e($item[0]) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>
      <span class="navbar-text me-3 small">
        <?= e($__user['full_name']) ?> · <span class="badge text-bg-secondary"><?= e($__user['role']) ?></span>
      </span>
      <a class="btn btn-sm btn-outline-light" href="<?= e(url('/logout.php')) ?>">Çıkış</a>
    </div>
  </div>
</nav>
<main class="container-fluid py-4">
