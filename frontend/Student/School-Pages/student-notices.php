<?php
require __DIR__ . '/../student-init.php';

// School-wide notices plus the ones targeted at MY class only
$stmt = $pdo->prepare(
    "SELECT n.title, n.content, n.posted_at, n.target_class, u.full_name
     FROM notices n JOIN users u ON n.posted_by = u.id
     WHERE n.target_class = 'All' OR n.target_class = :c
     ORDER BY n.posted_at DESC"
);
$stmt->execute(['c' => $myClass]);
$notices = $stmt->fetchAll();

$pageTitle = 'Notices'; $activePage = 'notices'; $crumb = 'Notices';
require __DIR__ . '/../student-header.php';
?>

<h1>Notices</h1><br>

<div style="max-width:760px;">
  <?php if (empty($notices)): ?>
    <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">No notices for you right now.</div>
  <?php endif; ?>
  <?php foreach ($notices as $n): ?>
    <div class="feed-card">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
        <h3><?php echo htmlspecialchars($n['title']); ?></h3>
        <span class="badge <?php echo $n['target_class'] === 'All' ? '' : 'gold'; ?>">
          <?php echo $n['target_class'] === 'All' ? 'All Classes' : 'Your Class'; ?>
        </span>
      </div>
      <p class="content"><?php echo nl2br(htmlspecialchars($n['content'])); ?></p>
      <div class="feed-meta">
        <span>Posted by <?php echo htmlspecialchars($n['full_name']); ?> · <?php echo date('d M Y, g:i A', strtotime($n['posted_at'])); ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../student-footer.php'; ?>