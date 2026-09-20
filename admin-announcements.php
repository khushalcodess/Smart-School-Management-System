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
        "INSERT INTO announcements (title, content, posted_by) VALUES (:title, :content, :posted_by)"
    );
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'posted_by' => $_SESSION['user_id']
    ]);

    header('Location: admin-announcements.php');
    exit;
}

$announcements = $pdo->query(
    "SELECT announcements.id, announcements.title, announcements.content, announcements.posted_at, users.full_name
     FROM announcements
     JOIN users ON announcements.posted_by = users.id
     ORDER BY announcements.posted_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements</title>
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Announcements</h1>

  <h2>Post New Announcement</h2>
  <form method="POST">
    <input type="text" name="title" placeholder="Title" required><br>
    <textarea name="content" placeholder="Content" required></textarea><br>
    <button type="submit">Post</button>
  </form>

  <h2>All Announcements</h2>
  <table border="1" cellpadding="6">
    <tr>
      <th>Title</th><th>Content</th><th>Posted By</th><th>Date</th><th>Actions</th>
    </tr>
    <?php foreach ($announcements as $a): ?>
    <tr>
      <td><?php echo htmlspecialchars($a['title']); ?></td>
      <td><?php echo htmlspecialchars($a['content']); ?></td>
      <td><?php echo htmlspecialchars($a['full_name']); ?></td>
      <td><?php echo htmlspecialchars($a['posted_at']); ?></td>
      <td>
        <a href="admin-delete-announcement.php?id=<?php echo $a['id']; ?>" onclick="return confirm('Delete this announcement?');">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>