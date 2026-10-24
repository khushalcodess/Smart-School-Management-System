<?php
require __DIR__ . '/../student-init.php';

$images = $pdo->query(
    "SELECT image_path, caption, uploaded_at FROM gallery ORDER BY uploaded_at DESC"
)->fetchAll();

$pageTitle = 'Gallery'; $activePage = 'gallery'; $crumb = 'Gallery';
require __DIR__ . '/../student-header.php';
?>

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

<?php require __DIR__ . '/../student-footer.php'; ?>