<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO courses (course_name) VALUES (?)");
    $stmt->execute([$_POST['course_name']]);
    header("Location: courses.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Course</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>

  <div class="main-content">
    <div class="page-container">
      <div class="page-header">
        <h2>Add Course</h2>
        <a href="courses.php" class="btn">Back to Courses</a>
      </div>

      <form method="post">
        <label>Course Name:</label><br>
        <input type="text" name="course_name" required><br><br>
        <button type="submit" class="btn">Save</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>