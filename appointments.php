<?php
require 'includes/auth.php';
require 'includes/db.php';
$errors = [];
$success = '';
$today = date('Y-m-d');
$slots = ['08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00'];
$centres = $pdo->query('SELECT id, name, address FROM centres ORDER BY name')->fetchAll();
$centreIds = array_map('intval', array_column($centres, 'id'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $centre = filter_input(INPUT_POST, 'centre_id', FILTER_VALIDATE_INT);
    $date = trim($_POST['appointment_date'] ?? '');
    $time = trim($_POST['appointment_time'] ?? '');
    $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if (!$centre || !in_array($centre, $centreIds, true)) $errors[] = 'Please choose a donation centre.';
    if (!$validDate || $validDate->format('Y-m-d') !== $date || $date < $today) $errors[] = 'Please choose today or a future date.';
    if (!in_array($time, $slots, true)) $errors[] = 'Please choose one of the available time options.';
    if (!$errors && $date === $today && $time <= date('H:i')) $errors[] = 'Please select a time that has not passed.';
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE donor_id = ? AND appointment_date = ? AND appointment_time = ? AND status <> 'Cancelled'");
        $stmt->execute([$_SESSION['donor_id'], $date, $time]);
        if ($stmt->fetchColumn() > 0) $errors[] = 'You already have an appointment at this date and time.';
        else {
            $stmt = $pdo->prepare('INSERT INTO appointments (donor_id, centre_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)');
            $stmt->execute([$_SESSION['donor_id'], $centre, $date, $time]);
            $success = 'Your appointment request has been saved. Status: Pending.';
        }
    }
}
$stmt = $pdo->prepare('SELECT a.appointment_date, a.appointment_time, a.status, c.name AS centre_name FROM appointments a JOIN centres c ON c.id = a.centre_id WHERE a.donor_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC');
$stmt->execute([$_SESSION['donor_id']]);
$appointments = $stmt->fetchAll();
$pageTitle = 'Appointments';
include 'includes/header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow">DONOR PORTAL</span><h1>Book a Blood Donation</h1><p>Choose your preferred donation centre, date and time. Your request will be saved so you can track it below.</p></div></section>
<section class="container section appointment-layout">
<div class="appointment-panel">
<h2>Choose your appointment</h2>
<p class="fine-print">Fields marked * are required. Times are in South African local time (SAST).</p>
<?php foreach ($errors as $error): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
<?php if ($success): ?><p class="success" role="status"><?= htmlspecialchars($success) ?></p><?php endif; ?>
<?php if (!$centres): ?>
<p class="error">No donation centres have been added to the booking database yet. Add centres in phpMyAdmin before requesting an appointment.</p>
<?php else: ?>
<form method="post" id="booking-form">
<label for="centre_id">Donation centre *</label>
<select name="centre_id" id="centre_id" required><option value="">Select a centre</option>
<?php foreach ($centres as $centre): ?><option value="<?= (int)$centre['id'] ?>" <?= (string)($_POST['centre_id'] ?? '') === (string)$centre['id'] ? 'selected' : '' ?>><?= htmlspecialchars($centre['name']) ?> — <?= htmlspecialchars($centre['address']) ?></option><?php endforeach; ?>
</select>
<div class="booking-fields"><div><label for="appointment_date">Preferred date *</label><input type="date" name="appointment_date" id="appointment_date" min="<?= $today ?>" value="<?= htmlspecialchars($_POST['appointment_date'] ?? '') ?>" required></div>
<div><label for="appointment_time">Preferred time *</label><select name="appointment_time" id="appointment_time" required><option value="">Select a time</option><?php foreach ($slots as $slot): ?><option value="<?= $slot ?>" <?= ($_POST['appointment_time'] ?? '') === $slot ? 'selected' : '' ?>><?= $slot ?></option><?php endforeach; ?></select></div></div>
<p class="fine-print">These are request times, not live availability. A pending booking is not a confirmed appointment.</p>
<button class="btn full" type="submit">Request Appointment</button>
</form>
<?php endif; ?>
</div>
<aside class="appointment-help"><div class="icon-bubble">♥</div><h3>Before you donate</h3><p>Eat a balanced meal, drink water and bring your identification. Donation staff will check your eligibility at the centre.</p><h3>What happens next?</h3><p>Your request appears under My Appointments with a <strong>Pending</strong> status. Contact the centre directly to verify the appointment before travelling.</p><a href="centres.php" class="text-link">Browse donation centres →</a></aside>
</section>
<section class="container appointment-history"><h2>My Appointments</h2>
<?php if (!$appointments): ?><p>You have not requested any appointments yet.</p>
<?php else: ?><div class="table-wrap"><table><thead><tr><th>Donation Centre</th><th>Date</th><th>Time (SAST)</th><th>Status</th></tr></thead><tbody>
<?php foreach ($appointments as $item): ?><tr><td><?= htmlspecialchars($item['centre_name']) ?></td><td><?= htmlspecialchars(date('d M Y', strtotime($item['appointment_date']))) ?></td><td><?= htmlspecialchars(substr($item['appointment_time'],0,5)) ?></td><td><span class="appointment-status"><?= htmlspecialchars($item['status']) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section>
<?php include 'includes/footer.php'; ?>
