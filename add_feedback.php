<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Fetch assigned trainees
$traineeStmt = $pdo->query("
  SELECT trainee_id, first_name, surname
  FROM trainees
  ORDER BY surname ASC
");
$trainees = $traineeStmt->fetchAll();

// Fetch tutors
$tutorStmt = $pdo->query("
  SELECT tutor_id, first_name, surname
  FROM tutors
  ORDER BY surname ASC
");
$tutors = $tutorStmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainee_id = $_POST['trainee_id'];
    $tutor_id = $_POST['tutor_id'];
    $feedback_date = $_POST['feedback_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'];
    $alert_flag = $_POST['alert_flag'] ?? 'none';
    $follow_up_date = $_POST['follow_up_date'] ?? null;
    $resolved = isset($_POST['resolved']) ? 1 : 0;

    $insertStmt = $pdo->prepare("
      INSERT INTO tutor_feedback (trainee_id, tutor_id, feedback_date, notes, alert_flag, follow_up_date, resolved)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([$trainee_id, $tutor_id, $feedback_date, $notes, $alert_flag, $follow_up_date, $resolved]);

    logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'add_feedback', "Feedback added for trainee ID $trainee_id by tutor ID $tutor_id");

    header("Location: view_trainee.php?id=$trainee_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Tutor Feedback</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>Add Tutor Feedback</h2>

      <form method="post">
        <label>Select Trainee:</label><br>
        <select name="trainee_id" required>
          <option value="">-- Choose Trainee --</option>
          <?php foreach ($trainees as $trainee): ?>
            <option value="<?= $trainee['trainee_id'] ?>">
              <?= htmlspecialchars($trainee['surname'] . ', ' . $trainee['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select><br><br>

        <label>Select Tutor:</label><br>
        <select name="tutor_id" required>
          <option value="">-- Choose Tutor --</option>
          <?php foreach ($tutors as $tutor): ?>
            <option value="<?= $tutor['tutor_id'] ?>">
              <?= htmlspecialchars($tutor['surname'] . ', ' . $tutor['first_name']) ?>
            </option>
          <?php endforeach; ?>
        </select><br><br>

        <label>Feedback Date:</label><br>
        <input type="date" name="feedback_date" value="<?= date('Y-m-d') ?>"><br><br>

        <label>Session Notes:</label><br>
        <textarea name="notes" rows="5" cols="60" placeholder="Enter feedback notes here..." required></textarea><br><br>

        <label>Alert Flag:</label><br>
        <select name="alert_flag">
          <option value="none">No Alert</option>
          <option value="safeguarding">Safeguarding</option>
          <option value="performance">Performance</option>
          <option value="attendance">Attendance</option>
        </select><br><br>

        <label>Follow-Up Date (optional):</label><br>
        <input type="date" name="follow_up_date"><br><br>

        <label><input type="checkbox" name="resolved"> Mark as Resolved</label><br><br>

        <button type="submit" class="btn">Submit Feedback</button>
        <a href="dashboard.php" class="btn">Cancel</a>
      </form>
    </div>
  </div>
</div>
</body>
</html>