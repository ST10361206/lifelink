<?php
require 'common.php';
if (!empty($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 if (!hash_equals(adminToken(), $_POST['csrf'] ?? '')) $error = 'Invalid request. Refresh and try again.';
 else {
  $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM admins WHERE email = ? LIMIT 1');
  $stmt->execute([trim($_POST['email'] ?? '')]); $admin = $stmt->fetch();
  if ($admin && password_verify($_POST['password'] ?? '', $admin['password_hash'])) {
   session_regenerate_id(true); $_SESSION['admin_id'] = (int)$admin['id']; $_SESSION['admin_name'] = $admin['full_name'];
   header('Location: dashboard.php'); exit;
  }
  $error = 'Incorrect email or password.';
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login | LifeLink</title><link rel="stylesheet" href="admin.css"></head><body><main class="login-wrap"><div class="login-card"><div class="brand">♥ LifeLink <span>Admin</span></div><h1>Administrator Login</h1><p>Sign in to manage LifeLink records.</p><?php if(isset($_GET['created'])): ?><div class="success">Account created. Sign in below, then delete admin/setup.php.</div><?php endif; ?><?php if($error): ?><div class="error"><?= adminEscape($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= adminEscape(adminToken()) ?>"><label>Email address<input name="email" type="email" autocomplete="username" required></label><label>Password<input name="password" type="password" autocomplete="current-password" required></label><button type="submit">Login</button></form><a class="back" href="../index.php">← Back to LifeLink website</a></div></main></body></html>
