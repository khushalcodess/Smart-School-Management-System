<?php
require __DIR__ . '/../student-init.php';

$announcements = $pdo->query(
    "SELECT a.title, a.content, a.posted_at, u.full_name
     FROM announcements a JOIN users u ON a.posted_by = u.id
     ORDER BY a.posted_at DESC"
)->fetchAll();

$pageTitle = 'Announcements'; $activePage = 'announcements'; $crumb = 'Announcements';
require __DIR__ . '/../student-header.php';
?>

<h1>Announcements</h1><br>

<div style="max-width:760px;">
  <?php if (empty($announcements)): ?>
    <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">No announcements yet.</div>
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

<?php require __DIR__ . '/../student-footer.php'; ?>