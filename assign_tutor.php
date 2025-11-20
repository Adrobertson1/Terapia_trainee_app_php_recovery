<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$trainee_id = $_GET['id'] ?? null;

if (!$trainee_id) {
    die("No trainee ID provided.");
}

// Fetch trainee info
$stmt = $pdo->prepare("SELECT first_name, surname FROM trainees WHERE trainee_id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

if (!$trainee) {
    die("Trainee not found.");
}

// Fetch available tutors
$tutorStmt = $pdo->query("SELECT tutor_id, first_name, surname FROM tutors ORDER BY surname ASC");
$tutors = $tutorStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tutor_id = $_POST['tutor_id'];
    $assigned_date = $_POST['assigned_date'] ?? date('Y-m-d');

    // Check for existing assignment
    $checkStmt = $pdo->prepare("SELECT * FROM trainee_tutors WHERE trainee_id = ? AND tutor_id = ?");
    $checkStmt->execute([$trainee_id, $tutor_id]);
    if ($checkStmt->fetch()) {
        $error = "This tutor is already assigned to this trainee.";
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO trainee_tutors (trainee_id, tutor_id, assigned_date) VALUES (?, ?, ?)");
        $insertStmt->execute([$trainee_id, $tutor_id, $assigned_date]);

        logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'assign_tutor', "Assigned tutor ID $tutor_id to trainee ID $trainee_id");

        header("Location: view_trainee.php?id=$trainee_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Assign Tutor</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>Assign Tutor to <?= htmlspecialchars($trainee['first_name'] . ' ' . $trainee['surname']) ?></h2>

      <?php if (isset($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form method="post">
        <label>Select Tutor:</label><br>
        <select name="tutor_id" required>
          <option value="">-- Choose Tutor --</option>
          <?php foreach ($tutors as $tutor): ?>
            <option value="<?= $tutor['tutor_id'] ?>">
              <?= htmlspecialchars($tutor['surname'] . ', ' . $tutor['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select><br><br>

        <label>Assignment Date:</label><br>
        <input type="date" name="assigned_date" value="<?= date('Y-m-d') ?>"><br><br>

        <button type="submit" class="btn">Assign Tutor</button>
        <a href="view_trainee.php?id=<?= $trainee_id ?>" class="btn">Cancel</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>