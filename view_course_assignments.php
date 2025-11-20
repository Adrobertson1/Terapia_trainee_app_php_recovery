<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$stmt = $pdo->prepare("
  SELECT c.course_name, t.trainee_id, t.first_name, t.surname, t.email, t.start_date
  FROM trainee_courses tc
  JOIN trainees t ON CAST(tc.trainee_id AS UNSIGNED) = CAST(t.trainee_id AS UNSIGNED)
  JOIN courses c ON CAST(tc.course_id AS UNSIGNED) = CAST(c.course_id AS UNSIGNED)
  ORDER BY c.course_name, t.surname
");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=course_assignments.csv');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');
  if (!empty($records)) {
    fputcsv($output, array_keys($records[0]));
    foreach ($records as $row) {
      fputcsv($output, $row);
    }
  } else {
    fputcsv($output, ['No course assignments found']);
  }
  fclose($output);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Course Assignments</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; max-width: 1200px; margin: auto; }
    .record-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .record-table th, .record-table td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
    }
    .record-table th {
      background-color: #6a1b9a;
      color: white;
    }
    .btn {
      display: inline-block;
      padding: 8px 16px;
      background-color: #6a1b9a;
      color: white;
      text-decoration: none;
      border-radius: 4px;
      font-weight: bold;
      margin-bottom: 20px;
    }
    .btn:hover {
      background-color: #4a148c;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Course Assignments</h2>

    <a href="view_course_assignments.php?export=csv" class="btn">Export CSV</a>

    <?php if (count($records) === 0): ?>
      <p class="no-results">No course assignments found.</p>
    <?php else: ?>
      <table class="record-table">
        <thead>
          <tr>
            <th>Course</th>
            <th>Trainee Name</th>
            <th>Email</th>
            <th>Start Date</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['course_name']) ?></td>
              <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['surname']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['start_date']) ?></td>
              <td>
                <a href="view_trainee.php?id=<?= $r['trainee_id'] ?>" class="btn" target="_blank">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>