<?php
session_start();
require 'db.php';
require_once 'functions.php';

if (!in_array($_SESSION['role'], ['superuser', 'staff', 'tutor', 'admin', 'trainee'])) {
    die("Access denied");
}

$isTrainee = ($_SESSION['role'] === 'trainee');
if (!$isTrainee) {
    $users = $pdo->query("SELECT user_id, username FROM users ORDER BY username")->fetchAll();
}

$prefill_date = $_GET['date'] ?? '';
$default_datetime = '';
if ($prefill_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $prefill_date)) {
    $default_datetime = $prefill_date . 'T09:00';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $isTrainee ? $_SESSION['user_id'] : $_POST['user_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_datetime = $_POST['event_datetime'];
    $event_type = $_POST['event_type'];
    $color_code = $_POST['color_code'];
    $recurrence = $_POST['recurrence'];
    $created_by = $_SESSION['role'];

    $group_id = ($recurrence !== 'none') ? uniqid('rec_', true) : null;

    $stmt = $pdo->prepare("INSERT INTO user_calendar_events 
        (user_id, title, description, event_date, created_by, event_type, color_code, recurrence, recurrence_group_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $title,
        $description,
        $event_datetime,
        $created_by,
        $event_type,
        $color_code,
        $recurrence,
        $group_id
    ]);

    logAction($pdo, $_SESSION['user_id'], $_SESSION['role'], 'add_event', "Created $event_type event for user ID $user_id");

    if ($recurrence !== 'none') {
        $baseEvent = [
            'user_id' => $user_id,
            'title' => $title,
            'description' => $description,
            'event_date' => $event_datetime,
            'created_by' => $created_by,
            'event_type' => $event_type,
            'color_code' => $color_code,
            'recurrence' => $recurrence
        ];
        generateRecurringEvents($pdo, $baseEvent, $recurrence, $group_id);
    }

    header("Location: add_event.php?success=1");
    exit;
}

function generateRecurringEvents($pdo, $baseEvent, $interval, $group_id, $count = 10) {
    $date = new DateTime($baseEvent['event_date']);

    for ($i = 1; $i <= $count; $i++) {
        $date->modify($interval === 'weekly' ? '+1 week' : '+1 month');

        $stmt = $pdo->prepare("INSERT INTO user_calendar_events 
            (user_id, title, description, event_date, created_by, event_type, color_code, recurrence, recurrence_group_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $baseEvent['user_id'],
            $baseEvent['title'],
            $baseEvent['description'],
            $date->format('Y-m-d H:i:s'),
            $baseEvent['created_by'],
            $baseEvent['event_type'],
            $baseEvent['color_code'],
            $baseEvent['recurrence'],
            $group_id
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add Calendar Event</title>
  <link rel="stylesheet" href="style.css">
  <style>
    .main-content { padding: 40px; }
    .event-form-container {
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      max-width: 700px;
      margin: 0 auto;
      font-family: 'Inter', sans-serif;
    }
    .event-form-container h2 {
      color: #850069;
      margin-bottom: 20px;
    }
    .event-form-container label {
      font-weight: bold;
      display: block;
      margin-top: 15px;
      margin-bottom: 5px;
    }
    .event-form-container input,
    .event-form-container textarea,
    .event-form-container select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1em;
    }
    .event-form-container button {
      margin-top: 20px;
      background-color: #850069;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 1em;
    }
    .event-form-container button:hover {
      background-color: #a0007a;
    }
    .success-message {
      background-color: #dff0d8;
      color: #2e7d32;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: bold;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <div class="event-form-container">
      <h2>Add Calendar Event</h2>
      <?php if (isset($_GET['success'])): ?>
        <div class="success-message">✅ Event added successfully!</div>
      <?php endif; ?>

      <form method="post">
        <?php if ($isTrainee): ?>
          <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
          <p><strong>Note:</strong> You are adding this event to your own calendar.</p>
        <?php else: ?>
          <label for="user_id">Select User:</label>
          <select name="user_id" id="user_id" required>
            <option value="">-- Choose --</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>

        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>

        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="3"></textarea>

        <label for="event_datetime">Date & Time:</label>
        <input type="datetime-local" name="event_datetime" id="event_datetime" value="<?= htmlspecialchars($default_datetime) ?>" required>

        <label for="event_type">Event Type:</label>
        <select name="event_type" id="event_type" required>
          <option value="Tutorial">Tutorial</option>
          <option value="Exam">Exam</option>
          <option value="Meeting">Meeting</option>
          <option value="Deadline">Deadline</option>
          <option value="General">General</option>
        </select>

        <label for="color_code">Color Code:</label>
        <select name="color_code" id="color_code" required>
          <option value="#BB9DC6" style="background-color:#BB9DC6; color:#000;">🟣 Default Purple – General</option>
          <option value="#1A73E8" style="background-color:#1A73E8; color:#fff;">🔵 Outlook Blue – Tutorial</option>
          <option value="#34A853" style="background-color:#34A853; color:#fff;">🟢 Outlook Green – Confirmed</option>
          <option value="#EA4335" style="background-color:#EA4335; color:#fff;">🔴 Outlook Red – Important / Deadline</option>
          <option value="#FBBC05" style="background-color:#FBBC05; color:#000;">🟡 Outlook Yellow – Reminder</option>
          <option value="#9E9E9E" style="background-color:#9E9E9E; color:#000;">⚪ Outlook Gray – Out of Office</option>
        </select>

        <label for="recurrence">Recurrence:</label>
        <select name="recurrence" id="recurrence">
          <option value="none">None</option>
          <option value="weekly">Weekly</option>
          <option value="monthly">Monthly</option>
        </select>

        <button type="submit">Add Event</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>