<?php
require 'common.php';
adminGuard();
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';
    if (!is_string($token) || !hash_equals(adminToken(), $token)) {
        $error = 'The form expired. Refresh the page and try again.';
    } elseif (!$id || !in_array($action, ['Confirmed', 'Cancelled'], true)) {
        $error = 'Invalid appointment action.';
    } else {
        $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ? AND status = 'Pending'");
        $stmt->execute([$action, $id]);
        if ($stmt->rowCount() === 1) {
            $_SESSION['appointment_flash'] = 'Appointment #' . $id . ' marked as ' . strtolower($action) . '.';
            header('Location: appointments.php');
            exit;
        }
        $error = 'This appointment is no longer pending or does not exist.';
    }
}
if (isset($_SESSION['appointment_flash'])) {
    $message = $_SESSION['appointment_flash'];
    unset($_SESSION['appointment_flash']);
}
$filter = $_GET['status'] ?? 'All';
if (!in_array($filter, ['All', 'Pending', 'Confirmed', 'Cancelled'], true)) $filter = 'All';
$sql = 'SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.created_at, d.full_name, d.email, d.phone, c.name AS centre_name FROM appointments a JOIN donors d ON d.id = a.donor_id JOIN centres c ON c.id = a.centre_id';
$params = [];
if ($filter !== 'All') { $sql .= ' WHERE a.status = ?'; $params[] = $filter; }
$sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Manage Appointments | LifeLink Admin</title><link rel="stylesheet" href="admin.css"></head>
<body><header class="top"><div class="top-inner"><a class="brand" href="dashboard.php">♥ LifeLink <span>Admin</span></a><nav><a href="dashboard.php">Dashboard</a><a href="appointments.php">Appointments</a><a href="donors.php">Donors</a><a href="logout.php">Logout</a></nav></div></header>
<main class="content"><div class="intro"><span class="eyebrow">ADMINISTRATOR PORTAL</span><h1>Manage Appointments</h1><p>Review donation appointment requests and update their status.</p></div>
<?php if ($message): ?><p class="success" role="status"><?= adminEscape($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="error" role="alert"><?= adminEscape($error) ?></p><?php endif; ?>
<section class="panel"><div class="panel-head"><h2>Appointment requests</h2><form method="get" class="status-filter"><label for="status">Filter by status</label><select id="status" name="status" onchange="this.form.submit()"><option value="All" <?= $filter === 'All' ? 'selected' : '' ?>>All</option><?php foreach (['Pending','Confirmed','Cancelled'] as $status): ?><option value="<?= $status ?>" <?= $filter === $status ? 'selected' : '' ?>><?= $status ?></option><?php endforeach; ?></select><noscript><button type="submit">Filter</button></noscript></form></div>
<?php if (!$appointments): ?><p class="empty">No appointments match this filter.</p><?php else: ?><div class="table-scroll"><table><thead><tr><th>Donor</th><th>Centre</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($appointments as $a): ?><tr><td><strong><?= adminEscape($a['full_name']) ?></strong><div class="cell-detail"><?= adminEscape($a['email']) ?></div><div class="cell-detail"><?= adminEscape($a['phone']) ?></div></td><td><?= adminEscape($a['centre_name']) ?></td><td><?= adminEscape($a['appointment_date']) ?></td><td><?= adminEscape(substr($a['appointment_time'],0,5)) ?></td><td><span class="pill <?= strtolower(adminEscape($a['status'])) ?>"><?= adminEscape($a['status']) ?></span></td><td>
<?php if ($a['status'] === 'Pending'): ?><div class="action-row"><form method="post"><input type="hidden" name="csrf_token" value="<?= adminEscape(adminToken()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>"><button class="small-btn confirm-btn" type="submit" name="action" value="Confirmed">Confirm</button></form><form method="post" onsubmit="return confirm('Cancel this appointment request?')"><input type="hidden" name="csrf_token" value="<?= adminEscape(adminToken()) ?>"><input type="hidden" name="appointment_id" value="<?= (int)$a['id'] ?>"><button class="small-btn cancel-btn" type="submit" name="action" value="Cancelled">Cancel</button></form></div><?php else: ?><span class="cell-detail">No action available</span><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></section><p class="cell-detail">Appointment statuses update in the LifeLink database. This does not send an email or reserve a slot with an external blood donation service.</p></main></body></html>
