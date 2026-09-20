<?php
session_start();

// Protect this page: only a logged-in admin can see it
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}
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

  <nav>
    <ul>
      <li><a href="admin-students.php">Manage Students</a></li>
      <li><a href="admin-teachers.php">Manage Teachers</a></li>
      <li><a href="admin-announcements.php">Announcements</a></li>
      <li><a href="admin-notices.php">Notices</a></li>
      <li><a href="admin-events.php">Events</a></li>
      <li><a href="#">Timetable</a></li>
      <li><a href="#">Gallery</a></li>
      <li><a href="backend/logout.php">Logout</a></li>
    </ul>
  </nav>

</body>
</html>