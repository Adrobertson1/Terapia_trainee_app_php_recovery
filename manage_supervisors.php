<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$stmt = $pdo->prepare("
  SELECT staff_id, first_name, surname, email, job_title, start_date
  FROM staff
  WHERE role = 'admin' AND is_archived = 0
  ORDER BY surname ASC
");
$stmt->execute();
$admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Admins</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #E6D6EC; margin: 0; }
    .dashboard-wrapper { display: flex; }
    .main-content { flex: 1; padding: 40px; }
    h2 { color: #850069; font-family: 'Josefin Sans', sans-serif; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ccc; vertical-align: middle; }
    th { background-color: #f0f0f0; }
    .btn {
      padding: 6px 12px;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.9em;
      margin-right: 5px;
    }
    .btn:hover { background-color: #0d47a1; }
    .message.success {
      background-color: #dff0d8;
      color: #2e7d32;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
    }
    .action-buttons { margin-top: 10px; margin-bottom: 20px; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Manage Admins</h2>

    <?php if (isset($_GET['archived'])): ?>
      <div class="message success">Admin archived successfully.</div>
    <?php endif; ?>

    <div class="action-buttons">
      <a class="btn" href="add_staff.php">Add New Admin</a>
      <a class="btn" href="archived_admin.php">View Archived Admins</a>
    </div>

    <?php if (empty($admins)): ?>
      <p>No active admin accounts found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Job Title</th>
            <th>Start Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['first_name'] . ' ' . $a['surname']) ?></td>
              <td><?= htmlspecialchars($a['email']) ?></td>
              <td><?= htmlspecialchars($a['job_title']) ?></td>
              <td><?= date('j M Y', strtotime($a['start_date'])) ?></td>
              <td>
                <a class="btn" href="edit_staff.php?staff_id=<?= $a['staff_id'] ?>">Edit</a>
                <a class="btn" href="archived_admin.php?archive=<?= $a['staff_id'] ?>" onclick="return confirm('Archive this admin?');">Archive</a>
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