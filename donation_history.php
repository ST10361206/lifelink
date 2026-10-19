<?php
require 'includes/auth.php';
require 'includes/db.php';

$donorId = (int) $_SESSION['donor_id'];
$stmt = $pdo->prepare("SELECT h.donation_date, h.blood_group, h.status, h.notes, c.name AS centre_name
                       FROM donation_history h
                       LEFT JOIN centres c ON c.id = h.centre_id
                       WHERE h.donor_id = ?
                       ORDER BY h.donation_date DESC, h.id DESC");
$stmt->execute([$donorId]);
$records = $stmt->fetchAll();
$completed = count(array_filter($records, fn($row) => $row['status'] === 'Completed'));
function historyEscape($value) { return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'); }
$pageTitle = 'Donation History';
include 'includes/header.php';
?>
<section class="page-hero"><div class="container"><p class="eyebrow">DONOR PORTAL</p><h1>My Donation History</h1><p>View the blood donations recorded against your LifeLink account.</p></div></section>
<section class="container section history-section">
  <div class="history-top">
    <div class="card history-stat"><span class="history-icon" aria-hidden="true">♥</span><div><strong><?= $completed ?></strong><p>Completed donations on record</p></div></div>
    <div class="history-actions"><a class="btn" href="appointments.php">Book an Appointment</a><a class="btn btn-dark" href="profile.php">My Profile</a></div>
  </div>
  <div class="history-heading"><div><h2>Donation Records</h2><p>Only donations entered by authorised staff will appear here.</p></div></div>
  <?php if (!$records): ?>
    <div class="card history-empty"><div class="history-empty-icon" aria-hidden="true">♥</div><h3>No donation records yet</h3><p>When a donation has been completed and recorded by a donation centre administrator, it will appear here. Your appointment bookings are listed separately.</p><a href="appointments.php" class="btn">View Appointments</a></div>
  <?php else: ?>
    <div class="table-wrap history-table"><table><thead><tr><th>Donation Date</th><th>Donation Centre</th><th>Blood Group</th><th>Status</th><th>Notes</th></tr></thead><tbody>
    <?php foreach ($records as $record): ?>
      <tr><td><?= historyEscape(date('d M Y', strtotime($record['donation_date']))) ?></td><td><?= historyEscape($record['centre_name'] ?: 'Not specified') ?></td><td><?= historyEscape($record['blood_group'] ?: 'Not recorded') ?></td><td><span class="history-badge <?= $record['status'] === 'Completed' ? 'is-complete' : 'is-deferred' ?>"><?= historyEscape($record['status']) ?></span></td><td><?= historyEscape($record['notes'] ?: '—') ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
  <p class="fine-print history-disclaimer">LifeLink is a project prototype. A booking is not proof of donation, and the information on this page is not a medical record.</p>
</section>
<?php include 'includes/footer.php'; ?>
