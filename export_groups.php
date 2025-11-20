<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

$moduleFilter = $_GET['module'] ?? '';
$supervisorFilter = $_GET['supervisor'] ?? '';
$where = [];
$params = [];

if ($moduleFilter !== '') {
    $where[] = 'sg.module_number = ?';
    $params[] = $moduleFilter;
}
if ($supervisorFilter !== '') {
    $where[] = '(s.first_name LIKE ? OR s.surname LIKE ?)';
    $params[] = '%' . $supervisorFilter . '%';
    $params[] = '%' . $supervisorFilter . '%';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("
    SELECT sg.module_number, sg.module_title, sg.group_type, sg.group_option,
           s.first_name AS supervisor_first, s.surname AS supervisor_surname,
           (SELECT COUNT(*) FROM supervision_group_trainees WHERE group_id = sg.group_id) AS trainee_count
    FROM supervision_groups sg
    LEFT JOIN staff s ON sg.supervisor_id = s.staff_id
    $whereClause
    ORDER BY sg.module_number, sg.group_type, sg.group_option
");
$stmt->execute($params);
$groups = $stmt->fetchAll();

// Output CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="supervision_groups.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Module Number', 'Module Title', 'Group Type', 'Group Option', 'Supervisor', 'Trainee Count']);

foreach ($groups as $group) {
    fputcsv($output, [
        $group['module_number'],
        $group['module_title'],
        $group['group_type'],
        $group['group_option'],
        $group['supervisor_first'] . ' ' . $group['supervisor_surname'],
        $group['trainee_count']
    ]);
}

fclose($output);
exit;