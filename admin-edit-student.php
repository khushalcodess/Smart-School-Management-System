<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: admin-students.php');
    exit;
}

// If the form was submitted, UPDATE the record
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);
    $rollNo = $_POST['roll_no'];

    $stmt1 = $pdo->prepare("UPDATE users SET full_name = :name, email = :email WHERE id = :id");
    $stmt1->execute(['name' => $fullName, 'email' => $email, 'id' => $id]);

    $stmt2 = $pdo->prepare("UPDATE students SET class = :class, section = :section, roll_no = :roll_no WHERE student_id = :id");
    $stmt2->execute(['class' => $class, 'section' => $section, 'roll_no' => $rollNo, 'id' => $id]);

    header('Location: admin-students.php');
    exit;
}

// Otherwise, LOAD the existing data to pre-fill the form
$stmt = $pdo->prepare(
    "SELECT users.id, users.full_name, users.email, students.class, students.section, students.roll_no
     FROM users JOIN students ON users.id = students.student_id
     WHERE users.id = :id"
);
$stmt->execute(['id' => $id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: admin-students.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Student</title>
</head>
<body>

  <a href="admin-students.php">&larr; Back to Manage Students</a>
  <h1>Edit Student</h1>

  <form method="POST">
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($student['full_name']); ?>" required><br>
    <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required><br>
    <input type="text" name="class" value="<?php echo htmlspecialchars($student['class']); ?>" required><br>
    <input type="text" name="section" value="<?php echo htmlspecialchars($student['section']); ?>"><br>
    <input type="number" name="roll_no" value="<?php echo htmlspecialchars($student['roll_no']); ?>"><br>
    <button type="submit">Save Changes</button>
  </form>

</body>
</html>