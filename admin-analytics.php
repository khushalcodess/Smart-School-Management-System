<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

// Total counts
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$totalTeachers = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();

// Students grouped by class
$byClass = $pdo->query(
    "SELECT class, COUNT(*) AS total FROM students GROUP BY class ORDER BY class"
)->fetchAll();

// Teachers grouped by department
$byDepartment = $pdo->query(
    "SELECT department, COUNT(*) AS total FROM teachers GROUP BY department"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Analytics & Reports</title>
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Analytics & Reports</h1>

  <h2>Overview</h2>
  <p>Total Students: <strong><?php echo $totalStudents; ?></strong></p>
  <p>Total Teachers: <strong><?php echo $totalTeachers; ?></strong></p>

  <h2>Students by Class</h2>
  <table border="1" cellpadding="6">
    <tr><th>Class</th><th>Number of Students</th></tr>
    <?php foreach ($byClass as $row): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['class']); ?></td>
      <td><?php echo $row['total']; ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <h2>Teachers by Department</h2>
  <table border="1" cellpadding="6">
    <tr><th>Department</th><th>Number of Teachers</th></tr>
    <?php foreach ($byDepartment as $row): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['department']); ?></td>
      <td><?php echo $row['total']; ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>