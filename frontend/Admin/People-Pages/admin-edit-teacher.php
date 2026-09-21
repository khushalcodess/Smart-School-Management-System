<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: admin-teachers.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $department = trim($_POST['department']);
    $qualification = trim($_POST['qualification']);

    $stmt1 = $pdo->prepare("UPDATE users SET full_name = :name, email = :email WHERE id = :id");
    $stmt1->execute(['name' => $fullName, 'email' => $email, 'id' => $id]);

    $stmt2 = $pdo->prepare("UPDATE teachers SET subject = :subject, department = :department, qualification = :qualification WHERE teacher_id = :id");
    $stmt2->execute(['subject' => $subject, 'department' => $department, 'qualification' => $qualification, 'id' => $id]);

    header('Location: admin-teachers.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT users.id, users.full_name, users.email, teachers.subject, teachers.department, teachers.qualification
     FROM users JOIN teachers ON users.id = teachers.teacher_id
     WHERE users.id = :id"
);
$stmt->execute(['id' => $id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: admin-teachers.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Teacher</title>
</head>
<body>

  <a href="../People-Pages/admin-teachers.php">&larr; Back to Manage Teachers</a>
  <h1>Edit Teacher</h1>

  <form method="POST">
    <input type="text" name="full_name" value="<?php echo htmlspecialchars($teacher['full_name']); ?>" required><br>
    <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required><br>
    <input type="text" name="subject" value="<?php echo htmlspecialchars($teacher['subject']); ?>" required><br>
    <input type="text" name="department" value="<?php echo htmlspecialchars($teacher['department']); ?>"><br>
    <input type="text" name="qualification" value="<?php echo htmlspecialchars($teacher['qualification']); ?>"><br>
    <button type="submit">Save Changes</button>
  </form>

</body>
</html>