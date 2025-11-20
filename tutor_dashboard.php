<?php
session_start();
require 'db.php';
require_once 'functions.php';

// Ensure user is logged in and is a tutor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tutor') {
    die("Access denied");
}

$tutor_id = $_SESSION['user_id'];

// Fetch assigned trainees
$stmt = $pdo->prepare("
  SELECT t.trainee_id, t.first_name, t.surname, tc.status_flag
  FROM trainee_tutor tt
  JOIN trainees t ON tt.trainee_id = t.trainee_id
  LEFT JOIN trainee_courses tc ON t.trainee_id = tc.trainee_id
  WHERE tt.tutor_id = ?
");
$stmt->execute([$tutor_id]);
$trainees = $stmt->fetchAll();

// Fetch feedback stats
$feedbackStats = [];
foreach ($trainees as $t) {
    $feedbackStmt = $pdo->prepare("
      SELECT COUNT(*) AS count, MAX(feedback_date) AS last_date
      FROM tutor_feedback
      WHERE trainee_id = ?
    ");
    $feedbackStmt->execute([$t['trainee_id']]);
    $feedbackStats[$t['trainee_id']] = $feedbackStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Trainees</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 12px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }
    .status-indicator {
      font-weight: bold;
      padding: 4px 8px;
      border-radius: 4px;
      color: white;
    }
    .status-green { background-color: #4CAF50; }
    .status-amber { background-color: #FFC107; }
    .status-red { background-color: #d32f2f; }
    .btn-link {
      text-decoration: none;
      color: #1976d2;
      font-weight: bold;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>My Assigned Trainees</h2>

      <?php if (count($trainees) === 0): ?>
        <p><em>No trainees assigned.</em></p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Name</th>
              <th>Status</th>
              <th>Feedback</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($trainees as $t): ?>
              <?php
                $flag = $t['status_flag'] ?? 'green';
                $statusClass = $flag === 'red' ? 'status-red' :
                               ($flag === 'amber' ? 'status-amber' : 'status-green');
                $statusLabel = $flag === 'red' ? 'Safeguarding' :
                               ($flag === 'amber' ? 'Performance' : 'OK');

                $stats = $feedbackStats[$t['trainee_id']] ?? ['count' => 0, 'last_date' => null];
              ?>
              <tr>
                <td><?= htmlspecialchars($t['surname'] . ', ' . $t['first_name']) ?></td>
                <td><span class="status-indicator <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                <td>
                  <?= $stats['count'] ?> entries
                  <?php if ($stats['last_date']): ?>
                    <br><small>Last: <?= htmlspecialchars($stats['last_date']) ?></small>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>" class="btn-link">View Profile</a> |
                  <a href="add_feedback.php?id=<?= $t['trainee_id'] ?>" class="btn-link">Add Feedback</a>
                </td>
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