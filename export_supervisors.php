<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=supervisor_allocations.csv');

$output = fopen('php://output', 'w');

// Write header
fputcsv($output, ['Supervisor ID', 'Supervisor Name', 'Email', 'Trainee Count', 'Trainee Name', 'Trainee Email', 'Telephone', 'Start Date']);

// Fetch supervisors and their trainees
$stmt = $pdo->prepare("
  SELECT s.supervisor_id, s.first_name AS sup_first, s.surname AS sup_surname, s.email AS sup_email,
         t.first_name AS trainee_first, t.surname AS trainee_surname, t.email AS trainee_email,
         t.telephone, t.start_date
  FROM supervisors s
  LEFT JOIN trainees t ON s.supervisor_id = t.supervisor_id AND t.is_archived = 0
  WHERE s.is_archived = 0
  ORDER BY s.surname, t.surname
");
$stmt->execute();

$lastSupervisorId = null;
$traineeCount = 0;
$rows = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $currentSupervisorId = $row['supervisor_id'];

  if ($currentSupervisorId !== $lastSupervisorId) {
    // Reset count for new supervisor
    $traineeCount = 0;
    $lastSupervisorId = $currentSupervisorId;
  }

  $traineeName = $row['trainee_first'] && $row['trainee_surname']
    ? $row['trainee_first'] . ' ' . $row['trainee_surname']
    : '';

  $traineeCount += $traineeName ? 1 : 0;

  fputcsv($output, [
    $row['supervisor_id'],
    $row['sup_first'] . ' ' . $row['sup_surname'],
    $row['sup_email'],
    $traineeName ? $traineeCount : '',
    $traineeName,
    $row['trainee_email'],
    $row['telephone'],
    $row['start_date']
  ]);
}

fclose($output);
exit;
?>