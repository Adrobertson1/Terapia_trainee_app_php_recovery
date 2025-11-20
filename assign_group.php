<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$trainee_id = $_GET['id'] ?? '';
if (!$trainee_id) {
  die("No trainee ID provided.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['group_id'])) {
  $group_id = $_POST['group_id'];

  // Check if already assigned
  $checkStmt = $pdo->prepare("SELECT * FROM supervision_group_trainees WHERE trainee_id = ?");
  $checkStmt->execute([$trainee_id]);
  $existing = $checkStmt->fetch();

  if ($existing) {
    $updateStmt = $pdo->prepare("UPDATE supervision_group_trainees SET group_id = ? WHERE trainee_id = ?");
    $updateStmt->execute([$group_id, $trainee_id]);
    $message = "Group assignment updated successfully.";
  } else {
    $insertStmt = $pdo->prepare("INSERT INTO supervision_group_trainees (trainee_id, group_id) VALUES (?, ?)");
    $insertStmt->execute([$trainee_id, $group_id]);
    $message = "Group assigned successfully.";
  }

  header("Location: view_trainee.php?id=" . urlencode($trainee_id));
  exit;
}

// Fetch trainee name
$traineeStmt = $pdo->prepare("SELECT first_name, surname FROM trainees WHERE trainee_id = ?");
$traineeStmt->execute([$trainee_id]);
$trainee = $traineeStmt->fetch();

if (!$trainee) {
  die("Trainee not found.");
}

// Fetch available groups
$groupStmt = $pdo->query("
  SELECT group_id, module_number, module_title, group_option
  FROM supervision_groups
  ORDER BY module_number, group_option
");
$groups = $groupStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Supervision Group</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    .form-box {
      background-color: #f9f9f9;
      padding: 20px;
      border-radius: 8px;
      border: 1px solid #ddd;
      max-width: 600px;
    }
    .form-box h3 {
      margin-top: 0;
    }
    .form-box select {
      padding: 8px;
      width: 100%;
      margin-bottom: 20px;
    }
    .form-box button {
      background-color: #1976d2;
      color: white;
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
    }
    .form-box button:hover {
      background-color: #0d47a1;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Assign Supervision Group</h2>

    <div class="form-box">
      <h3>Assign to: <?= htmlspecialchars($trainee['first_name'] . ' ' . $trainee['surname']) ?></h3>

      <form method="post">
        <label for="group_id"><strong>Select Group:</strong></label>
        <select name="group_id" id="group_id" required>
          <option value="">— Choose a group —</option>
          <?php foreach ($groups as $g): ?>
            <option value="<?= $g['group_id'] ?>">
              Module <?= htmlspecialchars($g['module_number']) ?> — <?= htmlspecialchars($g['module_title']) ?> (<?= htmlspecialchars($g['group_option']) ?>)
            </option>
          <?php endforeach; ?>
        </select>

        <button type="submit">Assign Group</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>