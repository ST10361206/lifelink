<?php
require_once 'common.php';
adminGuard();

// Get appointment information
$sql = "
    SELECT
        a.id,
        d.full_name AS donor,
        c.name AS donation_centre,
        a.appointment_date,
        a.appointment_time,
        a.status
    FROM appointments a
    JOIN donors d ON a.donor_id = d.id
    JOIN centres c ON a.centre_id = c.id
    ORDER BY a.appointment_date DESC
";

$statement = $pdo->query($sql);

// Prepare the CSV download
$filename = 'LifeLink_Appointments_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create the CSV file
$output = fopen('php://output', 'w');

// Column headings
fputcsv($output, [
    'Appointment ID',
    'Donor Name',
    'Donation Centre',
    'Appointment Date',
    'Appointment Time',
    'Status'
]);

// Export appointment records
while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $values = array_values($row);

    // Prevent spreadsheet formula injection
    foreach ($values as &$value) {
        $value = (string)$value;
        if (preg_match('/^[\s]*[=+\-@]/', $value)) {
            $value = "'" . $value;
        }
    }
    unset($value);

    fputcsv($output, $values);
}

fclose($output);
exit;