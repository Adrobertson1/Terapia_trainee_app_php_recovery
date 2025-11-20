<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$assignment_id = $_GET['id'] ?? null;         // trainee_courses.id
$trainee_id    = $_GET['trainee_id'] ?? null; // trainees.trainee_id

if (!$assignment_id || !$trainee_id) {
    die("Invalid request.");
}

// Confirm the assignment exists and belongs to the trainee
$checkStmt = $pdo->prepare("
    SELECT tc.course_id, c.course_name
    FROM trainee_courses tc
    JOIN courses c ON tc.course_id = c.course_id
    WHERE tc.id = ? AND tc.trainee_id = ?
");
$checkStmt->execute([$assignment_id, $trainee_id]);
$assignment = $checkStmt->fetch();

if (!$assignment) {
    die("Course assignment not found or does not match trainee.");
}

// Delete the assignment
$deleteStmt = $pdo->prepare("DELETE FROM trainee_courses WHERE id = ?");
$deleteStmt->execute([$assignment_id]);

// Log the action in assignment_logs for full auditability
$logStmt = $pdo->prepare("
    INSERT INTO assignment_logs (action, assignment_id, actor_id, user_type, timestamp)
    VALUES ('course_deleted', ?, ?, ?, NOW())
");
$logStmt->execute([
    $assignment_id,               // ✅ trainee_courses.id
    $_SESSION['user_id'],         // ✅ actor_id
    $_SESSION['role']             // ✅ user_type
]);

// Redirect back to trainee profile
header("Location: view_trainee.php?id=" . $trainee_id);
exit;