<?php
session_start();
require 'db.php';

if ($_SESSION['role'] !== 'trainee') {
    die("Access denied");
}

// Get trainee_id
$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainee = $stmt->fetch();

// Get assignments for courses this trainee is enrolled in
$stmt = $pdo->prepare("
    SELECT a.assignment_id, a.title, a.due_date, c.course_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.course_id
    JOIN trainee_course_enrollments e ON a.course_id = e.course_id
    WHERE e.trainee_id = ?");
$stmt->execute([$trainee['trainee_id']]);
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Assignments</title>
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
        <h2>My Assignments</h2>
      </div>

      <?php if (empty($assignments)): ?>
        <p>No assignments available.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Course</th>
              <th>Title</th>
              <th>Due</th>
              <th>Upload</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assignments as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['course_name']); ?></td>
                <td><?= htmlspecialchars($a['title']); ?></td>
                <td><?= htmlspecialchars($a['due_date']); ?></td>
                <td><a href="upload_submission.php?id=<?= $a['assignment_id']; ?>" class="btn">Upload</a></td>
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