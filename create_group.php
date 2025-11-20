<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

// Predefined supervision group variants
$groupVariants = [
    ['module' => 1, 'title' => 'Toddler Observation'],
    ['module' => 2, 'title' => 'General Supervision Group'],
    ['module' => 2, 'title' => 'Parent and Baby Observation Group'],
    ['module' => 3, 'title' => 'General Supervision Group'],
    ['module' => 4, 'title' => 'General Supervision Group'],
    ['module' => 4, 'title' => 'Dissertation Group']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $variant = $_POST['variant'] ?? '';
    $group_option = $_POST['group_option'] ?? '';
    $supervisor_id = $_POST['supervisor_id'] ?? '';

    if ($variant && $group_option && $supervisor_id) {
        list($module_number, $module_title) = explode('|', $variant);

        $stmt = $pdo->prepare("
            INSERT INTO supervision_groups (module_number, module_title, group_type, group_option, supervisor_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $module_number,
            $module_title,
            $module_title, // using module_title as group_type for consistency
            $group_option,
            $supervisor_id
        ]);

        header("Location: supervision_groups.php?created=1");
        exit;
    } else {
        $error = "All fields are required.";
    }
}

// Fetch supervisors
$supervisors = $pdo->query("SELECT staff_id, first_name, surname FROM staff ORDER BY surname")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Supervision Group</title>
    <link rel="stylesheet" href="style.css">
    <style>
        form {
            max-width: 600px;
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 12px;
        }
        select, input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 16px;
            background-color: #6a1b9a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .message-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Create New Supervision Group</h2>

        <?php if (isset($error)): ?>
            <div class="message-warning"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Group Variant:
                <select name="variant" required>
                    <option value="">-- Select Group Variant --</option>
                    <?php foreach ($groupVariants as $variant): ?>
                        <option value="<?= $variant['module'] . '|' . $variant['title'] ?>">
                            Module <?= $variant['module'] ?> – <?= htmlspecialchars($variant['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Group Option (e.g. Option A, Option B):
                <input type="text" name="group_option" required>
            </label>

            <label>Supervisor:
                <select name="supervisor_id" required>
                    <option value="">-- Select Supervisor --</option>
                    <?php foreach ($supervisors as $s): ?>
                        <option value="<?= $s['staff_id'] ?>">
                            <?= htmlspecialchars($s['surname'] . ', ' . $s['first_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button type="submit">Create Group</button>
        </form>
    </div>
</div>
</body>
</html>