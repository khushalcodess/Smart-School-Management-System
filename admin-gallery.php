<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $caption = trim($_POST['caption']);

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $originalName = $_FILES['image']['name'];
        $tmpPath = $_FILES['image']['tmp_name'];

        // Make the filename unique so two uploads never overwrite each other
        $newFileName = time() . '_' . $originalName;
        $destination = 'uploads/' . $newFileName;

        // Actually move the uploaded file from PHP's temp folder into /uploads
        move_uploaded_file($tmpPath, $destination);

        $stmt = $pdo->prepare(
            "INSERT INTO gallery (image_path, caption, uploaded_by) VALUES (:path, :caption, :uploaded_by)"
        );
        $stmt->execute([
            'path' => $destination,
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
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Gallery</h1>

  <h2>Upload New Photo</h2>
  <form method="POST" enctype="multipart/form-data">
    <input type="file" name="image" accept="image/*" required><br>
    <input type="text" name="caption" placeholder="Caption"><br>
    <button type="submit">Upload</button>
  </form>

  <h2>All Photos</h2>
  <div style="display:flex; flex-wrap:wrap; gap:16px;">
    <?php foreach ($images as $img): ?>
      <div style="border:1px solid #ccc; padding:8px; width:200px;">
        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" style="width:100%;">
        <p><?php echo htmlspecialchars($img['caption']); ?></p>
        <p><small>By <?php echo htmlspecialchars($img['full_name']); ?></small></p>
        <a href="admin-delete-image.php?id=<?php echo $img['id']; ?>" onclick="return confirm('Delete this photo?');">Delete</a>
      </div>
    <?php endforeach; ?>
  </div>

</body>
</html>