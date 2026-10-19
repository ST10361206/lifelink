<?php
require 'common.php';
adminGuard();

$search = trim((string)($_GET['q'] ?? ''));
$search = substr($search, 0, 100);
$donorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$selectedDonor = null;
$history = [];

if ($donorId) {
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone, blood_group, date_of_birth, created_at FROM donors WHERE id = ?');
    $stmt->execute([$donorId]);
    $selectedDonor = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($selectedDonor) {
        $stmt = $pdo->prepare('SELECT a.appointment_date, a.appointment_time, a.status, c.name AS centre_name FROM appointments a LEFT JOIN centres c ON c.id = a.centre_id WHERE a.donor_id = ? ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 50');
        $stmt->execute([$donorId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

if ($search !== '') {
    $stmt = $pdo->prepare('SELECT d.id, d.full_name, d.email, d.phone, d.blood_group, d.created_at, COUNT(a.id) AS appointment_count FROM donors d LEFT JOIN appointments a ON a.donor_id = d.id WHERE d.full_name LIKE ? OR d.email LIKE ? GROUP BY d.id, d.full_name, d.email, d.phone, d.blood_group, d.created_at ORDER BY d.full_name LIMIT 100');
    $term = '%' . $search . '%';
    $stmt->execute([$term, $term]);
} else {
    $stmt = $pdo->query('SELECT d.id, d.full_name, d.email, d.phone, d.blood_group, d.created_at, COUNT(a.id) AS appointment_count FROM donors d LEFT JOIN appointments a ON a.donor_id = d.id GROUP BY d.id, d.full_name, d.email, d.phone, d.blood_group, d.created_at ORDER BY d.created_at DESC LIMIT 100');
}
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Manage Donors | LifeLink Admin</title><link rel="stylesheet" href="admin.css"></head>
<body>
<header class="top"><div class="top-inner"><a class="brand" href="dashboard.php">♥ LifeLink <span>Admin</span></a><nav><a href="dashboard.php">Dashboard</a><a href="appointments.php">Appointments</a><a href="donors.php">Donors</a><a href="logout.php">Logout</a></nav></div></header>
<main class="content">
<div class="intro"><span class="eyebrow">ADMINISTRATOR PORTAL</span><h1>Manage Donors</h1><p>Find registered donors and review their appointment requests.</p></div>
<section class="panel">
<div class="panel-head"><h2>Registered donors</h2><form class="donor-search" method="get"><label for="q">Search donors</label><input id="q" name="q" type="search" placeholder="Name or email" value="<?= adminEscape($search) ?>"><button type="submit" class="small-btn">Search</button><a href="donors.php">Clear</a></form></div>
<?php if (!$donors): ?><p class="empty">No donors found.</p><?php else: ?>
<div class="table-scroll"><table><thead><tr><th>Donor</th><th>Phone</th><th>Blood group</th><th>Joined</th><th>Appointments</th><th></th></tr></thead><tbody>
<?php foreach ($donors as $donor): ?><tr><td><strong><?= adminEscape($donor['full_name']) ?></strong><div class="cell-detail"><?= adminEscape($donor['email']) ?></div></td><td><?= adminEscape($donor['phone']) ?></td><td><?= adminEscape($donor['blood_group'] ?: 'Not provided') ?></td><td><?= adminEscape(substr((string)$donor['created_at'], 0, 10)) ?></td><td><?= (int)$donor['appointment_count'] ?></td><td><a class="view-link" href="donors.php?id=<?= (int)$donor['id'] ?><?= $search !== '' ? '&amp;q=' . urlencode($search) : '' ?>">View history</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
<?php if (count($donors) === 100): ?><p class="cell-detail">Showing up to 100 donors. Use search to narrow the results.</p><?php endif; ?>
</section>
<?php if ($donorId): ?>
<section class="panel"><div class="panel-head"><h2>Donor appointment history</h2><a href="donors.php">Close details</a></div>
<?php if (!$selectedDonor): ?><p>Donor not found.</p><?php else: ?>
<p><strong><?= adminEscape($selectedDonor['full_name']) ?></strong> — <?= adminEscape($selectedDonor['email']) ?></p>
<p class="cell-detail">Phone: <?= adminEscape($selectedDonor['phone']) ?> · Blood group: <?= adminEscape($selectedDonor['blood_group'] ?: 'Not provided') ?> · Date of birth: <?= adminEscape($selectedDonor['date_of_birth'] ?: 'Not provided') ?></p>
<?php if (!$history): ?><p class="empty">This donor has no appointments yet.</p><?php else: ?><div class="table-scroll"><table><thead><tr><th>Centre</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>
<?php foreach ($history as $item): ?><tr><td><?= adminEscape($item['centre_name'] ?: 'Centre unavailable') ?></td><td><?= adminEscape($item['appointment_date']) ?></td><td><?= adminEscape(substr((string)$item['appointment_time'],0,5)) ?></td><td><span class="pill <?= strtolower(adminEscape($item['status'])) ?>"><?= adminEscape($item['status']) ?></span></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?><?php endif; ?></section>
<?php endif; ?>
<p class="cell-detail">Donor information is private. Only authorised administrators should access this page.</p>
</main></body></html>
