<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect(home_for_role(current_user()['role']));
}

$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_check($_POST['csrf_token'] ?? null)) {
        $error = 'Oturum süresi doldu, tekrar deneyin.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $error = 'Kullanıcı adı ve şifre zorunludur.';
        } elseif (attempt_login($username, $password)) {
            redirect(home_for_role(current_user()['role']));
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Giriş · Kafe Yönetim Sistemi</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body class="login-body">
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-5 col-lg-4">
        <div class="card shadow-lg border-0">
          <div class="card-body p-4">
            <h1 class="h4 mb-1 text-center">Kafe Yönetim Sistemi</h1>
            <p class="text-muted text-center small mb-4">Personel girişi</p>

            <?php if ($error): ?>
              <div class="alert alert-danger py-2 small"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" autocomplete="off">
              <?= csrf_field() ?>
              <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" required autofocus>
              </div>
              <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <button class="btn btn-primary w-100">Giriş Yap</button>
            </form>

            <hr class="my-4">
            <p class="small text-muted mb-1">Demo hesaplar (şifre: <code>1234</code>)</p>
            <p class="small text-muted mb-0">admin · yonetici · garson · kasa · mutfak · depo</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
