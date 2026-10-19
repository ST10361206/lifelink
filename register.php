<?php
session_start(); require 'includes/db.php';
if (isset($_SESSION['donor_id'])) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone || strlen($password) < 8) {
        $error = 'Complete all fields and use a password of at least 8 characters.';
    } else {
        $check = $pdo->prepare('SELECT id FROM donors WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) { $error = 'This email is already registered.'; }
        else {
            $insert = $pdo->prepare('INSERT INTO donors (full_name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
            $insert->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['donor_id'] = (int)$pdo->lastInsertId();
            $_SESSION['donor_name'] = $name;
            header('Location: dashboard.php'); exit;
        }
    }
}
$pageTitle='Register'; include 'includes/header.php';
?>
<section class="container section"><div class="form-card"><h1>Donor Registration</h1><p>Create your LifeLink account.</p><?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?><form method="post"><label for="full_name">Full Name</label><input id="full_name" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required><label for="email">Email Address</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required><label for="phone">Phone Number</label><input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required><label for="password">Password (at least 8 characters)</label><input type="password" id="password" name="password" minlength="8" required><button class="btn full" type="submit">Register</button></form><p class="small">Already have an account? <a href="login.php">Login</a></p></div></section>
<?php include 'includes/footer.php'; ?>
