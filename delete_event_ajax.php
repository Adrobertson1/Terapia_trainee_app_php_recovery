<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'error';
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'] ?? null;

if (!$event_id || !is_numeric($event_id)) {
    echo 'error';
    exit;
}

$stmt = $pdo->prepare("DELETE FROM user_calendar_events WHERE event_id = ? AND user_id = ?");
$success = $stmt->execute([$event_id, $user_id]);

echo $success ? 'success' : 'error';