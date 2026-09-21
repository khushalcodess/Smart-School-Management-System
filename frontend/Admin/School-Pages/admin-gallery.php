<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption']);

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $originalName = $_FILES['image']['name'];
        $tmpPath = $_FILES['image']['tmp_name'];

        $newFileName = time() . '_' . $originalName;

        // Save the actual file to the project-root uploads/ folder
        $destination = '../../../uploads/' . $newFileName;
        move_uploaded_file($tmpPath, $destination);

        // Store the CLEAN path (no ../) in the database
        $cleanPath = 'uploads/' . $newFileName;

        $stmt = $pdo->prepare(
            "INSERT INTO gallery (image_path, caption, uploaded_by) VALUES (:path, :caption, :uploaded_by)"
        );
        $stmt->execute([
            'path' => $cleanPath,
            'caption' => $caption,
            'uploaded_by' => $_SESSION['user_id']
        ]);
    }

    header('Location: admin-gallery.php');
    exit;
}

$images = $pdo->query(
    "SELECT gallery.id, gallery.image_path, gallery.caption, users.full_name
     FROM gallery
     JOIN users ON gallery.uploaded_by = users.id
     ORDER BY gallery.uploaded_at DESC"
)->fetchAll();
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
      <li><a href="../Communication-Pages/admin-announcements.php">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-notices.php">Notices</a></li>
      <li><a href="../Communication-Pages/admin-events.php">Events</a></li>
    </ul>
    <div class="nav-label">School</div>
    <ul>
      <li><a href="admin-timetable.php">Timetable</a></li>
      <li><a href="admin-gallery.php" class="active">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Gallery</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'],0,1))); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Gallery</h1><br>

      <div class="admin-form">
        <h3 style="margin-bottom:16px;">Upload New Photo</h3>
        <form method="POST" enctype="multipart/form-data">
          <div class="field">
            <label>Photo</label>
            <input type="file" name="image" accept="image/*" required>
          </div>
          <div class="field">
            <label>Caption</label>
            <input type="text" name="caption" placeholder="Optional caption">
          </div>
          <button type="submit">Upload</button>
        </form>
      </div>

      <h2>All Photos</h2>
      <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
          <div class="gallery-card">
            <img src="../../../<?php echo htmlspecialchars($img['image_path']); ?>">
            <p><?php echo htmlspecialchars($img['caption']); ?></p>
            <small>By <?php echo htmlspecialchars($img['full_name']); ?></small><br>
            <a href="admin-delete-image.php?id=<?php echo $img['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this photo?');" style="margin-top:8px; display:inline-block;">Delete</a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($images)): ?>
          <div class="empty-state">No photos uploaded yet.</div>
        <?php endif; ?>
      </div>

    </div>
  </div>

</div>
</body>
</html>