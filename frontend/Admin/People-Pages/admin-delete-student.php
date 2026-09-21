<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$id = $_GET['id'] ?? null;

if ($id) {
    // Delete from students FIRST, then users
    $stmt1 = $pdo->prepare("DELETE FROM students WHERE student_id = :id");
    $stmt1->execute(['id' => $id]);

    $stmt2 = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt2->execute(['id' => $id]);
}

header('Location: admin-students.php');
exit;   