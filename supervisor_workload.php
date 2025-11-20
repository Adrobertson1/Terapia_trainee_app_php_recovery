<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

// Fetch supervisors and their trainee counts
$stmt = $pdo->query("
    SELECT s.supervisor_id, s.first_name, s.surname, s.email,
           COUNT(t.trainee_id) AS trainee_count
    FROM supervisors s
    LEFT JOIN trainees t ON s.supervisor_id = t.supervisor_id
    GROUP BY s.supervisor_id
    ORDER BY trainee_count DESC, s.surname ASC
");
$supervisors = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supervisor Workload Summary</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border: 1px solid #ccc; vertical-align: top; }
    th { background-color: #f0f0f0; }
    .badge-supervisor {
      background-color: #6a1b9a;
      color: white;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 0.75em;
      font-weight: bold;
    }
    .btn {
      padding: 6px 12px;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.9em;
    }
    .btn:hover { background-color: #0d47a1; }
    .trainee-list { margin-top: 10px; padding-left: 20px; font-size: 0.9em; }
    details summary { cursor: pointer; font-weight: bold; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Supervisor Workload Summary</h2>

    <?php if (empty($supervisors)): ?>
      <p>No supervisors found.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Supervisor</th>
            <th>Email</th>
            <th>Assigned Trainees</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($supervisors as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?></td>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td><?= $s['trainee_count'] ?> assigned</td>
              <td>
                <?php if ($s['trainee_count'] > 0): ?>
                  <details class="trainee-list">
                    <summary>View Trainees</summary>
                    <ul>
                      <?php
                      $tStmt = $pdo->prepare("SELECT first_name, surname FROM trainees WHERE supervisor_id = ?");
                      $tStmt->execute([$s['supervisor_id']]);
                      foreach ($tStmt->fetchAll() as $t):
                      ?>
                        <li><?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </details>
                  <p><a class="btn" href="view_supervisor_trainees.php?supervisor_id=<?= $s['supervisor_id'] ?>">View All</a></p>
                <?php else: ?>
                  <em>No trainees assigned</em>
                <?php endif; ?>
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