<?php
session_start();
require 'db.php';

if ($_SESSION['role'] !== 'admin', 'superuser') {
  die("Access denied.");
}

$staff_id = $_GET['id'] ?? null;
if (!$staff_id || !is_numeric($staff_id)) {
  die("Invalid staff ID.");
}

try {
  $stmt = $pdo->prepare("UPDATE staff SET is_archived = 0 WHERE staff_id = ?");
  $stmt->execute([$staff_id]);
  header("Location: archived_admin.php?restored=1");
  exit;
} catch (PDOException $e) {
  die("Error restoring admin: " . $e->getMessage());
}