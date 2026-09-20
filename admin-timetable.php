<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = trim($_POST['class']);
    $day = $_POST['day'];
    $period = $_POST['period'];
    $subject = trim($_POST['subject']);
    $teacherId = $_POST['teacher_id'];

    $stmt = $pdo->prepare(
        "INSERT INTO timetable (class, day, period, subject, teacher_id) VALUES (:class, :day, :period, :subject, :teacher_id)"
    );
    $stmt->execute([
        'class' => $class,
        'day' => $day,
        'period' => $period,
        'subject' => $subject,
        'teacher_id' => $teacherId
    ]);

    header('Location: admin-timetable.php');
    exit;
}

// Fetch all teachers to populate the dropdown
$teachers = $pdo->query(
    "SELECT users.id, users.full_name, teachers.subject
     FROM users JOIN teachers ON users.id = teachers.teacher_id"
)->fetchAll();

// Fetch existing timetable entries
$timetable = $pdo->query(
    "SELECT timetable.id, timetable.class, timetable.day, timetable.period, timetable.subject, users.full_name
     FROM timetable
     JOIN users ON timetable.teacher_id = users.id
     ORDER BY timetable.class, FIELD(timetable.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), timetable.period"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Timetable</title>
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Timetable</h1>

  <h2>Add Timetable Slot</h2>
  <form method="POST">
    <input type="text" name="class" placeholder="Class (e.g. 10-A)" required><br>

    <select name="day" required>
      <option value="">-- Select Day --</option>
      <option value="Monday">Monday</option>
      <option value="Tuesday">Tuesday</option>
      <option value="Wednesday">Wednesday</option>
      <option value="Thursday">Thursday</option>
      <option value="Friday">Friday</option>
      <option value="Saturday">Saturday</option>
    </select><br>

    <input type="number" name="period" placeholder="Period (e.g. 1)" required><br>
    <input type="text" name="subject" placeholder="Subject" required><br>

    <select name="teacher_id" required>
      <option value="">-- Select Teacher --</option>
      <?php foreach ($teachers as $t): ?>
        <option value="<?php echo $t['id']; ?>">
          <?php echo htmlspecialchars($t['full_name']) . ' (' . htmlspecialchars($t['subject']) . ')'; ?>
        </option>
      <?php endforeach; ?>
    </select><br>

    <button type="submit">Add Slot</button>
  </form>

  <h2>Full Timetable</h2>
  <table border="1" cellpadding="6">
    <tr>
      <th>Class</th><th>Day</th><th>Period</th><th>Subject</th><th>Teacher</th><th>Actions</th>
    </tr>
    <?php foreach ($timetable as $row): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['class']); ?></td>
      <td><?php echo htmlspecialchars($row['day']); ?></td>
      <td><?php echo htmlspecialchars($row['period']); ?></td>
      <td><?php echo htmlspecialchars($row['subject']); ?></td>
      <td><?php echo htmlspecialchars($row['full_name']); ?></td>
      <td>
        <a href="admin-delete-timetable.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this slot?');">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>