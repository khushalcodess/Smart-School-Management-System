<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // First, find the file path so we can delete the actual file too
    $stmt = $pdo->prepare("SELECT image_path FROM gallery WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $image = $stmt->fetch();

    if ($image && file_exists($image['image_path'])) {
        unlink($image['image_path']); // deletes the actual file from disk
    }

    $stmt2 = $pdo->prepare("DELETE FROM gallery WHERE id = :id");
    $stmt2->execute(['id' => $id]);
}

header('Location: admin-gallery.php');
exit;