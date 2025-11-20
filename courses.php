<?php
session_start();
require 'db.php';

if ($_SESSION['role'] !== 'trainee') {
  die("Access denied.");
}

$trainee_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
  SELECT c.course_name, c.description, c.start_date, c.end_date
  FROM trainee_courses tc
  JOIN courses c ON tc.course_id = c.course_id
  WHERE tc.trainee_id = ?
  ORDER BY c.start_date ASC
");
$stmt->execute([$trainee_id]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Courses</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { font-family: 'Inter', sans-serif; background: #F4F0F8; margin: 0; }
    .dashboard-wrapper { display: flex; }
    .main-content { flex: 1; padding: 40px; }
    h2 { color: #6a1b9a; margin-bottom: 20px; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ccc;
      text-align: left;
      vertical-align: top;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .no-data {
      text-align: center;
      color: #999;
      padding: 20px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>My Courses</h2>
    <?php if (count($courses) === 0): ?>
      <div class="no-data">You are not currently assigned to any courses.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Course Name</th>
            <th>Description</th>
            <th>Start Date</th>
            <th>End Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
            <tr>
              <td><?= htmlspecialchars($c['course_name']) ?></td>
              <td><?= htmlspecialchars($c['description']) ?></td>
              <td><?= htmlspecialchars(date('j M Y', strtotime($c['start_date']))) ?></td>
              <td><?= htmlspecialchars(date('j M Y', strtotime($c['end_date']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
</body>
</html>