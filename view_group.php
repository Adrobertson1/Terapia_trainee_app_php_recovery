<?php
session_start();
require 'db.php';

$allowedRoles = ['superuser', 'admin', 'staff', 'supervisor', 'tutor', 'trainee'];
if (!in_array($_SESSION['role'], $allowedRoles)) {
    die("Access denied.");
}

$group_id = $_GET['group_id'] ?? '';
if (!$group_id || !is_numeric($group_id)) {
    die("Group ID missing or invalid.");
}

// Fetch group details
$stmt = $pdo->prepare("
    SELECT sg.*, s.first_name AS supervisor_first, s.surname AS supervisor_surname
    FROM supervision_groups sg
    LEFT JOIN staff s ON sg.supervisor_id = s.staff_id
    WHERE sg.group_id = ?
");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

// Fetch assigned trainees
$traineeStmt = $pdo->prepare("
    SELECT t.trainee_id, t.first_name, t.surname, t.email
    FROM supervision_group_trainees g
    JOIN trainees t ON g.trainee_id = t.trainee_id
    WHERE g.group_id = ?
    ORDER BY t.surname
");
$traineeStmt->execute([$group_id]);
$trainees = $traineeStmt->fetchAll();

// Fetch unassigned trainees
$unassignedStmt = $pdo->prepare("
    SELECT trainee_id, first_name, surname
    FROM trainees
    WHERE trainee_id NOT IN (
        SELECT trainee_id FROM supervision_group_trainees WHERE group_id = ?
    ) AND is_archived = 0
    ORDER BY surname
");
$unassignedStmt->execute([$group_id]);
$unassigned = $unassignedStmt->fetchAll();

// Fetch sessions with attendance summary
$sessions = [];
try {
    $sessionStmt = $pdo->prepare("
        SELECT ss.session_id, ss.session_date, ss.notes,
               (SELECT COUNT(*) FROM session_attendance WHERE session_id = ss.session_id AND attended = 1) AS attended_count,
               (SELECT COUNT(*) FROM supervision_group_trainees WHERE group_id = ss.group_id) AS total_trainees
        FROM supervision_sessions ss
        WHERE ss.group_id = ?
        ORDER BY ss.session_date DESC
    ");
    $sessionStmt->execute([$group_id]);
    $sessions = $sessionStmt->fetchAll();
} catch (PDOException $e) {
    $attendanceError = true;
}

// Handle trainee assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_trainee'])) {
    $newTraineeId = $_POST['new_trainee_id'] ?? '';
    if ($newTraineeId) {
        $assignStmt = $pdo->prepare("
            INSERT IGNORE INTO supervision_group_trainees (group_id, trainee_id)
            VALUES (?, ?)
        ");
        $assignStmt->execute([$group_id, $newTraineeId]);
        header("Location: view_group.php?group_id=$group_id");
        exit;
    }
}

// Handle trainee removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_trainee'])) {
    $removeTraineeId = $_POST['remove_trainee_id'] ?? '';
    if ($removeTraineeId) {
        $removeStmt = $pdo->prepare("
            DELETE FROM supervision_group_trainees
            WHERE group_id = ? AND trainee_id = ?
        ");
        $removeStmt->execute([$group_id, $removeTraineeId]);
        header("Location: view_group.php?group_id=$group_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Supervision Group</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .group-header h2 { margin-bottom: 8px; }
        .group-meta { font-size: 16px; color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 10px; border-bottom: 1px solid #ccc; text-align: left; }
        th { background-color: #6a1b9a; color: white; }
        .btn-sm { padding: 6px 12px; background-color: #6a1b9a; color: white; border: none; border-radius: 4px; text-decoration: none; font-size: 14px; }
        form.inline { display: inline; }
        select { padding: 6px; margin-right: 8px; }
        .profile-link {
            color: #6a1b9a;
            text-decoration: none;
            font-weight: bold;
        }
        .profile-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="group-header">
            <h2>
                Module <?= htmlspecialchars($group['module_number']) ?> – <?= htmlspecialchars($group['module_title']) ?>
                (<?= htmlspecialchars($group['group_option']) ?>)
            </h2>
            <div class="group-meta">
                Type: <?= htmlspecialchars($group['group_type']) ?><br>
                Supervisor: <?= htmlspecialchars($group['supervisor_first'] . ' ' . $group['supervisor_surname']) ?>
            </div>
        </div>

        <?php if (in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])): ?>
            <div style="margin-bottom: 20px;">
                <a href="schedule_session.php?group_id=<?= urlencode($group_id) ?>" class="btn-sm" style="background-color:#00796b;">
                    ➕ Schedule New Session
                </a>
            </div>
        <?php endif; ?>

        <h3>Assigned Trainees</h3>
        <?php if (count($trainees) === 0): ?>
            <p>No trainees assigned to this group.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>View</th>
                        <?php if (in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])): ?>
                            <th>Remove</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trainees as $t): ?>
                        <tr>
                            <td>
                                <a href="view_trainee.php?id=<?= urlencode($t['trainee_id']) ?>" class="profile-link">
                                    <?= htmlspecialchars($t['first_name'] . ' ' . $t['surname']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($t['email']) ?></td>
                            <td>
                                <a href="view_trainee.php?id=<?= urlencode($t['trainee_id']) ?>" class="btn-sm">View</a>
                            </td>
                            <?php if (in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])): ?>
                                <td>
                                    <form method="post" class="inline">
                                        <input type="hidden" name="remove_trainee_id" value="<?= $t['trainee_id'] ?>">
                                        <button type="submit" name="remove_trainee" class="btn-sm" style="background-color:#c62828;">Remove</button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])): ?>
            <h3>Assign New Trainee</h3>
            <form method="post">
                <select name="new_trainee_id" required>
                    <option value="">-- Select Trainee --</option>
                    <?php foreach ($unassigned as $u): ?>
                        <option value="<?= $u['trainee_id'] ?>">
                            <?= htmlspecialchars($u['surname'] . ', ' . $u['first_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="assign_trainee" class="btn-sm">Assign</button>
            </form>
        <?php endif; ?>

        <h3>Group Sessions</h3>
        <?php if (isset($attendanceError)): ?>
<p><strong>Attendance data could not be retrieved. Please check that the <code>session_attendance</code> table exists.</strong></p>
<?php elseif (count($sessions) === 0): ?>
    <p>No sessions scheduled yet.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Notes</th>
                <th>Attendance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['session_date']) ?></td>
                    <td><?= nl2br(htmlspecialchars($s['notes'])) ?></td>
                    <td><?= $s['attended_count'] ?> of <?= $s['total_trainees'] ?> present</td>
                    <td>
                        <a href="mark_attendance.php?session_id=<?= $s['session_id'] ?>" class="btn-sm">Mark/View</a>
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