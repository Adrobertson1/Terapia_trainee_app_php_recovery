<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Fetch all grades with course, assignment, trainee, tutor
$stmt = $pdo->query("
    SELECT 
        c.course_name,
        a.title AS assignment_title,
        t.first_name AS trainee_first,
        t.surname AS trainee_last,
        tr.first_name AS tutor_first,
        tr.surname AS tutor_last,
        g.grade,
        g.feedback,
        g.graded_at
    FROM grades g
    JOIN submissions s ON g.submission_id = s.submission_id
    JOIN assignments a ON s.assignment_id = a.assignment_id
    JOIN courses c ON a.course_id = c.course_id
    JOIN trainees t ON s.trainee_id = t.trainee_id
    JOIN tutors tr ON g.tutor_id = tr.tutor_id
");

$grades = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Export Grades</title>
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
        <h2>Export Grades</h2>
      </div>

      <?php if (count($grades) === 0): ?>
        <p>No grades available to export.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Course</th>
              <th>Assignment</th>
              <th>Trainee</th>
              <th>Tutor</th>
              <th>Grade</th>
              <th>Feedback</th>
              <th>Graded At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($grades as $g): ?>
              <tr>
                <td><?= htmlspecialchars($g['course_name']) ?></td>
                <td><?= htmlspecialchars($g['assignment_title']) ?></td>
                <td><?= htmlspecialchars($g['trainee_first'] . ' ' . $g['trainee_last']) ?></td>
                <td><?= htmlspecialchars($g['tutor_first'] . ' ' . $g['tutor_last']) ?></td>
                <td><?= htmlspecialchars($g['grade']) ?></td>
                <td><?= htmlspecialchars($g['feedback']) ?></td>
                <td><?= htmlspecialchars($g['graded_at']) ?></td>
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