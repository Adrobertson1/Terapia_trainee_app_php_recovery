<?php
session_start();
require 'db.php';
require_once 'functions.php';

if ($_SESSION['role'] !== 'tutor') {
    die("Access denied");
}

$stmt = $pdo->prepare("SELECT tutor_id FROM tutors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tutor = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];

    $stmt = $pdo->prepare("INSERT INTO assignments (course_id, tutor_id, title, description, due_date)
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$course_id, $tutor['tutor_id'], $title, $description, $due_date]);

    logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'create_assignment', "Created assignment: $title");
    header("Location: assignments.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Create Assignment</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>Create Assignment</h2>
      <form method="post">
        <label>Course:</label><br>
        <select name="course_id" required>
          <!-- Populate with course options -->
        </select><br><br>
        <label>Title:</label><br>
        <input type="text" name="title" required><br><br>
        <label>Description:</label><br>
        <textarea name="description"></textarea><br><br>
        <label>Due Date:</label><br>
        <input type="date" name="due_date" required><br><br>
        <button type="submit" class="btn">Create</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>