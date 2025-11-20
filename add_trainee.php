<?php
// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate unique trainee ID
function generateTraineeId($pdo) {
    do {
        $id = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE trainee_id = ?");
        $stmt->execute([$id]);
    } while ($stmt->fetch());

    return $id;
}

$generated_id = generateTraineeId($pdo);
$courseOptions = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

    $trainee_id = $generated_id;
    $first_name = $_POST['first_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $email = $_POST['email'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $disability_status = $_POST['disability_status'] ?? 'No';
    $disability_type = $_POST['disability_type'] ?? '';
    $town_city = $_POST['town_city'] ?? '';
    $postcode = $_POST['postcode'] ?? '';
    $address_line1 = $_POST['address_line1'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $course_id = $_POST['course_id'] ?? '';
    $enrolment_date = $_POST['enrolment_date'] ?? date('Y-m-d');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $profileImagePath = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES['profile_image']['name']);
        $safeName = preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);
        $profileImagePath = $targetDir . time() . "_" . $safeName;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImagePath)) {
            $error = 'Failed to upload profile image.';
        }
    }

    if (empty($course_id)) {
        $error = 'Please select a course.';
    } elseif (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^\d{11}$/', $telephone)) {
        $error = 'Telephone number must be 11 digits.';
    } elseif (empty($password) || empty($confirm_password)) {
        $error = 'Password and confirmation are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (!$error) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO trainees (
                    trainee_id, first_name, surname, email, date_of_birth, disability_status,
                    disability_type, town_city, postcode, address_line1, telephone,
                    password, profile_image
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $trainee_id,
                $first_name,
                $surname,
                $email,
                $date_of_birth,
                $disability_status,
                $disability_type,
                $town_city,
                $postcode,
                $address_line1,
                $telephone,
                $hashedPassword,
                $profileImagePath
            ]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Trainee insert failed—no rows affected.");
            }

            $codeStmt = $pdo->query("SELECT COUNT(*) FROM trainees");
            $count = $codeStmt->fetchColumn();
            $trainee_code = 'TRN-' . date('Y') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $updateCode = $pdo->prepare("UPDATE trainees SET trainee_code = ? WHERE trainee_id = ?");
            $updateCode->execute([$trainee_code, $trainee_id]);

            $assignStmt = $pdo->prepare("
                INSERT INTO trainee_courses (trainee_id, course_id, enrolment_date)
                VALUES (?, ?, ?)
            ");
            $assignStmt->execute([
                $trainee_id,
                $course_id,
                $enrolment_date
            ]);

            $pdo->commit();

            header("Location: view_trainee.php?id=" . urlencode($trainee_id));
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Trainee</title>
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
        .form-box input, .form-box select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-box input[readonly] {
            background-color: #eee;
            color: #555;
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
        .error-message {
            color: #d32f2f;
            font-weight: bold;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Add New Trainee</h2>

        <div class="form-box">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <label for="trainee_id_display">Trainee ID:</label>
                <input type="text" id="trainee_id_display" value="<?= htmlspecialchars($generated_id) ?>" readonly>
                <input type="hidden" name="trainee_id" value="<?= htmlspecialchars($generated_id) ?>">

                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" id="first_name" required>

                <label for="surname">Surname:</label>
                <input type="text" name="surname" id="surname" required>

                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" required>

                <label for="date_of_birth">Date of Birth:</label>
                <input type="date" name="date_of_birth" id="date_of_birth" required>

                <label for="disability_status">Disability Status:</label>
                <select name="disability_status" id="disability_status">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                </select>

                <label for="disability_type">Disability Type (if applicable):</label>
<input type="text" name="disability_type" id="disability_type">
                <label for="town_city">Town/City:</label>
                <input type="text" name="town_city" id="town_city" required>

                <label for="postcode">Postcode:</label>
                <input type="text" name="postcode" id="postcode" required>

                <label for="address_line1">First Line of Address:</label>
                <input type="text" name="address_line1" id="address_line1" required>

                <label for="telephone">Telephone Number:</label>
                <input type="text" name="telephone" id="telephone" pattern="\d{11}" maxlength="11" required>

                <label for="course_id">Assign Course:</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Choose a course --</option>
                    <?php while ($course = $courseOptions->fetch()): ?>
                        <option value="<?= htmlspecialchars($course['course_id']) ?>">
                            <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="enrolment_date">Date Enrolled:</label>
                <input type="date" name="enrolment_date" id="enrolment_date" value="<?= date('Y-m-d') ?>" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <label for="profile_image">Profile Picture:</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*">

                <button type="submit">Add Trainee</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>