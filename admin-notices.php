<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);

    $stmt = $pdo->prepare(
        "INSERT INTO notices (title, content, posted_by) VALUES (:title, :content, :posted_by)"
    );
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'posted_by' => $_SESSION['user_id']
    ]);

    header('Location: admin-notices.php');
    exit;
}

$notices = $pdo->query(
    "SELECT notices.id, notices.title, notices.content, notices.posted_at, users.full_name
     FROM notices
     JOIN users ON notices.posted_by = users.id
     ORDER BY notices.posted_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notices</title>
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Notices</h1>

  <h2>Post New Notice</h2>
  <form method="POST">
    <input type="text" name="title" placeholder="Title" required><br>
    <textarea name="content" placeholder="Content" required></textarea><br>
    <button type="submit">Post</button>
  </form>

  <h2>All Notices</h2>
  <table border="1" cellpadding="6">
    <tr>
      <th>Title</th><th>Content</th><th>Posted By</th><th>Date</th><th>Actions</th>
    </tr>
    <?php foreach ($notices as $n): ?>
    <tr>
      <td><?php echo htmlspecialchars($n['title']); ?></td>
      <td><?php echo htmlspecialchars($n['content']); ?></td>
      <td><?php echo htmlspecialchars($n['full_name']); ?></td>
      <td><?php echo htmlspecialchars($n['posted_at']); ?></td>
      <td>
        <a href="admin-delete-notice.php?id=<?php echo $n['id']; ?>" onclick="return confirm('Delete this notice?');">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>