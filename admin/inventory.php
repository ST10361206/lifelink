<?php
require 'common.php';
adminGuard();

$groups = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
$centres = $pdo->query('SELECT id, name FROM centres ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$centreIds = array_map('intval', array_column($centres, 'id'));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals(adminToken(), (string)($_POST['csrf'] ?? ''))) {
        $error = 'Your session expired. Refresh and try again.';
    } else {
        $centre = filter_var($_POST['centre_id'] ?? '', FILTER_VALIDATE_INT);
        $group = (string)($_POST['blood_group'] ?? '');
        $units = filter_var($_POST['units'] ?? '', FILTER_VALIDATE_INT, ['options'=>['min_range'=>0, 'max_range'=>1000000]]);
        $date = (string)($_POST['expiry_date'] ?? '');
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        $validDate = $parsed && $parsed->format('Y-m-d') === $date;
        $recordId = filter_var($_POST['record_id'] ?? '', FILTER_VALIDATE_INT);
        if (!in_array($centre, $centreIds, true) || !in_array($group, $groups, true) || $units === false || !$validDate) {
            $error = 'Please enter a valid centre, blood group, units and expiry date.';
        } elseif ($recordId) {
            $stmt = $pdo->prepare('UPDATE blood_inventory SET centre_id=?, blood_group=?, units=?, expiry_date=? WHERE id=?');
            $stmt->execute([$centre, $group, $units, $date, $recordId]);
            header('Location: inventory.php?saved=1'); exit;
        } else {
            $stmt = $pdo->prepare('INSERT INTO blood_inventory (centre_id, blood_group, units, expiry_date) VALUES (?, ?, ?, ?)');
            $stmt->execute([$centre, $group, $units, $date]);
            header('Location: inventory.php?saved=1'); exit;
        }
    }
}

$edit = null;
if (isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM blood_inventory WHERE id=?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$records = $pdo->query('SELECT i.*, c.name AS centre_name FROM blood_inventory i JOIN centres c ON c.id=i.centre_id ORDER BY i.expiry_date, c.name, i.blood_group')->fetchAll(PDO::FETCH_ASSOC);
$today = date('Y-m-d');
$lowLimit = 5; // Demo threshold, not a clinical inventory rule.
function inventoryStatus($r, $today, $lowLimit) {
    if ($r['expiry_date'] < $today) return 'Expired';
    if ((int)$r['units'] <= $lowLimit) return 'Low stock';
    return 'Available';
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Blood Inventory | LifeLink Admin</title><link rel="stylesheet" href="admin.css"><link rel="stylesheet" href="inventory.css"></head>
<body>
<header class="top"><div class="top-inner"><a class="brand" href="dashboard.php">♥ LifeLink <span>Admin</span></a><nav><a href="dashboard.php">Dashboard</a><a href="appointments.php">Appointments</a><a href="donors.php">Donors</a><a href="inventory.php">Inventory</a><a href="logout.php">Logout</a></nav></div></header>
<main class="content inventory-content">
<div class="intro"><div><span class="eyebrow">ADMINISTRATOR PORTAL</span><h1>Blood Inventory</h1><p>Record demonstration stock by centre, blood group and expiry date.</p></div></div>
<p class="inventory-note">Student prototype only. These records are not live blood supplies. A low-stock warning means 5 units or fewer in this demo. Expired records are not counted as usable stock.</p>
<?php if ($error): ?><p class="inventory-error" role="alert"><?= adminEscape($error) ?></p><?php endif; ?>
<?php if (isset($_GET['saved'])): ?><p class="inventory-success" role="status">Inventory record saved.</p><?php endif; ?>
<section class="panel"><h2><?= $edit ? 'Edit stock record' : 'Add stock record' ?></h2>
<?php if (!$centres): ?><p>Add donation centres before entering inventory.</p><?php else: ?>
<form method="post" class="inventory-form">
<input type="hidden" name="csrf" value="<?= adminEscape(adminToken()) ?>">
<?php if ($edit): ?><input type="hidden" name="record_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
<label>Donation centre <select name="centre_id" required><option value="">Choose a centre</option><?php foreach ($centres as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($edit['centre_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= adminEscape($c['name']) ?></option><?php endforeach; ?></select></label>
<label>Blood group <select name="blood_group" required><option value="">Choose group</option><?php foreach ($groups as $g): ?><option value="<?= adminEscape($g) ?>" <?= ($edit['blood_group'] ?? '') === $g ? 'selected' : '' ?>><?= adminEscape($g) ?></option><?php endforeach; ?></select></label>
<label>Units <input type="number" name="units" min="0" max="1000000" required value="<?= adminEscape($edit['units'] ?? '') ?>"></label>
<label>Expiry date <input type="date" name="expiry_date" required value="<?= adminEscape($edit['expiry_date'] ?? '') ?>"></label>
<div class="inventory-actions"><button type="submit"><?= $edit ? 'Save changes' : 'Add record' ?></button><?php if ($edit): ?><a href="inventory.php">Cancel edit</a><?php endif; ?></div>
</form><?php endif; ?></section>
<section class="panel"><h2>Recorded stock</h2>
<?php if (!$records): ?><p class="empty">No inventory records yet. Add a test record above.</p><?php else: ?>
<div class="table-scroll"><table><thead><tr><th>Centre</th><th>Blood group</th><th>Units</th><th>Expiry</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($records as $r): $status = inventoryStatus($r, $today, $lowLimit); ?><tr>
<td><?= adminEscape($r['centre_name']) ?></td><td><?= adminEscape($r['blood_group']) ?></td><td><?= (int)$r['units'] ?></td><td><?= adminEscape($r['expiry_date']) ?></td><td><span class="inventory-tag <?= $status === 'Available' ? 'ok' : 'warn' ?>"><?= adminEscape($status) ?></span></td><td><a href="inventory.php?edit=<?= (int)$r['id'] ?>">Edit</a></td>
</tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section></main></body></html>
