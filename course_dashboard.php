<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Fetch all courses with trainee counts
$courseSummaryStmt = $pdo->query("
  SELECT c.course_id, c.course_name, COUNT(tc.trainee_id) AS trainee_count
  FROM courses c
  LEFT JOIN trainee_courses tc ON c.course_id = tc.course_id
  GROUP BY c.course_id, c.course_name
  ORDER BY c.course_name
");
$courseSummary = $courseSummaryStmt->fetchAll();

// Get selected course
$selectedCourseId = $_GET['course_id'] ?? null;
$selectedCourseName = null;
$activeTrainees = [];
$archivedTrainees = [];
$activeCount = 0;
$archivedCount = 0;

if ($selectedCourseId) {
  $courseNameStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
  $courseNameStmt->execute([$selectedCourseId]);
  $selectedCourseName = $courseNameStmt->fetchColumn();

  $activeStmt = $pdo->prepare("
    SELECT t.*
    FROM trainees t
    JOIN trainee_courses tc ON t.trainee_id = tc.trainee_id
    WHERE tc.course_id = ? AND t.is_archived = 0
    ORDER BY t.surname
  ");
  $activeStmt->execute([$selectedCourseId]);
  $activeTrainees = $activeStmt->fetchAll();
  $activeCount = count($activeTrainees);

  $archivedStmt = $pdo->prepare("
    SELECT t.*
    FROM trainees t
    JOIN trainee_courses tc ON t.trainee_id = tc.trainee_id
    WHERE tc.course_id = ? AND t.is_archived = 1
    ORDER BY t.surname
  ");
  $archivedStmt->execute([$selectedCourseId]);
  $archivedTrainees = $archivedStmt->fetchAll();
  $archivedCount = count($archivedTrainees);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Course Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .dashboard-section {
      margin-bottom: 30px;
    }
    .course-summary-table, .trainee-table, .status-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }
    th {
      background-color: #6a1b9a;
      color: white;
    }
    .btn-sm {
      padding: 6px 12px;
      font-size: 14px;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      border: none;
      cursor: pointer;
    }
    .btn-view { background-color: #4CAF50; color: white; }
    .btn-edit { background-color: #6a1b9a; color: white; }
    .btn-archive { background-color: #d32f2f; color: white; }
    .btn-active { background-color: #4CAF50; color: white; }
    .filter-form {
      margin-bottom: 20px;
    }
    .filter-form select {
      padding: 8px;
      border-radius: 4px;
      border: 1px solid #ccc;
      min-width: 250px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>Course Dashboard</h2>

    <div class="dashboard-section">
      <h3>📊 Course Summary</h3>
      <table class="course-summary-table">
        <thead>
          <tr>
            <th>Course Name</th>
            <th>Trainee Count</th>
            <th>View</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($courseSummary as $course): ?>
            <tr>
              <td><?= htmlspecialchars($course['course_name']) ?></td>
              <td><?= $course['trainee_count'] ?></td>
              <td>
                <a href="course_dashboard.php?course_id=<?= $course['course_id'] ?>" class="btn-sm btn-view">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($selectedCourseId): ?>
      <div class="dashboard-section">
        <h3>👥 Trainees in <?= htmlspecialchars($selectedCourseName) ?></h3>

        <table class="status-table">
          <thead>
            <tr>
              <th>Status</th>
              <th>Count</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Active Trainees</td>
              <td><?= $activeCount ?></td>
            </tr>
            <tr>
              <td>Archived Trainees</td>
              <td><?= $archivedCount ?></td>
            </tr>
          </tbody>
        </table>

        <?php if ($activeCount > 0): ?>
          <h4>✅ Active Trainees</h4>
          <table class="trainee-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Telephone</th>
                <th>Start Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($activeTrainees as $t): ?>
                <tr>
                  <td>
                    <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>">
                      <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($t['email']) ?></td>
                  <td><?= htmlspecialchars($t['telephone']) ?></td>
                  <td><?= htmlspecialchars($t['start_date']) ?></td>
                  <td>
                    <a href="edit_trainee.php?trainee_id=<?= $t['trainee_id'] ?>" class="btn-sm btn-edit">Edit</a>
                    <form method="post" action="archived_trainee.php" style="display:inline;" onsubmit="return confirm('Archive this trainee?');">
                      <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                      <button type="submit" class="btn-sm btn-archive">Archive</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

        <?php if ($archivedCount > 0): ?>
          <h4>📁 Archived Trainees</h4>
          <table class="trainee-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Telephone</th>
                <th>Start Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($archivedTrainees as $t): ?>
                <tr>
                  <td>
                    <a href="view_trainee.php?id=<?= $t['trainee_id'] ?>">
                      <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($t['email']) ?></td>
                  <td><?= htmlspecialchars($t['telephone']) ?></td>
                  <td><?= htmlspecialchars($t['start_date']) ?></td>
                  <td>
                    <form method="post" action="restore_trainee.php" style="display:inline;" onsubmit="return confirm('Restore this trainee?');">
                      <input type="hidden" name="trainee_id" value="<?= $t['trainee_id'] ?>">
                      <button type="submit" class="btn-sm btn-active">Restore</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>