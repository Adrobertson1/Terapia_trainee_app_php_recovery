<?php
session_start();
require 'db.php';

$role = strtolower($_SESSION['role'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (!in_array($role, ['superuser', 'admin', 'staff', 'tutor', 'trainee', 'supervisor'])) {
    die("Access denied.");
}

// Filters (only for non-trainees)
$moduleFilter = $role === 'trainee' ? '' : ($_GET['module'] ?? '');
$supervisorFilter = $role === 'trainee' ? '' : ($_GET['supervisor'] ?? '');
$where = [];
$params = [];

if ($moduleFilter !== '') {
    $where[] = 'sg.module_number = ?';
    $params[] = $moduleFilter;
}
if ($supervisorFilter !== '') {
    $where[] = '(s.first_name LIKE ? OR s.surname LIKE ?)';
    $params[] = '%' . $supervisorFilter . '%';
    $params[] = '%' . $supervisorFilter . '%';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

if ($role === 'trainee') {
    $stmt = $pdo->prepare("
        SELECT sg.group_id, sg.module_number, sg.module_title, sg.group_type, sg.group_option,
               s.first_name AS supervisor_first, s.surname AS supervisor_surname,
               (SELECT COUNT(*) FROM supervision_group_trainees WHERE group_id = sg.group_id) AS trainee_count
        FROM supervision_groups sg
        JOIN supervision_group_trainees t ON sg.group_id = t.group_id
        LEFT JOIN staff s ON sg.supervisor_id = s.staff_id
        WHERE t.trainee_id = ?
        ORDER BY sg.module_number, sg.group_type, sg.group_option
    ");
    $stmt->execute([$user_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT sg.group_id, sg.module_number, sg.module_title, sg.group_type, sg.group_option,
               s.first_name AS supervisor_first, s.surname AS supervisor_surname,
               (SELECT COUNT(*) FROM supervision_group_trainees WHERE group_id = sg.group_id) AS trainee_count
        FROM supervision_groups sg
        LEFT JOIN staff s ON sg.supervisor_id = s.staff_id
        $whereClause
        ORDER BY sg.module_number, sg.group_type, sg.group_option
    ");
    $stmt->execute($params);
}

$groups = $stmt->fetchAll();

$unassignedTrainees = [];
if (in_array($role, ['superuser', 'admin', 'staff', 'tutor', 'supervisor'])) {
    $stmt = $pdo->prepare("
        SELECT t.trainee_id, t.first_name, t.surname, t.email
        FROM trainees t
        WHERE t.is_archived = 0
          AND NOT EXISTS (
            SELECT 1 FROM supervision_group_trainees sgt
            WHERE sgt.trainee_id = t.trainee_id
          )
        ORDER BY t.surname, t.first_name
    ");
    $stmt->execute();
    $unassignedTrainees = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervision Groups</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .group-card {
            border: 1px solid #ccc;
            padding: 16px;
            margin-bottom: 20px;
            border-radius: 6px;
            background-color: #f9f9f9;
        }
        .group-card h3 {
            margin: 0 0 8px;
        }
        .group-card a {
            text-decoration: none;
            color: #6a1b9a;
            font-weight: bold;
        }
        .group-meta {
            font-size: 14px;
            color: #555;
        }
        .top-actions {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-form input {
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .filter-form button,
        .top-actions a {
            padding: 6px 12px;
            border: none;
            background-color: #6a1b9a;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
        }
        .filter-form a {
            padding: 6px 12px;
            background-color: #ccc;
            color: #333;
            border-radius: 4px;
            text-decoration: none;
        }
        .schedule-button {
            padding: 6px 12px;
            background-color: #F3EAF5;
            color: #6a1b9a;
            border: 2px solid #6a1b9a;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 30px;
        }
        .schedule-button:hover {
            background-color: #e0d3ec;
            color: #4a148c;
            border-color: #4a148c;
        }
        .empty-message {
            background-color: #fff3cd;
            border-left: 6px solid #ffc107;
            padding: 16px;
            border-radius: 6px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
        }
        #unassignedTable {
            margin-top: 20px;
            margin-bottom: 40px;
            display: none;
        }
        #unassignedTable table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
        }
        #unassignedTable th, #unassignedTable td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        #unassignedTable th {
            background-color: #F3EAF5;
            color: #6a1b9a;
        }
        #unassignedTable a {
            color: #6a1b9a;
            font-weight: bold;
            text-decoration: none;
        }
        #unassignedTable a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Supervision Groups</h2>

        <?php if ($role !== 'trainee'): ?>
        <div class="top-actions">
            <a href="create_group.php">
                <i class="fas fa-plus-circle"></i> Create New Group
            </a>
            <a href="export_groups.php?module=<?= urlencode($moduleFilter) ?>&supervisor=<?= urlencode($supervisorFilter) ?>">
                <i class="fas fa-file-export"></i> Export CSV
            </a>
        </div>

        <form method="get" class="filter-form">
            <label>
                Module:
                <input type="number" name="module" value="<?= htmlspecialchars($moduleFilter) ?>">
            </label>
            <label>
                Supervisor:
                <input type="text" name="supervisor" value="<?= htmlspecialchars($supervisorFilter) ?>">
            </label>
            <button type="submit">Filter</button>
            <a href="supervision_groups.php">Reset</a>
        </form>

        <?php if (count($unassignedTrainees) > 0): ?>
        <button onclick="toggleUnassigned()" class="schedule-button">👥 View Unassigned Trainees</button>
        <div id="unassignedTable">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Trainee ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unassignedTrainees as $trainee): ?>
                    <tr>
                        <td>
                            <a href="view_trainee.php?id=<?= $trainee['trainee_id'] ?>">
                                <?= htmlspecialchars($trainee['first_name'] . ' ' . $trainee['surname']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($trainee['email']) ?></td>
                        <td><?= htmlspecialchars($trainee['trainee_id']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (count($groups) > 0): ?>
    <?php foreach ($groups as $group): ?>
                <div class="group-card">
            <h3>
                <a href="view_group.php?group_id=<?= $group['group_id'] ?>">
                    Module <?= htmlspecialchars($group['module_number']) ?> – <?= htmlspecialchars($group['module_title']) ?>
                    (<?= htmlspecialchars($group['group_option']) ?>)
                </a>
            </h3>
            <div class="group-meta">
                Type: <?= htmlspecialchars($group['group_type']) ?><br>
                Supervisor: <?= htmlspecialchars($group['supervisor_first'] . ' ' . $group['supervisor_surname']) ?><br>
                Trainees Assigned: <?= $group['trainee_count'] ?>
            </div>
            <?php if ($role !== 'trainee'): ?>
            <div style="margin-top: 10px;">
                <a href="schedule_session.php?group_id=<?= $group['group_id'] ?>" class="schedule-button">
                    <i class="fas fa-calendar-plus"></i> Schedule Group Session
                </a>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty-message">
        You are currently not enrolled in a Clinical Supervision Group.
    </div>
<?php endif; ?>
</div>
</div>

<script>
    function toggleUnassigned() {
        const table = document.getElementById('unassignedTable');
        table.style.display = table.style.display === 'none' ? 'block' : 'none';
    }
</script>
</body>
</html>