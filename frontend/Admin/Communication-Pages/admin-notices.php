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
    $targetClass = trim($_POST['target_class']) ?: 'All';

    $stmt = $pdo->prepare(
        "INSERT INTO notices (title, content, posted_by, target_class) VALUES (:title, :content, :posted_by, :target_class)"
    );
    $stmt->execute([
        'title' => $title,
        'content' => $content,
        'posted_by' => $_SESSION['user_id'],
        'target_class' => $targetClass
    ]);

    header('Location: admin-notices.php');
    exit;
}

$notices = $pdo->query(
    "SELECT notices.id, notices.title, notices.content, notices.posted_at, notices.target_class, users.full_name
     FROM notices
     JOIN users ON notices.posted_by = users.id
     ORDER BY notices.posted_at DESC"
)->fetchAll();

// Get the list of existing classes (from students table) to populate the dropdown
$classOptions = $pdo->query("SELECT DISTINCT class FROM students ORDER BY class")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notices</title>
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
      <li><a href="admin-announcements.php">Announcements</a></li>
      <li><a href="admin-notices.php" class="active">Notices</a></li>
      <li><a href="admin-events.php">Events</a></li>
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
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Notices</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'],0,1))); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Notices</h1><br>

      <div class="feed-layout">

        <div class="admin-form" style="margin-bottom:0;">
          <h3 style="margin-bottom:16px;">Post New Notice</h3>
          <form method="POST">
            <div class="field">
              <label>Title</label>
              <input type="text" name="title" required>
            </div>
            <div class="field">
              <label>Content</label>
              <textarea name="content" required></textarea>
            </div>
            <div class="field">
              <label>Send To</label>
              <select name="target_class">
                <option value="All">All Classes</option>
                <?php foreach ($classOptions as $c): ?>
                  <option value="<?php echo htmlspecialchars($c); ?>">Class <?php echo htmlspecialchars($c); ?> only</option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit">Post Notice</button>
          </form>
        </div>

        <div>
          <?php if (empty($notices)): ?>
            <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
              No notices posted yet.
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
                <a href="admin-delete-notice.php?id=<?php echo $n['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this notice?');">Delete</a>
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