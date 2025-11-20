<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor'])) {
  die("Access denied.");
}

// Get tutor_id from session user
$stmt = $pdo->prepare("SELECT tutor_id FROM tutors WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tutor = $stmt->fetch();

// Fetch all grades given by this tutor
$stmt = $pdo->prepare("
    SELECT g.grade_id, g.grade, g.feedback, g.graded_at,
           a.title AS assignment_title, c.course_name,
           t.first_name, t.surname, s.file_path
    FROM grades g
    JOIN submissions s ON g.submission_id = s.submission_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN courses c ON a.course_id = c.course_id
    JOIN trainees t ON s.trainee_id = t.trainee_id
    WHERE g.tutor_id = ?
    ORDER BY g.graded_at DESC
");
$stmt->execute([$tutor['tutor_id']]);
$grades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Grades Given</title>
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
        <h2>Grades Given</h2>
      </div>

      <?php if (count($grades) === 0): ?>
        <p>No grades have been submitted yet.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Course</th>
              <th>Assignment</th>
              <th>Trainee</th>
              <th>Grade</th>
              <th>Feedback</th>
              <th>Graded At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grades as $g): ?>
              <tr>
                <td><?= htmlspecialchars($g['course_name']) ?></td>
                <td><?= htmlspecialchars($g['assignment_title']) ?></td>
                <td><?= htmlspecialchars($g['first_name'] . ' ' . $g['surname']) ?></td>
                <td><?= htmlspecialchars($g['grade']) ?></td>
                <td><?= htmlspecialchars($g['feedback']) ?></td>
                <td><?= htmlspecialchars($g['graded_at']) ?></td>
                <td>
                  <a href="grade_submission.php?id=<?= $g['grade_id'] ?>" class="btn">Edit</a>
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