<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor'])) {
    die("Access denied");
}

// Safely retrieve and normalize trainee ID
$trainee_id = isset($_GET['id']) ? strtoupper(trim($_GET['id'])) : '';
if ($trainee_id === '') {
    die("No trainee ID provided.");
}

// Fetch trainee data
$stmt = $pdo->prepare("SELECT * FROM trainees WHERE trainee_id COLLATE utf8mb4_general_ci = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();

if (!$trainee) {
    echo "<pre>Debug: trainee_id = " . htmlspecialchars($trainee_id) . "</pre>";
    die("Trainee not found.");
}

// Fetch course assignments
$courseStmt = $pdo->prepare("
  SELECT tc.id AS assignment_id, c.course_name, tc.status_flag, tc.enrolment_date
  FROM trainee_courses tc
  JOIN courses c ON tc.course_id = c.course_id
  WHERE tc.trainee_id = ?
  ORDER BY tc.enrolment_date ASC
");
$courseStmt->execute([$trainee_id]);
$courses = $courseStmt->fetchAll();

// Fetch feedback
$feedbackStmt = $pdo->prepare("
  SELECT tf.feedback_date, tf.notes, tf.alert_flag, tu.first_name AS tutor_first, tu.surname AS tutor_surname
  FROM tutor_feedback tf
  JOIN tutors tu ON tf.tutor_id = tu.tutor_id
  WHERE tf.trainee_id = ?
  ORDER BY tf.feedback_date DESC
  LIMIT 3
");
$feedbackStmt->execute([$trainee_id]);
$feedbackEntries = $feedbackStmt->fetchAll();

// Fetch logs
$logStmt = $pdo->prepare("
  SELECT action_type, description, timestamp, performed_by
  FROM trainee_logs
  WHERE trainee_id = ?
  ORDER BY timestamp DESC
");
$logStmt->execute([$trainee_id]);
$logs = $logStmt->fetchAll();

// Fetch current supervisor
$supervisorStmt = $pdo->prepare("
  SELECT first_name, surname, email
  FROM supervisors
  WHERE supervisor_id = ?
");
$supervisorStmt->execute([$trainee['supervisor_id']]);
$supervisor = $supervisorStmt->fetch();

// Fetch current assignments with grading info
$assignmentStmt = $pdo->prepare("
  SELECT ta.id AS assignment_id, at.type_name, ta.assigned_date, ta.due_date, ta.status,
         s.score_percent, s.feedback_text
  FROM trainee_assignments ta
  LEFT JOIN assignment_types at ON ta.type_id = at.type_id
  LEFT JOIN assignment_submissions s ON ta.id = s.assignment_id AND ta.trainee_id = s.trainee_id
  WHERE ta.trainee_id = ?
  ORDER BY ta.due_date ASC
");
$assignmentStmt->execute([$trainee_id]);
$currentAssignments = $assignmentStmt->fetchAll();

// Fetch supervision group
$groupStmt = $pdo->prepare("
  SELECT sg.group_id, sg.module_number, sg.module_title, sg.group_option
  FROM supervision_group_trainees sgt
  JOIN supervision_groups sg ON sgt.group_id = sg.group_id
  WHERE sgt.trainee_id = ?
  LIMIT 1
");
$groupStmt->execute([$trainee_id]);
$group = $groupStmt->fetch();

// Photo path
$photoPath = '';
if (!empty($trainee['profile_image']) && file_exists($trainee['profile_image'])) {
    $photoPath = $trainee['profile_image'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Trainee</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .status-indicator {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 6px;
    }
    .assignment-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    .assignment-table th, .assignment-table td {
      padding: 8px;
      border: 1px solid #ccc;
    }
    .btn-sm {
      padding: 6px 12px;
      font-size: 0.85em;
      border-radius: 4px;
      text-decoration: none;
      display: inline-block;
      background-color: #4CAF50;
      color: white;
    }
    .btn-sm:hover {
      background-color: #388E3C;
    }
    .status-pending { color: orange; font-weight: bold; }
    .status-submitted { color: blue; font-weight: bold; }
    .status-graded { color: green; font-weight: bold; }
    .graded-yes { color: green; font-weight: bold; }
    .graded-no { color: red; font-weight: bold; }
    .feedback-cell { font-size: 0.95em; color: #333; }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="page-container">
      <h2>View Trainee</h2>

      <div class="profile-box">
        <h3><?= htmlspecialchars($trainee['first_name'] . ' ' . $trainee['surname']) ?></h3>

        <div class="profile-photo">
          <?php if ($photoPath): ?>
            <img src="<?= htmlspecialchars($photoPath) ?>" alt="Trainee Photo">
          <?php else: ?>
            <em>No photo available.</em>
          <?php endif; ?>
        </div>

        <div class="profile-field"><strong>Trainee ID:</strong> <?= htmlspecialchars($trainee['trainee_id']) ?></div>
        <div class="profile-field"><strong>Date of Birth:</strong> <?= htmlspecialchars($trainee['date_of_birth']) ?></div>
        <div class="profile-field"><strong>Disability Status:</strong> <?= htmlspecialchars($trainee['disability_status']) ?></div>
        <?php if ($trainee['disability_status'] === 'Yes'): ?>
          <div class="profile-field"><strong>Disability Type:</strong> <?= htmlspecialchars($trainee['disability_type']) ?></div>
        <?php endif; ?>
        <div class="profile-field"><strong>Town/City:</strong> <?= htmlspecialchars($trainee['town_city']) ?></div>
        <div class="profile-field"><strong>Postcode:</strong> <?= htmlspecialchars($trainee['postcode']) ?></div>

        <?php if ($courses): ?>
          <?php foreach ($courses as $index => $course): ?>
            <hr>
            <div class="profile-field"><strong>Assigned Course<?= $index === 0 ? '' : ' ' . ($index + 1) ?>:</strong> <?= htmlspecialchars($course['course_name']) ?></div>
            <div class="profile-field"><strong>Enrolment Date:</strong> <?= htmlspecialchars($course['enrolment_date']) ?></div>
            <div class="profile-field">
              <strong>Status:</strong>
              <?php
                $flag = $course['status_flag'];
                $color = $flag === 'green' ? '#4CAF50' : ($flag === 'amber' ? '#FFC107' : '#F44336');
                $label = $flag === 'green' ? 'Doing Well' : ($flag === 'amber' ? 'Needs Monitoring' : 'Failing');
              ?>
              <span class="status-indicator" style="background-color:<?= $color ?>"></span> <?= $label ?>
            </div>
            <div class="profile-field">
              <a href="edit_course_assignment.php?id=<?= $course['assignment_id'] ?>" class="btn">Edit This Course</a>
              <?php if ($index > 0): ?>
                <a href="delete_course_assignment.php?id=<?= $course['assignment_id'] ?>&trainee_id=<?= $trainee_id ?>"
                   class="btn"
                   onclick="return confirm('Are you sure you want to delete this course assignment?');">
                  Delete This Course
                </a>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="profile-field"><em>No course assigned.</em></div>
        <?php endif; ?>

        <div class="btn-group-horizontal">
          <a href="edit_trainee.php?trainee_id=<?= $trainee['trainee_id'] ?>" class="btn">Edit Trainee</a>
          <a href="assign_course.php?id=<?= $trainee['trainee_id'] ?>" class="btn">Assign Course</a>
          <a href="assign_tutor.php?id=<?= $trainee['trainee_id'] ?>" class="btn">Assign Tutor</a>
          <a href="add_feedback.php?id=<?= $trainee['trainee_id'] ?>" class="btn">Add Feedback</a>
<a href="add_log.php?id=<?= $trainee['trainee_id'] ?>" class="btn">Add Log Entry</a>
<a href="assign_supervisor.php?id=<?= $trainee['trainee_id'] ?>" class="btn">Assign Supervisor</a>
<a href="assign_group.php?id=<?= $trainee['trainee_id'] ?>" class="btn">Assign to Group</a>
</div>

<hr>
<h3>Current Assignments</h3>
<?php if (empty($currentAssignments)): ?>
  <p><em>No assignments found.</em></p>
<?php else: ?>
  <table class="assignment-table">
    <thead>
      <tr>
        <th>Assignment Type</th>
        <th>Assigned Date</th>
        <th>Due Date</th>
        <th>Status</th>
        <th>Graded?</th>
        <th>Score</th>
        <th>Feedback</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($currentAssignments as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['type_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['assigned_date'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['due_date'] ?? '—') ?></td>
          <td><span class="status-<?= htmlspecialchars($a['status']) ?>"><?= ucfirst(htmlspecialchars($a['status'])) ?></span></td>
          <td>
            <?= is_numeric($a['score_percent']) ? '<span class="graded-yes">✅ Yes</span>' : '<span class="graded-no">❌ No</span>' ?>
          </td>
          <td>
            <?= is_numeric($a['score_percent']) ? htmlspecialchars($a['score_percent']) . '%' : '<em>—</em>' ?>
          </td>
          <td class="feedback-cell">
            <?= !empty($a['feedback_text']) ? nl2br(htmlspecialchars($a['feedback_text'])) : '<em>—</em>' ?>
          </td>
          <td>
            <a href="view_assignment.php?id=<?= $a['assignment_id'] ?>" class="btn-sm">View</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<hr>
<h3>Recent Feedback</h3>
<?php if (empty($feedbackEntries)): ?>
  <p><em>No feedback entries found.</em></p>
<?php else: ?>
  <ul>
    <?php foreach ($feedbackEntries as $entry): ?>
      <li>
        <strong><?= htmlspecialchars($entry['feedback_date']) ?>:</strong>
        <?= htmlspecialchars($entry['notes']) ?>
        <br><em>By <?= htmlspecialchars($entry['tutor_first'] . ' ' . $entry['tutor_surname']) ?></em>
        <?php if ($entry['alert_flag'] === 'Yes'): ?>
          <span style="color:red;font-weight:bold;">⚠️ Alert</span>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<hr>
<h3>Recent Logs</h3>
<?php if (empty($logs)): ?>
  <p><em>No log entries found.</em></p>
<?php else: ?>
  <ul>
    <?php foreach ($logs as $log): ?>
      <li>
        <strong><?= htmlspecialchars($log['timestamp']) ?>:</strong>
        <?= htmlspecialchars($log['action_type']) ?> —
        <?= htmlspecialchars($log['description']) ?>
        <br><em>By <?= htmlspecialchars($log['performed_by']) ?></em>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<hr>
<h3>Supervision Group</h3>
<?php if ($group): ?>
  <p>
    <strong>Module:</strong> <?= htmlspecialchars($group['module_number']) ?> —
    <?= htmlspecialchars($group['module_title']) ?><br>
    <strong>Option:</strong> <?= htmlspecialchars($group['group_option']) ?>
  </p>
<?php else: ?>
  <p><em>No supervision group assigned.</em></p>
<?php endif; ?>

<hr>
<h3>Supervisor</h3>
<?php if ($supervisor): ?>
  <p>
    <strong>Name:</strong> <?= htmlspecialchars($supervisor['first_name'] . ' ' . $supervisor['surname']) ?><br>
    <strong>Email:</strong> <?= htmlspecialchars($supervisor['email']) ?>
  </p>
<?php else: ?>
  <p><em>No supervisor assigned.</em></p>
<?php endif; ?>
</div>
</div>
</body>
</html>