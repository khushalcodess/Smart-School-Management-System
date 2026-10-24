<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$announcements = $pdo->query(
    "SELECT a.title, a.content, a.posted_at, u.full_name
     FROM announcements a JOIN users u ON a.posted_by = u.id
     ORDER BY a.posted_at DESC"
)->fetchAll();

$activePage = 'announcements';
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

  <?php include __DIR__ . '/../teacher-sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Announcements</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Announcements</h1><br>

      <div style="max-width:760px;">
        <?php if (empty($announcements)): ?>
          <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
            No announcements have been posted yet.
          </div>
        <?php endif; ?>

        <?php foreach ($announcements as $a): ?>
          <div class="feed-card">
            <h3><?php echo htmlspecialchars($a['title']); ?></h3>
            <p class="content"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>
            <div class="feed-meta">
              <span>Posted by <?php echo htmlspecialchars($a['full_name']); ?> · <?php echo date('d M Y, g:i A', strtotime($a['posted_at'])); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>

</div>
</body>
</html>