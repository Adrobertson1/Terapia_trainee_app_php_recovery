<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$trainee_id = $_POST['trainee_id'] ?? null;

if ($trainee_id) {
  $stmt = $pdo->prepare("UPDATE trainees SET is_archived = 0 WHERE trainee_id = ?");
  $stmt->execute([$trainee_id]);
}

header("Location: trainees.php?view=archived&restored=1");
exit;