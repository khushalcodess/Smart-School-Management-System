<?php
session_start();
require 'config.php';

$email = $_POST['email'];
$password = $_POST['password'];
$role = $_POST['role'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND role = :role LIMIT 1");
$stmt->execute(['email' => $email, 'role' => $role]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];

    if ($user['role'] === 'student') {
        header('Location: ../student-dashboard.php');
    } elseif ($user['role'] === 'teacher') {
        header('Location: ../teacher-dashboard.php');
    } elseif ($user['role'] === 'admin') {
        header('Location: ../admin-dashboard.php');
    }
    exit;
} else {
    header('Location: ../login.html?error=1');
    exit;
}