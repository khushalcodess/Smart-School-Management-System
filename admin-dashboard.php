<?php
session_start();
require 'backend/config.php';

// Protect this page: only a logged-in admin can see it
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

// Quick stats for the dashboard summary
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard — Vidya Bharati Public School</title>
</head>
<body>

  <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
  <p>You are logged in as: <?php echo htmlspecialchars($_SESSION['role']); ?></p>

<div style="display:flex; gap:20px; margin:20px 0;">
  <div style="border:1px solid #ccc; padding:16px; width:150px;">
    <h3><?php echo $totalStudents; ?></h3>
    <p>Total Students</p>
  </div>
  <div style="border:1px solid #ccc; padding:16px; width:150px;">
    <h3><?php echo $totalTeachers; ?></h3>
    <p>Total Teachers</p>
  </div>
</div>

  <nav>
    <ul>
      <li><a href="admin-analytics.php">Analytics & Reports</a></li>
      <li><a href="admin-students.php">Manage Students</a></li>
      <li><a href="admin-teachers.php">Manage Teachers</a></li>
      <li><a href="admin-announcements.php">Announcements</a></li>
      <li><a href="admin-notices.php">Notices</a></li>
      <li><a href="admin-events.php">Events</a></li>
      <li><a href="admin-timetable.php">Timetable</a></li>
      <li><a href="admin-gallery.php">Gallery</a></li>
      <li><a href="backend/logout.php">Logout</a></li>
    </ul>
  </nav>

</body>
</html>