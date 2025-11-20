<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    die("Access denied. No user session found.");
}

$user_id = $_SESSION['user_id'];

// Fetch calendar events for the logged-in user
$stmt = $pdo->prepare("
    SELECT event_id, title, event_date, notes
    FROM user_calendar_events
    WHERE user_id = ?
    ORDER BY event_date ASC
");
$stmt->execute([$user_id]);
$rows = $stmt->fetchAll();

// Format events for FullCalendar
$events = [];
foreach ($rows as $row) {
    $events[] = [
        'id' => $row['event_id'],
        'title' => $row['title'],
        'start' => date('c', strtotime($row['event_date'])),
        'description' => $row['notes']
    ];
}

$today = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Calendar</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(to bottom right, #F4F0F8, #E6D6EC);
      color: #333;
      margin: 0;
      padding: 0;
    }
    .dashboard-wrapper {
      display: flex;
      flex-direction: row;
    }
    .main-content {
      flex: 1;
      padding: 40px;
      background-color: #fff;
      border-top-left-radius: 16px;
      border-bottom-left-radius: 16px;
      box-shadow: -4px 0 12px rgba(0,0,0,0.05);
      min-height: 100vh;
    }
    h2 {
      color: #850069;
      font-family: 'Josefin Sans', sans-serif;
      font-size: 28px;
      margin-bottom: 30px;
    }
    .calendar-intro {
      background-color: #F4F0F8;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }
    .calendar-intro p {
      margin: 6px 0;
      font-size: 15px;
    }
    .calendar-intro strong {
      color: #850069;
    }
    .calendar-filters {
      display: flex;
      gap: 20px;
      margin-top: 16px;
    }
    .calendar-filters label {
      font-weight: bold;
      color: #850069;
      display: block;
      margin-bottom: 6px;
    }
    .calendar-filters select {
      padding: 8px;
      border-radius: 6px;
      border: 1px solid #ccc;
      font-family: 'Inter', sans-serif;
    }
    #calendar {
      background: #fff;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      font-size: 15px;
      max-width: 1000px;
      margin: 0 auto;
    }
    .fc-toolbar-title {
      font-family: 'Josefin Sans', sans-serif;
      font-size: 22px;
      color: #850069;
    }
    .fc-button {
      background-color: #850069 !important;
      border: none !important;
      border-radius: 6px !important;
      padding: 6px 12px !important;
      font-weight: bold;
    }
    .fc-button:hover {
      background-color: #BB9DC6 !important;
    }
    .fc-event {
      cursor: pointer;
      background-color: #BB9DC6 !important;
      border: none !important;
      border-radius: 4px !important;
      padding: 2px 6px;
      font-size: 13px;
    }
    .fc-daygrid-day-frame {
      position: relative;
    }
    .fc-add-prompt {
      position: absolute;
      bottom: 4px;
      right: 4px;
      font-size: 11px;
      color: #850069;
      text-align: center;
      cursor: pointer;
      background-color: #F4F0F8;
      border-radius: 4px;
      padding: 2px 4px;
      transition: background-color 0.2s ease;
    }
    .fc-add-prompt:hover {
      background-color: #E6D6EC;
    }
    .fc-add-prompt span {
      display: block;
      font-size: 16px;
      line-height: 1;
    }
  </style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="dashboard-wrapper">
  <?php include 'sidebar.php'; ?>
  <div class="main-content">
    <h2>📅 My Calendar</h2>

    <div class="calendar-intro">
      <p><strong>🗓️ Today is: <?= $today ?></strong></p>
      <p>Click on any day to view or add events. Existing events are shown in lavender. Use the filters below to narrow your view.</p>
      <div class="calendar-filters">
        <div>
          <label for="filter-type">Filter by Type:</label>
          <select id="filter-type">
            <option value="">All</option>
            <option value="General">General</option>
            <option value="Tutorial">Tutorial</option>
            <option value="Supervision">Supervision</option>
          </select>
        </div>
        <div>
          <label for="filter-recurrence">Filter by Recurrence:</label>
          <select id="filter-recurrence">
            <option value="">All</option>
            <option value="none">None</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
          </select>
        </div>
      </div>
    </div>

    <div id="calendar"></div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      events: <?= json_encode($events) ?>,
      editable: false,
      selectable: true,
      dateClick: function(info) {
        const targetDate = info.dateStr;
        window.location.href = "view_day.php?date=" + targetDate;
      },
      eventClick: function(info) {
        const eventTitle = info.event.title;
        const eventNotes = info.event.extendedProps.description || 'None';
        const eventTime = new Date(info.event.start).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        alert(
          '🗓️ Event: ' + eventTitle +
          '\n⏰ Time: ' + eventTime +
          '\n\n📝 Notes:\n' + eventNotes
        );
      },
      dayCellDidMount: function(info) {
        const addPrompt = document.createElement('div');
        addPrompt.className = 'fc-add-prompt';
        addPrompt.innerHTML = '<span>➕</span>Add';
        addPrompt.onclick = function(e) {
          e.stopPropagation();
          window.location.href = "view_day.php?date=" + info.date.toISOString().split('T')[0];
        };
        info.el.querySelector('.fc-daygrid-day-frame')?.appendChild(addPrompt);
      }
    });
    calendar.render();
  });
</script>
</body>
</html>