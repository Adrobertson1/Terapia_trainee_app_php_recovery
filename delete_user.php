<?php
session_start();
require 'db.php';

if (!in_array($_SESSION['role'], ['superuser', 'admin',])) {
  die("Access denied.");
}

$user_id = $_GET['id'] ?? null;
if (!$user_id || !is_numeric($user_id)) {
  die("Invalid user ID.");
}

try {
  $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ?");
  $stmt->execute([$user_id]);
  header("Location: manage_admins.php?deleted=1");
  exit;
} catch (PDOException $e) {
  die("Error deleting user: " . $e->getMessage());
}