<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor'])) {
  die("Access denied.");
}

// Handle filters
$filter_course = $_GET['course_id'] ?? '';
$filter_status = $_GET['status_flag'] ?? '';
$search_name = $_GET['search_name'] ?? '';

// Build query
$sql = "
  SELECT t.trainee_id, t.first_name, t.surname, c.course_name, tc.enrolment_date, tc.status_flag
  FROM trainee_courses tc
  JOIN trainees t ON tc.trainee_id = t.trainee_id
  JOIN courses c ON tc.course_id = c.course_id
  WHERE 1=1
";

$params = [];

if ($filter_course !== '') {
    $sql .= " AND c.course_id = ?";
    $params[] = $filter_course;
}

if ($filter_status !== '') {
    $sql .= " AND tc.status_flag = ?";
    $params[] = $filter_status;
}

if ($search_name !== '') {
    $sql .= " AND (t.first_name LIKE ? OR t.surname LIKE ?)";
    $params[] = "%$search_name%";
    $params[] = "%$search_name%";
}

$sql .= " ORDER BY tc.enrolment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Fetch course list for filter dropdown
$courseList = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Course Assignments</title>
  <link rel="stylesheet" href="style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    th, td {
      padding: 10px;
      border-bottom: 1px solid #ccc;
      text-align: left;
    }
    .status-indicator {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 6px;
    }
    .filter-form {
      margin-bottom: 20px;
    }
    .filter-form input[type="text"],
    .filter-form select {
      padding: 6px;
      margin-right: 10px;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>Course Assignments</h2>

      <form method="get" class="filter-form">
        <label>Search Trainee:</label>
        <input type="text" name="search_name" value="<?= htmlspecialchars($search_name) ?>" placeholder="e.g. Smith or John">

        <label>Filter by Course:</label>
        <select name="course_id">
          <option value="">All Courses</option>
          <?php foreach ($courseList as $course): ?>
            <option value="<?= $course['course_id'] ?>" <?= $filter_course == $course['course_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($course['course_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>Status:</label>
        <select name="status_flag">
          <option value="">All Statuses</option>
          <option value="green" <?= $filter_status === 'green' ? 'selected' : '' ?>>Doing Well</option>
          <option value="amber" <?= $filter_status === 'amber' ? 'selected' : '' ?>>Needs Monitoring</option>
          <option value="red" <?= $filter_status === 'red' ? 'selected' : '' ?>>Failing</option>
        </select>

        <button type="submit" class="btn">Apply Filters</button>
      </form>

      <table>
        <thead>
          <tr>
            <th>Trainee</th>
            <th>Course</th>
            <th>Enrolment Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($assignments) === 0): ?>
            <tr><td colspan="5"><em>No assignments found.</em></td></tr>
          <?php else: ?>
            <?php foreach ($assignments as $row): ?>
              <?php
                $color = $row['status_flag'] === 'green' ? '#4CAF50' : ($row['status_flag'] === 'amber' ? '#FFC107' : '#F44336');
                $label = $row['status_flag'] === 'green' ? 'Doing Well' : ($row['status_flag'] === 'amber' ? 'Needs Monitoring' : 'Failing');
              ?>
              <tr>
                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['surname']) ?></td>
                <td><?= htmlspecialchars($row['course_name']) ?></td>
                <td><?= htmlspecialchars($row['enrolment_date']) ?></td>
                <td><span class="status-indicator" style="background-color:<?= $color ?>"></span><?= $label ?></td>
                <td>
                  <a href="view_trainee.php?id=<?= $row['trainee_id'] ?>" class="btn">View</a>
                  <a href="edit_course_assignment.php?id=<?= $row['trainee_id'] ?>" class="btn">Edit</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>