<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$classOptions = array_map('strval', range(1, 12));
$filter = $_GET['class'] ?? '';
if (!in_array($filter, $classOptions, true)) { $filter = ''; }

if ($filter !== '') {
    // School-wide notices plus those targeted to the chosen class
    $stmt = $pdo->prepare(
        "SELECT n.title, n.content, n.posted_at, n.target_class, u.full_name
         FROM notices n JOIN users u ON n.posted_by = u.id
         WHERE n.target_class = 'All' OR n.target_class = :c
         ORDER BY n.posted_at DESC"
    );
    $stmt->execute(['c' => $filter]);
    $notices = $stmt->fetchAll();
} else {
    $notices = $pdo->query(
        "SELECT n.title, n.content, n.posted_at, n.target_class, u.full_name
         FROM notices n JOIN users u ON n.posted_by = u.id
         ORDER BY n.posted_at DESC"
    )->fetchAll();
}

$activePage = 'notices';
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notices</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .filter-line{display:flex; align-items:center; gap:10px; margin-bottom:18px;}
  .filter-line select{padding:8px 12px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:13.5px;}
</style>
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/../teacher-sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Notices</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Notices</h1><br>

      <div style="max-width:760px;">
        <form method="GET" class="filter-line">
          <label style="font-size:13px; font-weight:600; color:var(--navy-deep);">Show notices for:</label>
          <select name="class" onchange="this.form.submit()">
            <option value="">Everything</option>
            <?php foreach ($classOptions as $c): ?>
              <option value="<?php echo $c; ?>" <?php echo ($filter === $c) ? 'selected' : ''; ?>>Class <?php echo $c; ?> (and school-wide)</option>
            <?php endforeach; ?>
          </select>
        </form>

        <?php if (empty($notices)): ?>
          <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
            No notices found.
          </div>
        <?php endif; ?>

        <?php foreach ($notices as $n): ?>
          <div class="feed-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
              <h3><?php echo htmlspecialchars($n['title']); ?></h3>
              <span style="background:<?php echo $n['target_class'] === 'All' ? 'var(--cream)' : 'rgba(200,150,62,0.15)'; ?>; color:var(--navy-deep); font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:12px; white-space:nowrap; margin-left:10px;">
                <?php echo $n['target_class'] === 'All' ? 'All Classes' : 'Class ' . htmlspecialchars($n['target_class']); ?>
              </span>
            </div>
            <p class="content"><?php echo nl2br(htmlspecialchars($n['content'])); ?></p>
            <div class="feed-meta">
              <span>Posted by <?php echo htmlspecialchars($n['full_name']); ?> · <?php echo date('d M Y, g:i A', strtotime($n['posted_at'])); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>

</div>
</body>
</html>