<?php require 'config.php';
if (user()) redirect('index.php');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  if (login_throttled()) { http_response_code(429); $error = 'Terlalu banyak percobaan. Tunggu 15 menit lalu coba kembali.'; }
  else {
    $stmt = db()->prepare('SELECT u.*, b.code branch_code, b.name branch_name FROM users u JOIN branches b ON b.id=u.branch_id WHERE u.username=? AND u.is_active=1');
    $stmt->execute([trim($_POST['username'] ?? '')]); $found = $stmt->fetch();
    if ($found && password_verify($_POST['password'] ?? '', $found['password_hash'])) {
      session_regenerate_id(true); clear_login_failures(); unset($found['password_hash']); $_SESSION['user']=$found; redirect('index.php');
    }
    record_login_failure(); $error = 'Username atau kata sandi salah.';
  }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login - Rumah Sakit</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="card login"><h1>RS Sehat Bersama</h1><p class="muted">Masuk ke sistem rekam medis.</p><?php if($error): ?><div class="alert error"><?=e($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label>Username</label><input name="username" required autofocus><label>Kata sandi</label><input type="password" name="password" required><p><button class="btn">Masuk</button></p></form></div></body></html>