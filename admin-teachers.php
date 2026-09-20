<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $subject = trim($_POST['subject']);
    $department = trim($_POST['department']);
    $qualification = trim($_POST['qualification']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role) VALUES (:name, :email, :password, 'teacher')"
    );
    $stmt->execute([
        'name' => $fullName,
        'email' => $email,
        'password' => $hashedPassword
    ]);

    $newUserId = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare(
        "INSERT INTO teachers (teacher_id, subject, department, qualification) VALUES (:id, :subject, :department, :qualification)"
    );
    $stmt2->execute([
        'id' => $newUserId,
        'subject' => $subject,
        'department' => $department,
        'qualification' => $qualification
    ]);

    header('Location: admin-teachers.php');
    exit;
}

$teachers = $pdo->query(
    "SELECT users.id, users.full_name, users.email, teachers.subject, teachers.department, teachers.qualification
     FROM users
     JOIN teachers ON users.id = teachers.teacher_id"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Teachers</title>
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Manage Teachers</h1>

  <h2>Add New Teacher</h2>
  <form method="POST">
    <input type="text" name="full_name" placeholder="Full Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <input type="text" name="subject" placeholder="Subject" required><br>
    <input type="text" name="department" placeholder="Department"><br>
    <input type="text" name="qualification" placeholder="Qualification"><br>
    <button type="submit">Add Teacher</button>
  </form>

  <h2>Existing Teachers</h2>
  <table border="1" cellpadding="6">
    <tr>
      <th>Name</th><th>Email</th><th>Subject</th><th>Department</th><th>Qualification</th><th>Actions</th>
    </tr>
    <?php foreach ($teachers as $t): ?>
    <tr>
      <td><?php echo htmlspecialchars($t['full_name']); ?></td>
      <td><?php echo htmlspecialchars($t['email']); ?></td>
      <td><?php echo htmlspecialchars($t['subject']); ?></td>
      <td><?php echo htmlspecialchars($t['department']); ?></td>
      <td><?php echo htmlspecialchars($t['qualification']); ?></td>
      <td>
        <a href="admin-edit-teacher.php?id=<?php echo $t['id']; ?>">Edit</a> |
        <a href="admin-delete-teacher.php?id=<?php echo $t['id']; ?>" onclick="return confirm('Delete this teacher?');">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>