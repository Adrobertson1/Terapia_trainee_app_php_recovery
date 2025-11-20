<?php
session_start();
require 'db.php';

$role = strtolower($_SESSION['role'] ?? '');
if (!in_array($role, ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

$group_id = $_GET['group_id'] ?? '';
if (!$group_id || !is_numeric($group_id)) {
    die("Invalid group ID.");
}

// Fetch group details
$stmt = $pdo->prepare("SELECT module_number, module_title, group_option FROM supervision_groups WHERE group_id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();
if (!$group) {
    die("Group not found.");
}

// Fetch assigned trainees
$traineeStmt = $pdo->prepare("
    SELECT first_name, surname
    FROM supervision_group_trainees sgt
    JOIN trainees t ON sgt.trainee_id = t.trainee_id
    WHERE sgt.group_id = ?
    ORDER BY t.surname
");
$traineeStmt->execute([$group_id]);
$group_trainees = $traineeStmt->fetchAll();

// Fetch historical sessions with invited trainees
$sessionStmt = $pdo->prepare("
    SELECT ss.session_date, ss.notes,
           GROUP_CONCAT(CONCAT(t.first_name, ' ', t.surname) SEPARATOR ', ') AS invited_trainees
    FROM supervision_sessions ss
    LEFT JOIN supervision_group_trainees sgt ON ss.group_id = sgt.group_id
    LEFT JOIN trainees t ON sgt.trainee_id = t.trainee_id
    WHERE ss.group_id = ?
    GROUP BY ss.session_id
    ORDER BY ss.session_date DESC
");
$sessionStmt->execute([$group_id]);
$past_sessions = $sessionStmt->fetchAll();

// Handle form submission
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_session'])) {
    $session_date = $_POST['session_date'] ?? '';
    $session_time = $_POST['session_time'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $notify = isset($_POST['notify_group']) ? true : false;

    $datetime = ($session_date && $session_time) ? "$session_date $session_time" : null;

    if ($datetime) {
        // Insert session
        $stmt = $pdo->prepare("INSERT INTO supervision_sessions (group_id, session_date) VALUES (?, ?)");
        $stmt->execute([$group_id, $datetime]);

        // Notify trainees if requested
        if ($notify) {
            $stmt = $pdo->prepare("
                SELECT t.user_id
                FROM supervision_group_trainees sgt
                JOIN trainees t ON sgt.trainee_id = t.trainee_id
                WHERE sgt.group_id = ?
            ");
            $stmt->execute([$group_id]);
            $trainees = $stmt->fetchAll();

            foreach ($trainees as $t) {
                if (!empty($t['user_id'])) {
                    $stmt = $pdo->prepare("INSERT INTO user_calendar_events (user_id, title, event_date, notes) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $t['user_id'],
                        "Supervision Session – Module {$group['module_number']}",
                        $datetime,
                        $notes
                    ]);
                }
            }
        }

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Schedule Group Session</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #E6D6EC;
      color: #000;
    }
    .dashboard-wrapper {
      display: flex;
      flex-direction: row;
    }
    .main-content {
      flex: 1;
      padding: 40px;
    }
    .form-card {
      max-width: 600px;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    h2 {
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
    }
    label {
      display: block;
      margin-top: 16px;
      font-weight: bold;
    }
    input, textarea {
      width: 100%;
      padding: 8px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-family: 'Inter', sans-serif;
    }
    button {
      margin-top: 20px;
      padding: 10px 16px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover {
      background-color: #BB9DC6;
    }
    .success-box {
      background-color: #dff0d8;
      border-left: 6px solid #3c763d;
      padding: 16px;
      margin-bottom: 20px;
      border-radius: 6px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 30px;
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
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="form-card">
      <h2>Schedule Session for Module <?= htmlspecialchars($group['module_number']) ?> – <?= htmlspecialchars($group['module_title']) ?> (<?= htmlspecialchars($group['group_option']) ?>)</h2>

      <?php if ($success): ?>
        <div class="success-box">
          ✅ Session scheduled successfully.
        </div>
      <?php endif; ?>

      <form method="post">
        <label for="session_date">Date</label>
        <input type="date" name="session_date" required>

        <label for="session_time">Time</label>
        <input type="time" name="session_time" required>

        <label for="notes">Optional Notes (used only for calendar events)</label>
        <textarea name="notes" rows="4"></textarea>

        <label>
          <input type="checkbox" name="notify_group" value="1" checked>
          Notify all trainees in this group
        </label>

        <button type="submit" name="schedule_session">Schedule Session</button>
      </form>

      <?php if (count($group_trainees) > 0): ?>
        <div style="margin-top: 40px; padding: 20px; background-color: #f5f5f5; border-left: 6px solid #6a1b9a; border-radius: 6px;">
          <h3 style="margin-top: 0;">👥 Trainees in This Group</h3>
          <ul style="list-style: none; padding-left: 0;">
            <?php foreach ($group_trainees as $t): ?>
              <li style="padding: 6px 0; border-bottom: 1px solid #ddd;">
                <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php else: ?>
        <p style="margin-top: 40px;">No trainees are currently assigned to this group.</p>
      <?php endif; ?>

      <?php if (count($past_sessions) > 0): ?>
        <h3>📅 Historical Sessions</h3>
        <table>
          <thead>
            <tr>
              <th>Date & Time</th>
              <th>Notes</th>
              <th>Invited Trainees</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($past_sessions as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['session_date']) ?></td>
                <td><?= nl2br(htmlspecialchars($s['notes'])) ?></td>
                <td><?= htmlspecialchars($s['invited_trainees']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p style="margin-top: 30px;">No sessions have been scheduled yet for this group.</p>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>