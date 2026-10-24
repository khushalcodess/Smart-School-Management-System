<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$images = $pdo->query(
    "SELECT g.image_path, g.caption, g.uploaded_at, u.full_name
     FROM gallery g JOIN users u ON g.uploaded_by = u.id
     ORDER BY g.uploaded_at DESC"
)->fetchAll();

$activePage = 'gallery';
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gallery</title>
<link rel="stylesheet" href="../../Style/admin.css">
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/../teacher-sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Gallery</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>School Gallery</h1><br>

      <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
          <div class="gallery-card">
            <img src="../../../<?php echo htmlspecialchars($img['image_path']); ?>" alt="<?php echo htmlspecialchars($img['caption']); ?>">
            <p><?php echo htmlspecialchars($img['caption']); ?></p>
            <small><?php echo date('d M Y', strtotime($img['uploaded_at'])); ?></small>
          </div>
        <?php endforeach; ?>
        <?php if (empty($images)): ?>
          <div class="empty-state">No photos have been uploaded yet.</div>
        <?php endif; ?>
      </div>

    </div>
  </div>

</div>
</body>
</html>