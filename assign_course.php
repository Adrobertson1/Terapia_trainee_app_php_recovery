<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

// Accept trainee ID from either GET or POST
$trainee_id = $_POST['trainee_id'] ?? $_GET['id'] ?? null;

if (!$trainee_id) {
    die("Trainee ID not provided.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'] ?? null;

    if ($course_id) {
        // Insert course assignment
        $stmt = $pdo->prepare("
            INSERT INTO trainee_courses (trainee_id, course_id, enrolment_date)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $trainee_id,
            $course_id,
            date('Y-m-d')
        ]);

        // Fetch course name for logging
        $courseNameStmt = $pdo->prepare("SELECT course_name FROM courses WHERE course_id = ?");
        $courseNameStmt->execute([$course_id]);
        $courseName = $courseNameStmt->fetchColumn();

        // Log the action
        $logStmt = $pdo->prepare("
            INSERT INTO trainee_logs (trainee_id, action_type, description, performed_by)
            VALUES (?, 'course_added', ?, ?)
        ");
        $logStmt->execute([
            $trainee_id,
            "Assigned course: $courseName",
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
    <title>Assign Course</title>
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
        .form-box select, .form-box input {
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
        <h2>Assign Course to Trainee</h2>

        <div class="form-box">
            <form method="post">
                <input type="hidden" name="trainee_id" value="<?= htmlspecialchars($trainee_id) ?>">

                <label for="course_id">Select Course:</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php
                    $courses = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name");
                    while ($course = $courses->fetch()) {
                        echo "<option value=\"{$course['course_id']}\">" . htmlspecialchars($course['course_name']) . "</option>";
                    }
                    ?>
                </select>

                <button type="submit">Assign Course</button>
                <a href="view_trainee.php?id=<?= $trainee_id ?>" class="btn-cancel">Cancel</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>