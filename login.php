<?php
session_start(); require 'includes/db.php';
if (isset($_SESSION['donor_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $stmt = $pdo->prepare('SELECT id, full_name, password_hash FROM donors WHERE email = ?');
    $stmt->execute([$email]);
    $donor = $stmt->fetch();
    if ($donor && password_verify($_POST['password'] ?? '', $donor['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['donor_id'] = (int)$donor['id'];
        $_SESSION['donor_name'] = $donor['full_name'];
        header('Location: dashboard.php'); exit;
    }
    $error = 'Incorrect email or password.';
}
$pageTitle='Login'; include 'includes/header.php';
?>
<section class="container section"><div class="form-card"><h1>Donor Login</h1><p>Welcome back to LifeLink.</p><?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?><form method="post"><label for="email">Email Address</label><input type="email" id="email" name="email" required><label for="password">Password</label><input type="password" id="password" name="password" required><button class="btn full" type="submit">Login</button></form><p class="small">Don't have an account? <a href="register.php">Register here</a></p></div></section>
<?php include 'includes/footer.php'; ?>
