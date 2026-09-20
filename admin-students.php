<?php
session_start();
require 'backend/config.php';

// Protect this page — same check as the dashboard
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

// Handle the "Add Student" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);
    $rollNo = $_POST['roll_no'];

    // Step A: insert into users table first
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role) VALUES (:name, :email, :password, 'student')"
    );
    $stmt->execute([
        'name' => $fullName,
        'email' => $email,
        'password' => $hashedPassword
    ]);

    // Step B: get the ID that was just created
    $newUserId = $pdo->lastInsertId();

    // Step C: insert into students table using that same ID
    $stmt2 = $pdo->prepare(
        "INSERT INTO students (student_id, class, section, roll_no) VALUES (:id, :class, :section, :roll_no)"
    );
    $stmt2->execute([
        'id' => $newUserId,
        'class' => $class,
        'section' => $section,
        'roll_no' => $rollNo
    ]);

    header('Location: admin-students.php');
    exit;
}

// Fetch all students to display in the table below
$students = $pdo->query(
    "SELECT users.id, users.full_name, users.email, students.class, students.section, students.roll_no
     FROM users
     JOIN students ON users.id = students.student_id"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Students</title>
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Manage Students</h1>

  <h2>Add New Student</h2>
  <form method="POST">
    <input type="text" name="full_name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="text" name="class" placeholder="Class (e.g. 10)" required><br>
    <input type="text" name="section" placeholder="Section (e.g. A)"><br>
    <input type="number" name="roll_no" placeholder="Roll No"><br>
    <button type="submit">Add Student</button>
  </form>

  <h2>Existing Students</h2>
  <table border="1" cellpadding="6">
    <tr>
     <th>Name</th><th>Email</th><th>Class</th><th>Section</th><th>Roll No</th><th>Actions</th>
    </tr>
    <?php foreach ($students as $s): ?>
    <tr>
      <td><?php echo htmlspecialchars($s['full_name']); ?></td>
      <td><?php echo htmlspecialchars($s['email']); ?></td>
      <td><?php echo htmlspecialchars($s['class']); ?></td>
      <td><?php echo htmlspecialchars($s['section']); ?></td>
      <td><?php echo htmlspecialchars($s['roll_no']); ?></td>
<td>
  <a href="admin-edit-student.php?id=<?php echo $s['id']; ?>">Edit</a> |
  <a href="admin-delete-student.php?id=<?php echo $s['id']; ?>" onclick="return confirm('Delete this student?');">Delete</a>
</td>
</tr>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>