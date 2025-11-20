<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$supervisor_id = $_GET['supervisor_id'] ?? null;
if (!$supervisor_id || !is_numeric($supervisor_id)) {
  die("Invalid supervisor ID.");
}

// Handle unassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unassign_trainee'])) {
  $trainee_id = $_POST['trainee_id'] ?? '';
  if ($trainee_id) {
    $stmt = $pdo->prepare("UPDATE trainees SET supervisor_id = NULL WHERE trainee_id = ?");
    $stmt->execute([$trainee_id]);
    header("Location: view_supervisor_trainees.php?supervisor_id=$supervisor_id&unassigned=1");
    exit;
  }
}

// Handle reassignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_trainee'])) {
  $trainee_id = $_POST['trainee_id'] ?? '';
  $new_supervisor_id = $_POST['new_supervisor_id'] ?? '';
  if ($trainee_id && $new_supervisor_id && is_numeric($new_supervisor_id)) {
    $stmt = $pdo->prepare("UPDATE trainees SET supervisor_id = ? WHERE trainee_id = ?");
    $stmt->execute([$new_supervisor_id, $trainee_id]);
    header("Location: view_supervisor_trainees.php?supervisor_id=$supervisor_id&reassigned=1");
    exit;
  }
}

// Fetch supervisor details
$stmt = $pdo->prepare("SELECT first_name, surname, email FROM supervisors WHERE supervisor_id = ?");
$stmt->execute([$supervisor_id]);
$supervisor = $stmt->fetch();

if (!$supervisor) {
  die("Supervisor not found.");
}

// Fetch assigned trainees with course and start date
$tStmt = $pdo->prepare("
  SELECT t.trainee_id, t.first_name, t.surname, t.email, t.start_date,
         c.course_name
  FROM trainees t
  LEFT JOIN trainee_courses tc ON t.trainee_id = tc.trainee_id
  LEFT JOIN courses c ON tc.course_id = c.course_id
  WHERE t.supervisor_id = ?
  ORDER BY t.surname
");
$tStmt->execute([$supervisor_id]);
$trainees = $tStmt->fetchAll();

// Fetch all supervisors for reassignment dropdown
$supervisorListStmt = $pdo->query("SELECT supervisor_id, first_name, surname FROM supervisors WHERE is_archived = 0 ORDER BY surname");
$supervisorList = $supervisorListStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Supervisor Trainees</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .dashboard-wrapper {
      display: flex;
    }
    .main-content {
      flex: 1;
      padding: 20px;
    }
    h2 {
      margin-bottom: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: #fff;
      box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    th, td {
      padding: 10px;
      border: 1px solid #ccc;
      vertical-align: top;
    }
    th {
      background-color: #f0f0f0;
    }
    .btn {
      padding: 6px 12px;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      font-size: 0.9em;
      margin-top: 20px;
      display: inline-block;
    }
    .btn:hover {
      background-color: #0d47a1;
    }
    a.trainee-link {
      color: #1976d2;
      text-decoration: none;
      font-weight: bold;
    }
    a.trainee-link:hover {
      text-decoration: underline;
    }
    select {
      padding: 6px;
      border-radius: 4px;
      border: 1px solid #ccc;
      margin-right: 8px;
    }
    form.inline-form {
      display: inline-block;
      margin-top: 8px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Trainees Assigned to <?= htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['surname']) ?></h2>
    <p>Email: <?= htmlspecialchars($supervisor['email']) ?></p>

    <?php if (empty($trainees)): ?>
      <p>No trainees currently assigned to this supervisor.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Trainee ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Course Assigned</th>
            <th>Supervisor Start Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($trainees as $t): ?>
            <tr>
              <td><?= htmlspecialchars($t['trainee_id']) ?></td>
              <td>
                <a class="trainee-link" href="view_trainee.php?id=<?= urlencode($t['trainee_id']) ?>">
                  <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($t['email']) ?></td>
              <td><?= htmlspecialchars($t['course_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars($t['start_date'] ?? '—') ?></td>
              <td>
                <form method="post" class="inline-form" onsubmit="return confirm('Return this trainee to the waiting list?');">
                  <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                  <button type="submit" name="unassign_trainee" class="btn">Unassign</button>
                </form>
                <form method="post" class="inline-form" onsubmit="return confirm('Reassign this trainee to another supervisor?');">
                  <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                  <select name="new_supervisor_id" required>
                    <option value="">Reassign to...</option>
                    <?php foreach ($supervisorList as $s): ?>
                      <?php if ($s['supervisor_id'] != $supervisor_id): ?>
                        <option value="<?= $s['supervisor_id'] ?>">
                          <?= htmlspecialchars($s['first_name'] . ' ' . $s['surname']) ?>
                        </option>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" name="reassign_trainee" class="btn">Reassign</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <a class="btn" href="supervisors.php">← Back to Supervisor Directory</a>
  </div>
</div>
</body>
</html>