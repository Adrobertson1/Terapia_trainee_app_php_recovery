<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Collect filters
$filters = [];
$params = [];

if (!empty($_GET['trainee_id'])) {
  $filters[] = 'ta.trainee_id = ?';
  $params[] = $_GET['trainee_id'];
}

if (!empty($_GET['type_id'])) {
  $filters[] = 'ta.type_id = ?';
  $params[] = $_GET['type_id'];
}

if (!empty($_GET['assigned_by'])) {
  $filters[] = 'ta.assigned_by = ?';
  $params[] = $_GET['assigned_by'];
}

if (!empty($_GET['start_date'])) {
  $filters[] = 'ta.assigned_date >= ?';
  $params[] = $_GET['start_date'];
}

if (!empty($_GET['end_date'])) {
  $filters[] = 'ta.assigned_date <= ?';
  $params[] = $_GET['end_date'];
}

$whereClause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

// Fetch filtered assignments
$stmt = $pdo->prepare("
  SELECT 
    ta.id AS assignment_id,
    ta.assigned_date,
    ta.due_date,
    ta.status,
    u.username AS assigned_by_name,
    u.email AS assigned_by_email,
    t.first_name AS trainee_first_name,
    t.surname AS trainee_surname,
    at.type_name AS assignment_type
  FROM trainee_assignments ta
  LEFT JOIN users u ON ta.assigned_by = u.user_id
  LEFT JOIN trainees t ON ta.trainee_id = t.trainee_id
  LEFT JOIN assignment_types at ON ta.type_id = at.type_id
  $whereClause
  ORDER BY ta.assigned_date DESC
");
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Output CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="assignments_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'Trainee', 'Type', 'Assigned Date', 'Due Date', 'Status', 'Assigned By']);

foreach ($assignments as $a) {
  fputcsv($output, [
    $a['assignment_id'],
    $a['trainee_first_name'] . ' ' . $a['trainee_surname'],
    $a['assignment_type'],
    $a['assigned_date'],
    $a['due_date'],
    ucfirst($a['status']),
    $a['assigned_by_name'] . ' (' . $a['assigned_by_email'] . ')'
  ]);
}

fclose($output);
exit;