<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff', 'tutor', 'trainee'])) {
    die("Access denied");
}

$assignment_id = $_GET['id'] ?? null;
if (!$assignment_id) die("No assignment specified");

$stmt = $pdo->prepare("SELECT trainee_id FROM trainees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainee = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['file']['name'])) {
        $targetDir = "uploads/submissions/";
        $fileName = uniqid() . "_" . basename($_FILES["file"]["name"]);
        $targetFile = $targetDir . $fileName;
        move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile);

        $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, trainee_id, file_path) VALUES (?,?,?)");
        $stmt->execute([$assignment_id, $trainee['trainee_id'], $targetFile]);

        logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'upload_submission', "Submitted assignment ID $assignment_id");
        header("Location: my_assignments.php");
        exit;
    }
}
?>
<!-- Your upload form HTML here -->