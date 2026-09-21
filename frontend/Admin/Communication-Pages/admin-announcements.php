<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
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

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Announcements</title>
<link rel="stylesheet" href="../../Style/admin.css">
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>
    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/admin-dashboard.php">Dashboard</a></li>
      <li><a href="../Overview-Pages/admin-analytics.php">Analytics & Reports</a></li>
    </ul>
    <div class="nav-label">People</div>
    <ul>
      <li><a href="../People-Pages/admin-students.php">Manage Students</a></li>
      <li><a href="../People-Pages/admin-teachers.php">Manage Teachers</a></li>
    </ul>
    <div class="nav-label">Communication</div>
    <ul>
      <li><a href="../Communication-Pages/admin- class="active">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-notices.php">Notices</a></li>
      <li><a href="../Communication-Pages/admin-events.php">Events</a></li>
    </ul>
    <div class="nav-label">School</div>
    <ul>
      <li><a href="../School-Pages/admin-timetable.php">Timetable</a></li>
      <li><a href="../School-Pages/admin-gallery.php">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Announcements</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Announcements</h1><br>

      <div class="feed-layout">

        <!-- LEFT: post form -->
        <div class="admin-form" style="margin-bottom:0;">
          <h3 style="margin-bottom:16px;">Post New Announcement</h3>
          <form method="POST">
            <div class="field">
              <label>Title</label>
              <input type="text" name="title" required>
            </div>
            <div class="field">
              <label>Content</label>
              <textarea name="content" required></textarea>
            </div>
            <button type="submit">Post Announcement</button>
          </form>
        </div>

        <!-- RIGHT: feed of announcements -->
        <div>
          <?php if (empty($announcements)): ?>
            <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
              No announcements posted yet.
            </div>
          <?php endif; ?>

          <?php foreach ($announcements as $a): ?>
            <div class="feed-card">
              <h3><?php echo htmlspecialchars($a['title']); ?></h3>
              <p class="content"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
              <div class="feed-meta">
                <span>Posted by <?php echo htmlspecialchars($a['full_name']); ?> · <?php echo date('d M Y, g:i A', strtotime($a['posted_at'])); ?></span>
                <a href="admin-delete-announcement.php?id=<?php echo $a['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this announcement?');">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>