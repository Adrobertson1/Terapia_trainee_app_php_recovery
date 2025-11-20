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
  $filters[] = 's.trainee_id = ?';
  $params[] = $_GET['trainee_id'];
}
if (!empty($_GET['type_id'])) {
  $filters[] = 'a.type_id = ?';
  $params[] = $_GET['type_id'];
}
if (!empty($_GET['start_date'])) {
  $filters[] = 's.submitted_date >= ?';
  $params[] = $_GET['start_date'];
}
if (!empty($_GET['end_date'])) {
  $filters[] = 's.submitted_date <= ?';
  $params[] = $_GET['end_date'];
}

$whereClause = $filters ? 'WHERE ' . implode(' AND ', $filters) : '';

// Fetch filtered submissions
$stmt = $pdo->prepare("
  SELECT 
    s.submission_id,
    s.submitted_date,
    s.status,
    s.grade,
    s.feedback,
    s.file_path,
    t.first_name,
    t.surname,
    at.type_name
  FROM assignment_submissions s
  LEFT JOIN trainees t ON s.trainee_id = t.trainee_id
  LEFT JOIN trainee_assignments a ON s.assignment_id = a.id
  LEFT JOIN assignment_types at ON a.type_id = at.type_id
  $whereClause
  ORDER BY s.submitted_date DESC
");
$stmt->execute($params);
$submissions = $stmt->fetchAll();

// Output CSV headers
$timestamp = date('Ymd_His');
header('Content-Type: text/csv');
header("Content-Disposition: attachment; filename=\"submissions_export_$timestamp.csv\"");

$output = fopen('php://output', 'w');
fputcsv($output, ['Submission ID', 'Trainee', 'Assignment Type', 'Submitted Date', 'Status', 'Grade', 'Feedback', 'File Path']);

foreach ($submissions as $s) {
  fputcsv($output, [
    $s['submission_id'],
    $s['first_name'] . ' ' . $s['surname'],
    $s['type_name'],
    $s['submitted_date'],
    ucfirst($s['status']),
    $s['grade'],
    $s['feedback'],
    $s['file_path'] ?? 'No file'
  ]);
}

fclose($output);
exit;