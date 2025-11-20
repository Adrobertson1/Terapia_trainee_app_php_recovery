<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$course_id = $_GET['id'] ?? null;
if (!$course_id) die("No course specified");

// Get course info
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

// Get all trainees
$allTrainees = $pdo->query("SELECT * FROM trainees ORDER BY surname")->fetchAll();

// Get enrolled trainees
$stmt = $pdo->prepare("
    SELECT e.enrollment_id, t.trainee_id, t.first_name, t.surname, e.start_date, e.expected_finish_date
    FROM trainee_course_enrollments e
    JOIN trainees t ON e.trainee_id = t.trainee_id
    WHERE e.course_id = ?");
$stmt->execute([$course_id]);
$enrollments = $stmt->fetchAll();

// Handle new enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO trainee_course_enrollments (trainee_id, course_id, start_date, expected_finish_date)
                           VALUES (?,?,?,?)");
    $stmt->execute([$_POST['trainee_id'], $course_id, $_POST['start_date'], $_POST['expected_finish_date']]);
    header("Location: enrollments.php?id=" . $course_id);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Enrollments</title>
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
        <h2>Enrollments for <?= htmlspecialchars($course['course_name']); ?></h2>
        <a href="courses.php" class="btn">Back to Courses</a>
      </div>

      <h3>Add Enrollment</h3>
      <form method="post">
        <label>Trainee:</label><br>
        <select name="trainee_id" required>
          <?php foreach ($allTrainees as $t): ?>
            <option value="<?= $t['trainee_id']; ?>"><?= htmlspecialchars($t['first_name']." ".$t['surname']); ?></option>
          <?php endforeach; ?>
        </select><br><br>

        <label>Start Date:</label><br>
        <input type="date" name="start_date" required><br><br>

        <label>Expected Finish Date:</label><br>
        <input type="date" name="expected_finish_date" required><br><br>

        <button type="submit" class="btn">Enroll</button>
      </form>

      <h3 style="margin-top:30px;">Currently Enrolled</h3>
      <?php if (empty($enrollments)): ?>
        <p>No trainees enrolled yet.</p>
      <?php else: ?>
        <table class="calendar-grid">
          <thead>
            <tr>
              <th>Name</th>
              <th>Start Date</th>
              <th>Expected Finish</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enrollments as $e): ?>
              <tr>
                <td><?= htmlspecialchars($e['first_name']." ".$e['surname']); ?></td>
                <td><?= htmlspecialchars($e['start_date']); ?></td>
                <td><?= htmlspecialchars($e['expected_finish_date']); ?></td>
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