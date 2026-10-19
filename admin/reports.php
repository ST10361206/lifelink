<?php
session_start();

require_once __DIR__ . '/../includes/db.php';

// Count registered donors
$totalDonors = $pdo->query(
    "SELECT COUNT(*) FROM donors"
)->fetchColumn();

// Count all appointments
$totalAppointments = $pdo->query(
    "SELECT COUNT(*) FROM appointments"
)->fetchColumn();

// Count pending appointments
$pending = $pdo->query(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'pending'"
)->fetchColumn();

// Count confirmed appointments
$confirmed = $pdo->query(
    "SELECT COUNT(*) FROM appointments
     WHERE status = 'confirmed'"
)->fetchColumn();
// Total usable blood units
$totalUnits = $pdo->query("
    SELECT COALESCE(SUM(units), 0)
    FROM blood_inventory
    WHERE expiry_date >= CURDATE()
")->fetchColumn();

// Low-stock inventory records
$lowStock = $pdo->query("
    SELECT COUNT(*)
    FROM blood_inventory
    WHERE units <= 5
    AND expiry_date >= CURDATE()
")->fetchColumn();

// Expired inventory records
$expired = $pdo->query("
    SELECT COUNT(*)
    FROM blood_inventory
    WHERE expiry_date < CURDATE()
")->fetchColumn();

// Blood stock grouped by blood type
$bloodGroups = $pdo->query("
    SELECT blood_group, SUM(units) AS total
    FROM blood_inventory
    WHERE expiry_date >= CURDATE()
    GROUP BY blood_group
    ORDER BY blood_group
")->fetchAll(PDO::FETCH_ASSOC);

// Monthly appointment statistics
$monthlyAppointments = $pdo->query("
    SELECT
        DATE_FORMAT(appointment_date, '%Y-%m') AS month,
        COUNT(*) AS total
    FROM appointments
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Appointment status statistics
$statusQuery = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM appointments
    GROUP BY status
");

$statusData = [
    'Pending' => 0,
    'Confirmed' => 0,
    'Cancelled' => 0
];

foreach ($statusQuery->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $status = ucfirst(strtolower($row['status']));

    if (array_key_exists($status, $statusData)) {
        $statusData[$status] = (int)$row['total'];
    }
}

// Donation Centre Statistics
$centreStats = $pdo->query("
    SELECT
        c.name AS centre_name,
        COUNT(a.id) AS total
    FROM centres c
    LEFT JOIN appointments a
        ON c.id = a.centre_id
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Reports | LifeLink Admin</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            margin: 0;
            color: #172638;
        }

        header {
            background: #142438;
            color: white;
            padding: 22px 8%;
        }

        main {
            max-width: 1100px;
            margin: 45px auto;
            padding: 20px;
        }

        .cards {
            display: grid;
            grid-template-columns:
                repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px #00000012;
        }

        .card h3 {
            font-size: 16px;
            color: #596579;
        }

        .card p {
            font-size: 36px;
            font-weight: bold;
            color: #bd183c;
        }

        a {
            color: #bd183c;
        }
        .chart-section {
             background: white;
             padding: 30px;
             border-radius: 15px;
             margin-top: 35px;
             box-shadow: 0 4px 15px #00000012;
        }

        .chart-section h2 {
              color: #172638;
              margin-bottom: 25px;
        }

        .chart-section canvas {
              max-height: 350px;
        }

        <style>
    body {
        font-family: Arial, sans-serif;
    }

    /* Your other existing CSS stays here */

    /* Download and Print buttons */
    .report-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin: 25px 0;
    }

    .report-btn {
        background: #bd183c;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        cursor: pointer;
    }

    .print-btn {
        background: #142438;
    }

    @media print {
        .report-actions {
            display: none;
        }
    }
</style>

</head>

<body>
    <header>
        <h2>LifeLink Admin | Reports</h2>
    </header>

    <main>
        <h1>Reports & Statistics</h1>

        <div class="report-actions">
    <a href="export_reports.php" class="report-btn">
        Download Appointments CSV
    </a>

    <button type="button"
            class="report-btn print-btn"
            onclick="window.print()">
        Print Report
    </button>
</div>
        <p>Overview of LifeLink donation activity.</p>

        <div class="cards">
            <div class="card">
                <h3>Registered Donors</h3>
                <p><?= (int)$totalDonors ?></p>
            </div>

            <div class="card">
                <h3>Total Appointments</h3>
                <p><?= (int)$totalAppointments ?></p>
            </div>

            <div class="card">
                <h3>Pending Appointments</h3>
                <p><?= (int)$pending ?></p>
            </div>

            <div class="card">
                <h3>Confirmed Appointments</h3>
                <p><?= (int)$confirmed ?></p>
            </div>
            <div class="card">
                <h3>Available Blood Units</h3>
                <p><?= (int)$totalUnits ?></p>
            </div>

            <div class="card">
                <h3>Low-Stock Records</h3>
            <p><?= (int)$lowStock ?></p>
            </div>

            <div class="card">
                 <h3>Expired Records</h3>
                 <p><?= (int)$expired ?></p>
            </div>
        </div>
        <section class="chart-section">
    <h2>Blood Inventory by Blood Group</h2>
    <canvas id="bloodChart"></canvas>
</section>

        <section class="chart-section">
    <h2>Monthly Appointment Statistics</h2>
    <canvas id="monthlyChart"></canvas>
</section>

        <section class="chart-section">
    <h2>Appointment Status Statistics</h2>
    <div style="max-width: 450px; height: 350px; margin: auto;">
        <canvas id="statusChart"></canvas>
    </div>
</section>

<section class="chart-section">
    <h2>Donation Centre Statistics</h2>
    <canvas id="centreChart"></canvas>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const bloodLabels = <?= json_encode(
    array_column($bloodGroups, 'blood_group')
) ?>;

const bloodValues = <?= json_encode(
    array_map('intval', array_column($bloodGroups, 'total'))
) ?>;

new Chart(document.getElementById('bloodChart'), {
    type: 'bar',
    data: {
        labels: bloodLabels,
        datasets: [{
            label: 'Available Blood Units',
            data: bloodValues,
            backgroundColor: '#bd183c',
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<script>
const monthlyData = <?= json_encode($monthlyAppointments) ?>;

const monthLabels = monthlyData.map(item => item.month);
const appointmentTotals = monthlyData.map(item => Number(item.total));

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Appointments',
            data: appointmentTotals,
            backgroundColor: '#b51235',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        }
    }
});
</script>

<script>
const statusData = <?= json_encode($statusData) ?>;

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData),
        datasets: [{
            data: Object.values(statusData),
            backgroundColor: [
                '#f4b942',
                '#28a745',
                '#dc3545'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

const centreLabels = <?= json_encode(
    array_column($centreStats, 'centre_name')
) ?>;

const centreValues = <?= json_encode(
    array_map('intval', array_column($centreStats, 'total'))
) ?>;

new Chart(document.getElementById('centreChart'), {
    type: 'bar',
    data: {
        labels: centreLabels,
        datasets: [{
            label: 'Total Appointments',
            data: centreValues,
            backgroundColor: '#b91c3c',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

        <p>
            <a href="dashboard.php">
                ← Back to Admin Dashboard
            </a>
        </p>
    </main>
</body>
</html>