<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$stmt = $pdo->prepare("
  SELECT t.trainee_id, t.first_name, t.surname, t.email, t.start_date,
         s.supervisor_id,
         CONCAT_WS(' ', s.first_name, s.surname_prefix, s.surname) AS supervisor_name,
         s.role AS supervisor_role, s.email AS supervisor_email
  FROM trainees t
  JOIN supervisors s ON t.supervisor_id = s.supervisor_id
  WHERE t.supervisor_id IS NOT NULL AND t.is_archived = 0
  ORDER BY s.surname, t.surname
");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=supervisor_allocations.csv');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');
  if (!empty($records)) {
    fputcsv($output, array_keys($records[0]));
    foreach ($records as $row) {
      fputcsv($output, $row);
    }
  } else {
    fputcsv($output, ['No supervisor allocations found']);
  }
  fclose($output);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supervisor Allocations</title>
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
    <h2>Supervisor Allocations</h2>

    <a href="view_all_supervisor_allocations.php?export=csv" class="btn">Export CSV</a>

    <?php if (count($records) === 0): ?>
      <p class="no-results">No supervisor allocations found.</p>
    <?php else: ?>
      <table class="record-table">
        <thead>
          <tr>
            <th>Trainee Name</th>
            <th>Email</th>
            <th>Start Date</th>
            <th>Supervisor Name</th>
            <th>Role</th>
            <th>Contact</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['surname']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
              <td><?= htmlspecialchars($r['start_date']) ?></td>
              <td><?= htmlspecialchars($r['supervisor_name']) ?></td>
              <td><?= htmlspecialchars($r['supervisor_role']) ?></td>
              <td><?= htmlspecialchars($r['supervisor_email']) ?></td>
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