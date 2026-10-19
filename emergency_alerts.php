<?php
require 'includes/auth.php';
require 'includes/db.php';
$allowedGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
$selectedGroup = $_GET['group'] ?? '';
if (!in_array($selectedGroup, $allowedGroups, true)) $selectedGroup = '';
$sql = "SELECT a.id, a.title, a.blood_group, a.priority, a.description, a.contact_phone, a.expires_at,
               c.name AS centre_name, c.address AS centre_address
        FROM emergency_alerts a LEFT JOIN centres c ON c.id = a.centre_id
        WHERE a.is_active = 1 AND a.expires_at > NOW()";
$params = [];
if ($selectedGroup !== '') { $sql .= ' AND a.blood_group = ?'; $params[] = $selectedGroup; }
$sql .= " ORDER BY FIELD(a.priority, 'Critical', 'High'), a.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$alerts = $stmt->fetchAll();
function alertEsc($value) { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
$pageTitle = 'Emergency Alerts';
include 'includes/header.php';
?>
<section class="page-hero"><div class="container"><p class="eyebrow">DONOR PORTAL</p><h1>Emergency Blood Alerts</h1><p>See urgent requests published by participating donation centres.</p></div></section>
<section class="container section alert-section">
  <div class="alert-notice"><strong>Important:</strong> Only verified requests entered into LifeLink appear here. Always confirm with the donation centre before travelling. If someone needs emergency medical care, contact local emergency services.</div>
  <div class="alert-heading"><div><h2>Current Requests</h2><p>Expired and inactive alerts are automatically hidden.</p></div>
  <form action="emergency_alerts.php" method="get" class="alert-filter"><label for="group">Filter by blood group</label><select name="group" id="group"><option value="">All blood groups</option>
  <?php foreach ($allowedGroups as $group): ?><option value="<?= alertEsc($group) ?>" <?= $selectedGroup === $group ? 'selected' : '' ?>><?= alertEsc($group) ?></option><?php endforeach; ?>
  </select><button class="btn" type="submit">Apply</button></form></div>
  <?php if (!$alerts): ?>
    <div class="card alert-empty"><span class="alert-heart" aria-hidden="true">♥</span><h3>No active alerts<?= $selectedGroup ? ' for '.alertEsc($selectedGroup) : '' ?></h3><p>There are no matching verified emergency requests at the moment. You can still schedule a regular donation appointment.</p><a class="btn" href="appointments.php">Book an Appointment</a></div>
  <?php else: ?>
    <div class="alert-grid">
    <?php foreach ($alerts as $alert): ?>
      <article class="card alert-card"><div class="alert-card-top"><span class="alert-priority <?= $alert['priority'] === 'Critical' ? 'critical' : 'high' ?>"><?= alertEsc($alert['priority']) ?> priority</span><span class="alert-group"><?= alertEsc($alert['blood_group']) ?></span></div>
      <h3><?= alertEsc($alert['title']) ?></h3><p><?= nl2br(alertEsc($alert['description'])) ?></p>
      <dl><dt>Donation centre</dt><dd><?= alertEsc($alert['centre_name'] ?: 'Contact the coordinator') ?></dd><?php if ($alert['centre_address']): ?><dt>Address</dt><dd><?= alertEsc($alert['centre_address']) ?></dd><?php endif; ?><dt>Request expires</dt><dd><?= alertEsc(date('d M Y, H:i', strtotime($alert['expires_at']))) ?></dd><?php if ($alert['contact_phone']): ?><dt>Contact</dt><dd><?= alertEsc($alert['contact_phone']) ?></dd><?php endif; ?></dl>
      <a class="btn" href="appointments.php">Request an Appointment</a></article>
    <?php endforeach; ?></div>
  <?php endif; ?>
  <p class="fine-print">LifeLink is a student project prototype. An appointment request is not a confirmed emergency donation slot, and blood group compatibility must be assessed by qualified staff.</p>
</section>
<?php include 'includes/footer.php'; ?>
