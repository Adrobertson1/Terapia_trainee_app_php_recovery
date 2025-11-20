<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$stmt = $pdo->prepare("
  SELECT a.submission_id, asn.title AS assignment_title, a.submitted_date, a.status,
         t.trainee_id, t.first_name, t.surname, t.email
  FROM assignment_submissions a
  JOIN trainees t ON CAST(a.trainee_id AS UNSIGNED) = CAST(t.trainee_id AS UNSIGNED)
  JOIN assignments asn ON CAST(a.assignment_id AS UNSIGNED) = CAST(asn.assignment_id AS UNSIGNED)
  ORDER BY a.submitted_date DESC
");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
  header('Content-Type: text/csv');
  header('Content-Disposition: attachment; filename=assignment_records.csv');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');
  if (!empty($records)) {
    fputcsv($output, array_keys($records[0]));
    foreach ($records as $row) {
      fputcsv($output, $row);
    }
  } else {
    fputcsv($output, ['No assignment records found']);
  }
  fclose($output);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assignment Records</title>
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
    <h2>Assignment Records</h2>

    <a href="view_all_assignment_records.php?export=csv" class="btn">Export CSV</a>

    <?php if (count($records) === 0): ?>
      <p class="no-results">No assignment records found.</p>
    <?php else: ?>
      <table class="record-table">
        <thead>
          <tr>
            <th>Assignment Title</th>
            <th>Submitted Date</th>
            <th>Status</th>
            <th>Trainee Name</th>
            <th>Email</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($records as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['assignment_title']) ?></td>
              <td><?= htmlspecialchars($r['submitted_date']) ?></td>
              <td><?= htmlspecialchars($r['status']) ?></td>
              <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['surname']) ?></td>
              <td><?= htmlspecialchars($r['email']) ?></td>
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