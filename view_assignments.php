<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'supervisor'])) {
  die("Access denied.");
}

// Fetch assignments with assigned_by name and email
$stmt = $pdo->prepare("
  SELECT 
    ta.id AS assignment_id,
    ta.trainee_id,
    ta.type_id,
    ta.assigned_date,
    ta.due_date,
    ta.status,
    ta.assigned_by,
    u.username AS assigned_by_name,
    u.email AS assigned_by_email,
    t.first_name AS trainee_first_name,
    t.surname AS trainee_surname,
    at.type_name AS assignment_type
  FROM trainee_assignments ta
  LEFT JOIN users u ON ta.assigned_by = u.user_id
  LEFT JOIN trainees t ON ta.trainee_id = t.trainee_id
  LEFT JOIN assignment_types at ON ta.type_id = at.type_id
  ORDER BY ta.assigned_date DESC
");
$stmt->execute();
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Assignments</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #f0f0f0;
    }
    .status-pending { color: orange; font-weight: bold; }
    .status-submitted { color: blue; font-weight: bold; }
    .status-graded { color: green; font-weight: bold; }
    .btn-sm {
      padding: 6px 12px;
      font-size: 0.85em;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      border: none;
      cursor: pointer;
    }
    .btn-view {
      background-color: #4CAF50;
      color: white;
    }
    .btn-view:hover {
      background-color: #388E3C;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>All Trainee Assignments</h2>

    <table>
      <thead>
        <tr>
          <th>Assignment ID</th>
          <th>Trainee</th>
          <th>Type</th>
          <th>Assigned Date</th>
          <th>Due Date</th>
          <th>Status</th>
          <th>Assigned By</th>
          <th>View Assignment Details</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($assignments as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['assignment_id']) ?></td>
            <td><?= htmlspecialchars($a['trainee_first_name'] . ' ' . $a['trainee_surname']) ?></td>
            <td><?= htmlspecialchars($a['assignment_type']) ?></td>
            <td><?= htmlspecialchars($a['assigned_date']) ?></td>
            <td><?= htmlspecialchars($a['due_date']) ?></td>
            <td class="status-<?= htmlspecialchars($a['status']) ?>">
              <?= ucfirst(htmlspecialchars($a['status'])) ?>
            </td>
            <td>
              <?= htmlspecialchars($a['assigned_by_name']) ?>
              (<?= htmlspecialchars($a['assigned_by_email']) ?>)
            </td>
            <td>
              <a href="view_assignment.php?assignment_id=<?= urlencode($a['assignment_id']) ?>" class="btn-sm btn-view">
                View Details
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>