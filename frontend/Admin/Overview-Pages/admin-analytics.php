<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();

$byClass = $pdo->query("SELECT class, COUNT(*) AS total FROM students GROUP BY class ORDER BY class")->fetchAll();
$classLabels = array_column($byClass, 'class');
$classCounts = array_column($byClass, 'total');

$byDepartment = $pdo->query("SELECT department, COUNT(*) AS total FROM teachers GROUP BY department")->fetchAll();
$deptLabels = array_column($byDepartment, 'department');
$deptCounts = array_column($byDepartment, 'total');

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics & Reports</title>
<link rel="stylesheet" href="../../Style/admin.css">
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>
    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/admin-dashboard.php">Dashboard</a></li>
      <li><a href="../Overview-Pages/admin-analytics.php" class="active">Analytics & Reports</a></li>
    </ul>
    <div class="nav-label">People</div>
    <ul>
      <li><a href="../People-Pages/admin-students.php">Manage Students</a></li>
      <li><a href="../People-Pages/admin-teachers.php">Manage Teachers</a></li>
    </ul>
    <div class="nav-label">Communication</div>
    <ul>
      <li><a href="../Communication-Pages/admin-announcements.php">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-notices.php">Notices</a></li>
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
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Analytics & Reports</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Analytics & Reports</h1><br>

      <div class="stat-cards">
        <div class="stat-card">
          <h3><?php echo $totalStudents; ?></h3>
          <p>Total Students</p>
          <div class="bar"></div>
        </div>
        <div class="stat-card">
          <h3><?php echo $totalTeachers; ?></h3>
          <p>Total Teachers</p>
          <div class="bar"></div>
        </div>
      </div>

      <div class="dash-grid">
        <div class="panel-card">
          <h2>Students by Class</h2>
          <canvas id="classChart" height="100"></canvas>
        </div>
        <div class="panel-card">
          <h2>Teachers by Department</h2>
          <canvas id="deptChart" height="100"></canvas>
        </div>
      </div>

    </div>
  </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
  new Chart(document.getElementById('classChart'), {
    type: 'bar',
    data: {
      labels: <?php echo json_encode($classLabels); ?>,
      datasets: [{
        label: 'Students',
        data: <?php echo json_encode($classCounts); ?>,
        backgroundColor: '#C8963E',
        borderRadius: 4
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

  new Chart(document.getElementById('deptChart'), {
    type: 'pie',
    data: {
      labels: <?php echo json_encode($deptLabels); ?>,
      datasets: [{
        data: <?php echo json_encode($deptCounts); ?>,
        backgroundColor: ['#132A46', '#C8963E', '#54725A', '#B3261E', '#E4B368']
      }]
    },
    options: {
      plugins: { legend: { position: 'bottom' } }
    }
  });
</script>
</body>
</html>