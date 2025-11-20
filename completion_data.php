<?php
require 'db.php';

// Get completion stats per trainee
$stmt = $pdo->prepare("
  SELECT t.trainee_id, t.first_name, t.surname,
         COUNT(a.assignment_id) AS total,
         COUNT(s.submission_id) AS submitted
  FROM trainees t
  LEFT JOIN trainee_course_enrollments e ON t.trainee_id = e.trainee_id
  LEFT JOIN assignments a ON e.course_id = a.course_id
  LEFT JOIN submissions s ON s.assignment_id = a.assignment_id AND s.trainee_id = t.trainee_id
  GROUP BY t.trainee_id
");
$stmt->execute();
$rows = $stmt->fetchAll();

$data = [];
foreach ($rows as $r) {
  $completionRate = $r['total'] > 0 ? round(($r['submitted'] / $r['total']) * 100, 2) : 0;
  $data[] = [
    'name' => $r['first_name'] . ' ' . $r['surname'],
    'completion' => $completionRate
  ];
}

header('Content-Type: application/json');
echo json_encode($data);