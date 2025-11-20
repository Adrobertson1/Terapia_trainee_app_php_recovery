<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied");
}

// Total safeguarding records
$totalStmt = $pdo->query("SELECT COUNT(*) FROM safeguarding_TerapiaPersonnel_records");
$totalRecords = $totalStmt->fetchColumn();

// Reviewed records
$reviewedStmt = $pdo->query("SELECT COUNT(*) FROM safeguarding_TerapiaPersonnel_records WHERE review_date IS NOT NULL AND review_date != ''");
$reviewedRecords = $reviewedStmt->fetchColumn();
$reviewRate = $totalRecords > 0 ? round(($reviewedRecords / $totalRecords) * 100, 1) : 0;

// Fetch full record list
$recordsStmt = $pdo->query("
  SELECT record_id, record_date, individual_name, individual_role,
         concern_categories, dsl_action, reviewer_name, review_date
  FROM safeguarding_TerapiaPersonnel_records
  ORDER BY record_date DESC
");
$safeguardingRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Safeguarding Summary Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .summary-card {
      display: inline-block;
      width: 250px;
      padding: 15px;
      margin: 10px;
      background-color: #f3f3f3;
      border-left: 5px solid #6a1b9a;
      border-radius: 6px;
    }
    .summary-card h3 {
      margin: 0 0 10px;
    }
    .summary-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
    }
    .summary-table th, .summary-table td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      text-align: left;
      vertical-align: top;
    }
    .summary-table th {
      background-color: #6a1b9a;
      color: white;
    }
    .summary-table td {
      background-color: #f9f9f9;
    }
    .section-divider {
      margin-top: 50px;
      margin-bottom: 20px;
      font-size: 1.2em;
      font-weight: bold;
      color: #333;
    }
    a.record-link {
      color: #6a1b9a;
      text-decoration: underline;
    }
    a.record-link:hover {
      text-decoration: none;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>Safeguarding Summary Dashboard</h2>

      <div class="summary-card">
        <h3>Total Records</h3>
        <p><?= $totalRecords ?></p>
      </div>

      <div class="summary-card">
        <h3>Reviewed Records</h3>
        <p><?= $reviewedRecords ?> (<?= $reviewRate ?>%)</p>
      </div>

      <div class="section-divider">Logged Safeguarding Records</div>

      <table class="summary-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Individual Concerned</th>
            <th>Role</th>
            <th>Concern Categories</th>
            <th>DSL Action</th>
            <th>Reviewed By</th>
            <th>Review Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($safeguardingRecords as $record): ?>
            <tr>
              <td><?= htmlspecialchars($record['record_date']) ?></td>
              <td>
                <a class="record-link" href="view_safeguarding.php?id=<?= $record['record_id'] ?>">
                  <?= htmlspecialchars($record['individual_name']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($record['individual_role']) ?></td>
              <td><?= htmlspecialchars($record['concern_categories']) ?></td>
              <td><?= htmlspecialchars($record['dsl_action']) ?></td>
              <td><?= htmlspecialchars($record['reviewer_name']) ?></td>
              <td><?= htmlspecialchars($record['review_date']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    </div>
  </div>
</div>
</body>
</html>