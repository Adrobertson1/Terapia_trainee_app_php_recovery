<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin'])) {
  die("Access denied.");
}

$staff_id = $_POST['staff_id'] ?? null;

if ($staff_id) {
  $stmt = $pdo->prepare("UPDATE staff SET is_archived = 1 WHERE staff_id = ?");
  $stmt->execute([$staff_id]);
}

header("Location: staff.php?archived=1");
exit;