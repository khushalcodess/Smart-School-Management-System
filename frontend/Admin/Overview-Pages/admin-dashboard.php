<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
$totalNotices  = $pdo->query("SELECT COUNT(*) FROM notices")->fetchColumn();
$totalEvents   = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn();

// Students by class, for the bar chart
$byClass = $pdo->query("SELECT class, COUNT(*) AS total FROM students GROUP BY class ORDER BY class")->fetchAll();
$classLabels = array_column($byClass, 'class');
$classCounts = array_column($byClass, 'total');

// Upcoming events
$upcomingEvents = $pdo->query(
    "SELECT title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5"
)->fetchAll();

// Recently added students
$recentStudents = $pdo->query(
    "SELECT users.full_name, users.email, students.class, students.roll_no
     FROM users JOIN students ON users.id = students.student_id
     ORDER BY users.id DESC LIMIT 5"
)->fetchAll();

// Attendance % for the current month
$attendanceStats = $pdo->query(
    "SELECT status, COUNT(*) AS total FROM attendance
     WHERE MONTH(attendance_date) = MONTH(CURDATE()) AND YEAR(attendance_date) = YEAR(CURDATE())
     GROUP BY status"
)->fetchAll();

$present = 0; $absent = 0;
foreach ($attendanceStats as $row) {
    if (strtolower($row['status']) === 'present') $present = $row['total'];
    if (strtolower($row['status']) === 'absent') $absent = $row['total'];
}
$attendanceTotal = $present + $absent;
$attendancePercent = $attendanceTotal > 0 ? round(($present / $attendanceTotal) * 100) : null;

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Vidya Bharati Public School</title>
<link rel="stylesheet" href="../../Style/admin.css">
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>
    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/admin-dashboard.php" class="active">Dashboard</a></li>
      <li><a href="../Overview-Pages/admin-analytics.php">Analytics & Reports</a></li>
    </ul>
    <div class="nav-label">People</div>
    <ul>
      <li><a href="../People-Pages/admin-students.php">Manage Students</a></li>
      <li><a href="../People-Pages/admin-teachers.php">Manage Teachers</a></li>
    </ul>
    <div class="nav-label">Communication</div>
    <ul>
      <li><a href="../Communication-Pages/admin-announcements.php">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-announcements.php">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-events.php">Events</a></li>
    </ul>
    <div class="nav-label">School</div>
    <ul>
      <li><a href="../School-Pages/admin-timetable.php">Timetable</a></li>
      <li><a href="../School-Pages/admin-gallery.php">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb">Admin / Dashboard</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1><br>

      <div class="icon-stat-cards">
        <div class="icon-stat-card">
          <div class="icon-circle blue">🎓</div>
          <div class="stat-info"><span>Students</span><strong><?php echo $totalStudents; ?></strong></div>
        </div>
        <div class="icon-stat-card">
          <div class="icon-circle gold">🧑‍🏫</div>
          <div class="stat-info"><span>Teachers</span><strong><?php echo $totalTeachers; ?></strong></div>
        </div>
        <div class="icon-stat-card">
          <div class="icon-circle sage">📢</div>
          <div class="stat-info"><span>Notices Posted</span><strong><?php echo $totalNotices; ?></strong></div>
        </div>
        <div class="icon-stat-card">
          <div class="icon-circle rose">📅</div>
          <div class="stat-info"><span>Upcoming Events</span><strong><?php echo $totalEvents; ?></strong></div>
        </div>
      </div>

      <div class="dash-grid">
        <div class="panel-card">
          <h2>Students by Class</h2>
          <canvas id="classChart" height="90"></canvas>
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

      <div class="dash-grid">
        <div class="panel-card">
          <h2>Recently Added Students</h2>
          <table class="admin-table">
            <tr><th>Name</th><th>Email</th><th>Class</th><th>Roll No</th></tr>
            <?php foreach ($recentStudents as $s): ?>
            <tr>
              <td><?php echo htmlspecialchars($s['full_name']); ?></td>
              <td><?php echo htmlspecialchars($s['email']); ?></td>
              <td><?php echo htmlspecialchars($s['class']); ?></td>
              <td><?php echo htmlspecialchars($s['roll_no']); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentStudents)): ?>
              <tr><td colspan="4" style="text-align:center; color:var(--ink-soft);">No students yet.</td></tr>
            <?php endif; ?>
          </table>
        </div>

        <div class="panel-card">
          <h2>Attendance This Month</h2>
          <div class="donut-wrap">
            <?php if ($attendancePercent === null): ?>
              <p style="color:var(--ink-soft); font-size:13.5px; padding:30px 0;">No attendance recorded yet.</p>
            <?php else: ?>
              <canvas id="attendanceDonut" width="160" height="160"></canvas>
              <div class="donut-percent"><?php echo $attendancePercent; ?>%</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
  const classLabels = <?php echo json_encode($classLabels); ?>;
  const classCounts = <?php echo json_encode($classCounts); ?>;

  new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: {
      labels: classLabels,
      datasets: [{
        label: 'Students',
        data: classCounts,
        backgroundColor: '#C8963E',
        borderRadius: 4
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

  <?php if ($attendancePercent !== null): ?>
  new Chart(document.getElementById('attendanceDonut'), {
    type: 'doughnut',
    data: {
      labels: ['Present', 'Absent'],
      datasets: [{
        data: [<?php echo $present; ?>, <?php echo $absent; ?>],
        backgroundColor: ['#C8963E', '#E4E0D6'],
        borderWidth: 0
      }]
    },
    options: {
      cutout: '75%',
      plugins: { legend: { position: 'bottom' } }
    }
  });
  <?php endif; ?>
</script>
</body>
</html>