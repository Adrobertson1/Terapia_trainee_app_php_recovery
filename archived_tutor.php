<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin', 'staff'])) {
  die("Access denied.");
}

$tutor_id = $_POST['tutor_id'] ?? null;

if ($tutor_id) {
  $stmt = $pdo->prepare("UPDATE tutors SET is_archived = 1 WHERE tutor_id = ?");
  $stmt->execute([$tutor_id]);
}

header("Location: tutors.php?archived=1");
exit;