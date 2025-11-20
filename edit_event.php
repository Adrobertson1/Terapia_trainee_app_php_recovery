<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}

$user_id = $_SESSION['user_id'];
$event_id = $_GET['event_id'] ?? null;

if (!$event_id || !is_numeric($event_id)) {
    die("Invalid event ID.");
}

// Fetch event details
$stmt = $pdo->prepare("
    SELECT * FROM user_calendar_events
    WHERE event_id = ? AND user_id = ?
");
$stmt->execute([$event_id, $user_id]);
$event = $stmt->fetch();

if (!$event) {
    die("Event not found or access denied.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_type = $_POST['event_type'] ?? 'General';
    $color_code = $_POST['color_code'] ?? '#BB9DC6';
    $recurrence = $_POST['recurrence'] ?? 'none';

    if ($title && $event_date) {
        $stmt = $pdo->prepare("
            UPDATE user_calendar_events
            SET title = ?, description = ?, event_date = ?, event_type = ?, color_code = ?, recurrence = ?
            WHERE event_id = ? AND user_id = ?
        ");
        $stmt->execute([
            $title,
            $description,
            $event_date,
            $event_type,
            $color_code,
            $recurrence,
            $event_id,
            $user_id
        ]);

        // Redirect to day view
        $redirect_date = date('Y-m-d', strtotime($event_date));
        header("Location: view_day.php?date=" . urlencode($redirect_date));
        exit;
    } else {
        $error = "Title and date/time are required.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Event</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #F4F0F8;
      color: #000;
    }
    .form-wrapper {
      max-width: 600px;
      margin: 60px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    h2 {
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
      margin-bottom: 20px;
    }
    label {
      display: block;
      margin-top: 16px;
      font-weight: bold;
    }
    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-family: 'Inter', sans-serif;
    }
    button {
      margin-top: 20px;
      padding: 12px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      cursor: pointer;
    }
    button:hover {
      background-color: #BB9DC6;
    }
    .error {
      margin-top: 15px;
      color: #c00;
      font-weight: bold;
    }
  </style>
</head>
<body>
<div class="form-wrapper">
  <h2>Edit Event</h2>

  <?php if (!empty($error)): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post">
    <label>Title:</label>
    <input type="text" name="title" value="<?= htmlspecialchars($event['title']) ?>" required>

    <label>Description:</label>
    <textarea name="description"><?= htmlspecialchars($event['description'] ?? $event['notes']) ?></textarea>

    <label>Date & Time:</label>
    <input type="datetime-local" name="event_date" value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>" required>

    <label>Event Type:</label>
    <select name="event_type">
      <option value="General" <?= $event['event_type'] === 'General' ? 'selected' : '' ?>>General</option>
      <option value="Tutorial" <?= $event['event_type'] === 'Tutorial' ? 'selected' : '' ?>>Tutorial</option>
      <option value="Supervision" <?= $event['event_type'] === 'Supervision' ? 'selected' : '' ?>>Supervision</option>
    </select>

    <label>Color Code:</label>
    <select name="color_code">
      <option value="#BB9DC6" <?= $event['color_code'] === '#BB9DC6' ? 'selected' : '' ?>>🟣 Default Purple – General</option>
      <option value="#4CAF50" <?= $event['color_code'] === '#4CAF50' ? 'selected' : '' ?>>🟢 Green – Tutorial</option>
      <option value="#2196F3" <?= $event['color_code'] === '#2196F3' ? 'selected' : '' ?>>🔵 Blue – Supervision</option>
      <option value="#FF9800" <?= $event['color_code'] === '#FF9800' ? 'selected' : '' ?>>🟠 Orange – Other</option>
    </select>

    <label>Recurrence:</label>
    <select name="recurrence">
      <option value="none" <?= $event['recurrence'] === 'none' ? 'selected' : '' ?>>None</option>
      <option value="weekly" <?= $event['recurrence'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
      <option value="monthly" <?= $event['recurrence'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
    </select>

    <button type="submit">💾 Save Changes</button>
  </form>
</div>
</body>
</html>