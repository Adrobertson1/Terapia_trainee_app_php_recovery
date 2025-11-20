<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$timeout = 1800;
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$trainee_id = $_GET['trainee_id'] ?? null;
if (!$trainee_id) {
    die("No trainee ID provided.");
}

$stmt = $pdo->prepare("SELECT * FROM trainees WHERE trainee_id = ?");
$stmt->execute([$trainee_id]);
$trainee = $stmt->fetch();
if (!$trainee) {
    die("Trainee not found.");
}

$courseOptions = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name");

$courseStmt = $pdo->prepare("SELECT course_id, enrolment_date FROM trainee_courses WHERE trainee_id = ?");
$courseStmt->execute([$trainee_id]);
$currentCourse = $courseStmt->fetch(PDO::FETCH_ASSOC);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }

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

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } elseif (!preg_match('/^\d{11}$/', $telephone)) {
        $error = 'Telephone number must be 11 digits.';
    }

    $profileImagePath = $trainee['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES['profile_image']['name']);
        $safeName = preg_replace("/[^a-zA-Z0-9.\-_]/", "", $fileName);
        $profileImagePath = $targetDir . time() . "_" . $safeName;

        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImagePath)) {
            $error = 'Failed to upload profile image.';
        }
    }

    if (!$error) {
        $updateStmt = $pdo->prepare("
            UPDATE trainees
            SET first_name = ?, surname = ?, email = ?, date_of_birth = ?, disability_status = ?, disability_type = ?,
                town_city = ?, postcode = ?, address_line1 = ?, telephone = ?, profile_image = ?
            WHERE trainee_id = ?
        ");
        $updateStmt->execute([
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
            $profileImagePath,
            $trainee_id
        ]);

        if (!empty($password) || !empty($confirm_password)) {
            if ($password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $passStmt = $pdo->prepare("UPDATE trainees SET password = ? WHERE trainee_id = ?");
                $passStmt->execute([$hashedPassword, $trainee_id]);
            }
        }

        if (!empty($course_id)) {
            $checkStmt = $pdo->prepare("SELECT * FROM trainee_courses WHERE trainee_id = ?");
            $checkStmt->execute([$trainee_id]);

            if ($checkStmt->fetch()) {
                $updateCourse = $pdo->prepare("UPDATE trainee_courses SET course_id = ?, enrolment_date = ? WHERE trainee_id = ?");
                $updateCourse->execute([$course_id, $enrolment_date, $trainee_id]);
            } else {
                $insertCourse = $pdo->prepare("INSERT INTO trainee_courses (trainee_id, course_id, enrolment_date) VALUES (?, ?, ?)");
                $insertCourse->execute([$trainee_id, $course_id, $enrolment_date]);
            }
        }

        if (!$error) {
            header("Location: view_trainee.php?id=" . $trainee_id);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Trainee</title>
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
        .btn-cancel {
            margin-left: 10px;
            text-decoration: none;
            color: #6a1b9a;
            font-weight: bold;
        }
        .btn-cancel:hover {
            text-decoration: underline;
        }
        .error-message {
            color: #d32f2f;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .thumbnail {
            max-width: 100px;
            height: auto;
            margin-bottom: 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <h2>Edit Trainee</h2>

        <div class="form-box">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <label for="trainee_id_display">Trainee ID:</label>
                <input type="text" id="trainee_id_display" value="<?= htmlspecialchars($trainee['trainee_id']) ?>" readonly>

                <label for="first_name">First Name:</label>
                <input type="text" name="first_name" id="first_name" value="<?= htmlspecialchars($trainee['first_name']) ?>" required>

                <label for="surname">Surname:</label>
                <input type="text" name="surname" id="surname" value="<?= htmlspecialchars($trainee['surname']) ?>" required>

                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" value="<?= htmlspecialchars($trainee['email']) ?>" required>

                <label for="date_of_birth">Date of Birth:</label>
<input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($trainee['date_of_birth']) ?>" required>

<label for="disability_status">Disability Status:</label>
<select name="disability_status" id="disability_status">
    <option value="No" <?= $trainee['disability_status'] === 'No' ? 'selected' : '' ?>>No</option>
    <option value="Yes" <?= $trainee['disability_status'] === 'Yes' ? 'selected' : '' ?>>Yes</option>
</select>

<label for="disability_type">Disability Type (if applicable):</label>
<input type="text" name="disability_type" id="disability_type" value="<?= htmlspecialchars($trainee['disability_type']) ?>">

<label for="town_city">Town/City:</label>
<input type="text" name="town_city" id="town_city" value="<?= htmlspecialchars($trainee['town_city']) ?>" required>

<label for="postcode">Postcode:</label>
<input type="text" name="postcode" id="postcode" value="<?= htmlspecialchars($trainee['postcode']) ?>" required>

<label for="address_line1">First Line of Address:</label>
<input type="text" name="address_line1" id="address_line1" value="<?= htmlspecialchars($trainee['address_line1']) ?>" required>

<label for="telephone">Telephone Number:</label>
<input type="text" name="telephone" id="telephone" value="<?= htmlspecialchars($trainee['telephone']) ?>" pattern="\d{11}" maxlength="11" required>

<label for="course_id">Assigned Course:</label>
<select name="course_id" id="course_id" required>
    <option value="">-- Choose a course --</option>
    <?php while ($course = $courseOptions->fetch()): ?>
        <option value="<?= htmlspecialchars($course['course_id']) ?>"
            <?= ($course['course_id'] == $currentCourse['course_id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($course['course_name']) ?>
        </option>
    <?php endwhile; ?>
</select>

<label for="enrolment_date">Date Enrolled:</label>
<input type="date" name="enrolment_date" id="enrolment_date" value="<?= htmlspecialchars($currentCourse['enrolment_date'] ?? date('Y-m-d')) ?>" required>

<label for="password">New Password (leave blank to keep current):</label>
<input type="password" name="password" id="password">

<label for="confirm_password">Confirm New Password:</label>
<input type="password" name="confirm_password" id="confirm_password">

<?php if (!empty($trainee['profile_image'])): ?>
    <label>Current Profile Image:</label>
    <img src="<?= htmlspecialchars($trainee['profile_image']) ?>" alt="Profile Image" class="thumbnail">
<?php endif; ?>

<label for="profile_image">Upload New Profile Image:</label>
<input type="file" name="profile_image" id="profile_image" accept="image/*">

<button type="submit">Update Trainee</button>
<a href="view_trainee.php?id=<?= htmlspecialchars($trainee['trainee_id']) ?>" class="btn-cancel">Cancel</a>