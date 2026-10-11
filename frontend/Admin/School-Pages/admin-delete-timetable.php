<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$id = $_GET['id'] ?? null;
$class = $_GET['class'] ?? null;
$returnClass = $class;

if ($id) {
    // Delete ONE slot. First remember its class so we can go back to the same tab.
    $stmt = $pdo->prepare("SELECT class FROM timetable WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $returnClass = $stmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM timetable WHERE id = :id");
    $stmt->execute(['id' => $id]);

} elseif ($class) {
    // Delete the ENTIRE timetable of one class
    $stmt = $pdo->prepare("DELETE FROM timetable WHERE class = :class");
    $stmt->execute(['class' => $class]);
    $returnClass = null; // that class no longer exists, so show the default tab
}

$redirect = 'admin-timetable.php';
if ($returnClass) {
    $redirect .= '?class=' . urlencode($returnClass);
}
header('Location: ' . $redirect);
exit;