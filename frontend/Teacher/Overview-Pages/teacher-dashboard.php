<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$teacherId = $_SESSION['user_id'];
$today = date('l'); // e.g. "Monday"

// Teacher's own profile
$stmt = $pdo->prepare("SELECT subject, department FROM teachers WHERE teacher_id = :id");
$stmt->execute(['id' => $teacherId]);
$profile = $stmt->fetch();

// Today's periods (only this teacher's slots)
$stmt = $pdo->prepare(
    "SELECT class, period, subject FROM timetable
     WHERE teacher_id = :id AND day = :day
     ORDER BY period"
);
$stmt->execute(['id' => $teacherId, 'day' => $today]);
$todaySlots = $stmt->fetchAll();

// Number of different classes this teacher teaches
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT class) FROM timetable WHERE teacher_id = :id");
$stmt->execute(['id' => $teacherId]);
$classesTaught = $stmt->fetchColumn();

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();

// Upcoming exams created by this teacher
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE created_by = :id AND exam_date >= CURDATE()");
$stmt->execute(['id' => $teacherId]);
$upcomingExams = $stmt->fetchColumn();

// Upcoming school events
$upcomingEvents = $pdo->query(
    "SELECT title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5"
)->fetchAll();

// Greeting based on the hour
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher Dashboard — Vidya Bharati Public School</title>
<link rel="stylesheet" href="../../Style/admin.css">
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>

    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/teacher-dashboard.php" class="active">Dashboard</a></li>
    </ul>

    <div class="nav-label">Academics</div>
    <ul>
      <li><a href="../Academic-Pages/teacher-attendance.php">Mark Attendance</a></li>
      <li><a href="../Academic-Pages/teacher-attendance-records.php">Attendance Records</a></li>
      <li><a href="../Academic-Pages/teacher-homework.php">Homework</a></li>
      <li><a href="../Academic-Pages/teacher-exams.php">Manage Exams</a></li>
    </ul>

    <div class="nav-label">Students</div>
    <ul>
      <li><a href="../People-Pages/teacher-students.php">My Students</a></li>
    </ul>

    <div class="nav-label">School</div>
    <ul>
      <li><a href="../School-Pages/teacher-timetable.php">My Timetable</a></li>
      <li><a href="../School-Pages/teacher-announcements.php">Announcements</a></li>
      <li><a href="../School-Pages/teacher-notices.php">Notices</a></li>
      <li><a href="../School-Pages/teacher-events.php">Events</a></li>
      <li><a href="../School-Pages/teacher-gallery.php">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb">Teacher / Dashboard</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
      <p style="color:var(--ink-soft); margin-top:4px;">
        <?php echo htmlspecialchars($profile['subject'] ?? ''); ?>
        <?php echo !empty($profile['department']) ? ' · ' . htmlspecialchars($profile['department']) . ' Department' : ''; ?>
      </p><br>

      <div class="icon-stat-cards">
        <div class="icon-stat-card">
          <div class="icon-circle blue">🕘</div>
          <div class="stat-info"><span>Periods Today</span><strong><?php echo count($todaySlots); ?></strong></div>
        </div>
        <div class="icon-stat-card">
          <div class="icon-circle gold">🏫</div>
          <div class="stat-info"><span>Classes I Teach</span><strong><?php echo $classesTaught; ?></strong></div>
        </div>
        <div class="icon-stat-card">
          <div class="icon-circle sage">🎓</div>
          <div class="stat-info"><span>Total Students</span><strong><?php echo $totalStudents; ?></strong></div>
        </div>
        <div class="icon-stat-card">
          <div class="icon-circle rose">📝</div>
          <div class="stat-info"><span>Upcoming Exams</span><strong><?php echo $upcomingExams; ?></strong></div>
        </div>
      </div>

      <div class="dash-grid">

        <div class="panel-card">
          <h2>Today's Schedule — <?php echo $today; ?></h2>
          <table class="admin-table">
            <tr><th>Period</th><th>Class</th><th>Subject</th></tr>
            <?php foreach ($todaySlots as $slot): ?>
            <tr>
              <td><?php echo htmlspecialchars($slot['period']); ?></td>
              <td><?php echo htmlspecialchars($slot['class']); ?></td>
              <td><?php echo htmlspecialchars($slot['subject']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($todaySlots)): ?>
              <tr><td colspan="3" style="text-align:center; color:var(--ink-soft);">No periods scheduled for today.</td></tr>
            <?php endif; ?>
          </table>
        </div>

        <div class="panel-card">
          <h2>Upcoming Events</h2>
          <?php if (empty($upcomingEvents)): ?>
            <p style="color:var(--ink-soft); font-size:13.5px;">No upcoming events.</p>
          <?php else: ?>
            <?php foreach ($upcomingEvents as $ev): ?>
              <?php $d = new DateTime($ev['event_date']); ?>
              <div class="event-item">
                <div class="event-date-badge">
                  <span class="day"><?php echo $d->format('d'); ?></span>
                  <span class="mon"><?php echo $d->format('M'); ?></span>
                </div>
                <p><?php echo htmlspecialchars($ev['title']); ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>