<?php
require 'includes/auth.php';
require 'includes/db.php';

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}
$donorId = (int) $_SESSION['donor_id'];
$message = '';
$error = '';

$stmt = $pdo->prepare('SELECT id, full_name, email, phone, blood_group, date_of_birth, created_at FROM donors WHERE id = ?');
$stmt->execute([$donorId]);
$donor = $stmt->fetch();
if (!$donor) {
    header('Location: logout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['profile_csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $phone = trim($_POST['phone'] ?? '');
        $bloodGroup = trim($_POST['blood_group'] ?? '');
        $dob = trim($_POST['date_of_birth'] ?? '');
        $groups = ['', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $validDate = true;
        if ($dob !== '') {
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $dob);
            $validDate = $parsed && $parsed->format('Y-m-d') === $dob && $dob <= date('Y-m-d');
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190 || $phone === '' || strlen($phone) > 30 || !in_array($bloodGroup, $groups, true) || !$validDate) {
            $error = 'Please check your name, email, phone number and optional profile details.';
        } else {
            $check = $pdo->prepare('SELECT id FROM donors WHERE email = ? AND id <> ?');
            $check->execute([$email, $donorId]);
            if ($check->fetch()) {
                $error = 'This email address is already used by another account.';
            } else {
                $save = $pdo->prepare('UPDATE donors SET full_name = ?, email = ?, phone = ?, blood_group = ?, date_of_birth = ? WHERE id = ?');
                $save->execute([$name, $email, $phone, $bloodGroup ?: null, $dob ?: null, $donorId]);
                $_SESSION['donor_name'] = $name;
                header('Location: profile.php?saved=1');
                exit;
            }
        }
        // Keep submitted values in the form if validation fails.
        $donor = array_merge($donor, ['full_name' => $name, 'email' => $email, 'phone' => $phone, 'blood_group' => $bloodGroup, 'date_of_birth' => $dob]);
    }
}
if (isset($_GET['saved'])) $message = 'Your profile has been updated successfully.';
$appt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE donor_id = ?');
$appt->execute([$donorId]);
$appointmentCount = (int) $appt->fetchColumn();
$pageTitle = 'My Profile';
include 'includes/header.php';
function profileText($value) { return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<section class="page-hero"><div class="container"><p class="eyebrow">DONOR PORTAL</p><h1>My Donor Profile</h1><p>Keep your contact details up to date and manage your LifeLink account.</p></div></section>
<section class="container section profile-layout">
  <aside class="profile-summary card">
    <div class="profile-avatar" aria-hidden="true"><?= profileText(strtoupper(substr($donor['full_name'], 0, 1))) ?></div>
    <h2><?= profileText($donor['full_name']) ?></h2>
    <p><?= profileText($donor['email']) ?></p>
    <div class="profile-stat"><strong><?= $appointmentCount ?></strong><span>Appointment request<?= $appointmentCount === 1 ? '' : 's' ?></span></div>
    <p class="fine-print">Member since <?= profileText(date('F Y', strtotime($donor['created_at']))) ?></p>
    <a class="btn btn-dark" href="appointments.php">View Appointments</a>
    <a class="profile-back" href="dashboard.php">← Back to Dashboard</a>
  </aside>
  <div class="profile-editor card">
    <h2>Personal Information</h2>
    <p>Update your details below. Fields marked * are required.</p>
    <?php if ($message): ?><p class="success" role="status"><?= profileText($message) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error" role="alert"><?= profileText($error) ?></p><?php endif; ?>
    <form method="post" action="profile.php">
      <input type="hidden" name="csrf" value="<?= profileText($_SESSION['profile_csrf']) ?>">
      <div class="profile-fields">
        <div><label for="full_name">Full name *</label><input id="full_name" name="full_name" maxlength="120" required value="<?= profileText($donor['full_name']) ?>"></div>
        <div><label for="email">Email address *</label><input id="email" name="email" type="email" maxlength="190" required value="<?= profileText($donor['email']) ?>"></div>
        <div><label for="phone">Phone number *</label><input id="phone" name="phone" type="tel" maxlength="30" required value="<?= profileText($donor['phone']) ?>"></div>
        <div><label for="date_of_birth">Date of birth (optional)</label><input id="date_of_birth" name="date_of_birth" type="date" max="<?= date('Y-m-d') ?>" value="<?= profileText($donor['date_of_birth']) ?>"></div>
        <div><label for="blood_group">Blood group (optional)</label><select id="blood_group" name="blood_group"><option value="">Not provided</option><?php foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $group): ?><option value="<?= profileText($group) ?>" <?= ($donor['blood_group'] === $group) ? 'selected' : '' ?>><?= profileText($group) ?></option><?php endforeach; ?></select></div>
      </div>
      <p class="fine-print">Only enter a blood group if it has been confirmed by a healthcare professional. LifeLink does not determine blood donation eligibility.</p>
      <button class="btn" type="submit">Save Changes</button>
    </form>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
