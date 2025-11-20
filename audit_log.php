<?php
session_start();
require 'db.php';
require_once 'functions.php';

$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'staff', 'superuser'])) {
    die("Access denied");
}

// Fetch latest 100 audit entries with resolved usernames
$stmt = $pdo->query("
    SELECT a.*,
           COALESCE(s.username, t.username, 'Unknown') AS username
    FROM audit_log a
    LEFT JOIN staff s ON a.user_id = s.staff_id
    LEFT JOIN trainees t ON a.user_id = t.trainee_id
    ORDER BY a.timestamp DESC
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Audit Log</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .calendar-grid {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .calendar-grid th, .calendar-grid td {
      border: 1px solid #ccc;
      padding: 10px;
      text-align: left;
      vertical-align: top;
    }
    .calendar-grid th {
      background-color: #f0e6f5;
      color: #850069;
    }
    .calendar-grid td {
      background-color: #fff;
    }
    .page-header h2 {
      margin-bottom: 5px;
    }
    .page-header p {
      margin-top: 0;
      color: #555;
    }
    .audit-detail {
      font-size: 0.95em;
      color: #333;
    }
    .audit-meta {
      font-size: 0.85em;
      color: #777;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <div class="page-header">
        <h2>Audit Log</h2>
        <p>Recent system activity (last 100 actions)</p>
      </div>

      <?php if (empty($logs)): ?>
        <p>No audit entries found.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Action</th>
              <th>Table</th>
              <th>Record ID</th>
              <th>Details</th>
              <th>IP Address</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td><?= htmlspecialchars($log['username']) ?></td>
                <td><?= htmlspecialchars($log['role']) ?></td>
                <td><?= htmlspecialchars($log['action_type']) ?></td>
                <td><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($log['record_id'] ?? '-') ?></td>
                <td class="audit-detail"><?= nl2br(htmlspecialchars($log['action_detail'])) ?></td>
                <td class="audit-meta"><?= htmlspecialchars($log['ip_address']) ?></td>
                <td class="audit-meta"><?= date('j M Y, H:i', strtotime($log['timestamp'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>