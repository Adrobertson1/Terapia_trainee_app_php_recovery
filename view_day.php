<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied. No user session found.");
}

$user_id = $_SESSION['user_id'];
$target_date = $_GET['date'] ?? '';

if (!$target_date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $target_date)) {
    die("Invalid or missing date.");
}

// Fetch events for this user on the given date
$stmt = $pdo->prepare("
    SELECT event_id, title, description, event_date, event_type, color_code, recurrence, notes
    FROM user_calendar_events
    WHERE user_id = ? AND DATE(event_date) = ?
    ORDER BY event_date ASC
");
$stmt->execute([$user_id, $target_date]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Events for <?= htmlspecialchars($target_date) ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #F4F0F8;
      color: #000;
    }
    .dashboard-wrapper {
      display: flex;
      flex-direction: row;
    }
    .main-content {
      flex: 1;
      padding: 40px;
    }
    h2 {
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
      margin-bottom: 20px;
    }
    .event-card {
      background: #fff;
      padding: 20px;
      margin-bottom: 20px;
      border-left: 6px solid #6a1b9a;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    }
    .event-card h3 {
      margin-top: 0;
      color: #6a1b9a;
    }
    .event-meta {
      font-size: 14px;
      color: #555;
      margin-top: 8px;
    }
    .add-button {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 16px;
      background-color: #6a1b9a;
      color: white;
      border: none;
      border-radius: 6px;
      font-weight: bold;
      text-decoration: none;
      margin-right: 10px;
      cursor: pointer;
    }
    .add-button:hover {
      background-color: #BB9DC6;
    }
    .delete-button {
      background-color: #c62828;
    }
    .delete-button:hover {
      background-color: #e53935;
    }
    .empty-state {
      background-color: #fff3cd;
      border-left: 6px solid #ffc107;
      padding: 20px;
      border-radius: 6px;
      font-weight: bold;
    }
    .success-message {
      background-color: #dff0d8;
      border-left: 6px solid #4CAF50;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-weight: bold;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>📅 Events for <?= date('d/m/Y', strtotime($target_date)) ?></h2>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
      <div class="success-message">✅ Event successfully deleted.</div>
    <?php endif; ?>

    <?php if (count($events) > 0): ?>
      <?php foreach ($events as $e): ?>
        <div class="event-card">
          <h3><?= htmlspecialchars($e['title']) ?></h3>
          <div class="event-meta">
            <strong>Date & Time:</strong> <?= date('d/m/Y H:i', strtotime($e['event_date'])) ?><br>
            <strong>Event Type:</strong> <?= htmlspecialchars($e['event_type'] ?? 'General') ?><br>
            <strong>Color Code:</strong> <?= htmlspecialchars($e['color_code'] ?? '🟣 Default Purple – General') ?><br>
            <strong>Recurrence:</strong> <?= htmlspecialchars($e['recurrence'] ?? 'None') ?><br>
            <strong>Description:</strong> <?= nl2br(htmlspecialchars($e['description'] ?? $e['notes'])) ?>
          </div>
          <div style="margin-top: 12px;">
            <a href="edit_event.php?event_id=<?= $e['event_id'] ?>" class="add-button">✏️ Edit</a>
            <button class="add-button delete-button" onclick="confirmDelete(<?= $e['event_id'] ?>)">🗑️ Delete</button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        No events scheduled for this day.
      </div>
    <?php endif; ?>

    <a href="add_event.php?date=<?= urlencode($target_date) ?>" class="add-button">➕ Add Event</a>
  </div>
</div>

<script>
function confirmDelete(eventId) {
  if (confirm("Are you sure you want to delete this event?")) {
    fetch('delete_event_ajax.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'event_id=' + encodeURIComponent(eventId)
    })
    .then(response => response.text())
    .then(result => {
      if (result === 'success') {
        const url = new URL(window.location.href);
        url.searchParams.set('deleted', '1');
        window.location.href = url.toString();
      } else {
        alert('Failed to delete event.');
      }
    });
  }
}
</script>
</body>
</html>