<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Accept assignment ID from URL
$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    die("No assignment ID provided.");
}

// Fetch current assignment details
$stmt = $pdo->prepare("
    SELECT tc.id, tc.trainee_id, tc.course_id, t.first_name, t.surname
    FROM trainee_courses tc
    JOIN trainees t ON tc.trainee_id = t.trainee_id
    WHERE tc.id = ?
");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    die("Assignment not found.");
}

$trainee_id = $assignment['trainee_id'];

// Fetch course options
$courseOptions = [];
$courseStmt = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name");
while ($row = $courseStmt->fetch()) {
    $courseOptions[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_course_id = $_POST['course_id'] ?? null;

    if ($new_course_id) {
        $updateStmt = $pdo->prepare("UPDATE trainee_courses SET course_id = ? WHERE id = ?");
        $updateStmt->execute([$new_course_id, $assignment_id]);

        // Fetch course name for logging
        $courseNameStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
        $courseNameStmt->execute([$new_course_id]);
        $courseName = $courseNameStmt->fetchColumn();

        // Log the action
        $logStmt = $pdo->prepare("
            INSERT INTO trainee_logs (trainee_id, action_type, description, performed_by)
            VALUES (?, 'course_edited', ?, ?)
        ");
        $logStmt->execute([
            $trainee_id,
            "Edited course assignment to: $courseName",
            $_SESSION['username'] ?? $_SESSION['role']
        ]);

        header("Location: view_trainee.php?id=" . $trainee_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Course Assignment</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            max-width: 600px;
            margin-bottom: 30px;
            border: 1px solid #ccc;
        }
        .form-box label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .form-box select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-box button {
            padding: 10px 16px;
            background-color: #6a1b9a;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-box button:hover {
            background-color: #8e24aa;
        }
        .btn-cancel {
            margin-left: 10px;
            text-decoration: none;
            color: #6a1b9a;
            font-weight: bold;
        }
        .btn-cancel:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Edit Course Assignment</h2>

        <p><strong>Trainee:</strong> <?= htmlspecialchars($assignment['first_name'] . ' ' . $assignment['surname']) ?></p>

        <div class="form-box">
            <form method="post">
                <label for="course_id">Select New Course:</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php foreach ($courseOptions as $course): ?>
                        <option value="<?= $course['course_id'] ?>"
                            <?= ($course['course_id'] == $assignment['course_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Update Assignment</button>
                <a href="view_trainee.php?id=<?= $assignment['trainee_id'] ?>" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>