<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
    die("Access denied.");
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$courseOptions = $pdo->query("SELECT course_id, course_name FROM courses ORDER BY course_name");

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
    $job_title = $_POST['job_title'] ?? '';
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $course_ids = $_POST['course_ids'] ?? [];

    $dbs_status = $_POST['dbs_status'] ?? 'Pending';
    $dbs_date = $_POST['dbs_date'] ?? null;
    $dbs_reference = $_POST['dbs_reference'] ?? '';
    $dbs_update_service = $_POST['dbs_update_service'] ?? 0;

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

    if (empty($email)) {
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
                INSERT INTO tutors (
                    first_name, surname, email, password, role,
                    job_title, start_date, telephone, profile_image,
                    disability_status, disability_type, address_line1, town_city, postcode, date_of_birth,
                    dbs_status, dbs_date, dbs_reference, dbs_update_service
                ) VALUES (?, ?, ?, ?, 'tutor', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $first_name, $surname, $email, $hashedPassword,
                $job_title, $start_date, $telephone, $profileImagePath,
                $disability_status, $disability_type, $address_line1, $town_city, $postcode, $date_of_birth,
                $dbs_status, $dbs_date, $dbs_reference, $dbs_update_service
            ]);

            $tutorId = $pdo->lastInsertId();

            $pdo->prepare("INSERT INTO users (user_id, role, email) VALUES (?, 'tutor', ?)")
                ->execute([$tutorId, $email]);

            $pdo->prepare("UPDATE tutors SET user_id = ? WHERE tutor_id = ?")
                ->execute([$tutorId, $tutorId]);

            foreach ($course_ids as $cid) {
                $pdo->prepare("INSERT INTO tutor_courses (tutor_id, course_id) VALUES (?, ?)")
                    ->execute([$tutorId, $cid]);
            }

            $pdo->commit();

            header("Location: tutors.php?added=1");
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
    <title>Add Tutor</title>
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
        <h2>Add New Tutor</h2>

        <div class="form-box">
            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

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

                <label for="job_title">Job Title:</label>
                <input type="text" name="job_title" id="job_title">

                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" id="start_date" value="<?= date('Y-m-d') ?>">

                <label for="dbs_status">DBS Status:</label>
                <select name="dbs_status" id="dbs_status">
                    <option value="Pending">Pending</option>
                    <option value="Cleared">Cleared</option>
                    <option value="Not Required">Not Required</option>
                </select>

                <label for="dbs_date">DBS Issue Date:</label>
                <input type="date" name="dbs_date" id="dbs_date">

                <label for="dbs_reference">DBS Reference Number:</label>
                <input type="text" name="dbs_reference" id="dbs_reference">

                <label for="dbs_update_service">DBS Update Service:</label>
                <select name="dbs_update_service" id="dbs_update_service">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>

                <label for="course_ids">Assign Courses:</label>
                <select name="course_ids[]" id="course_ids" multiple required>
                    <option value="">-- Select one or more courses --</option>
                    <?php
                    if ($courseOptions) {
                        while ($course = $courseOptions->fetch()):
                    ?>
                        <option value="<?= htmlspecialchars($course['course_id']) ?>">
                            <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php
                        endwhile;
                    }
                    ?>
                </select>
                <small>Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <label for="profile_image">Profile Picture:</label>
                <input type="file" name="profile_image" id="profile_image" accept="image/*">

                <button type="submit">Add Tutor</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>