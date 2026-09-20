<?php
$DB_HOST = 'localhost';
$DB_NAME = 'smart_school';
$DB_USER = 'root';
$DB_PASS = '';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS
    );
} catch (PDOException $e) {
    die('Database connection failed :- '. $e->getMessage());
}       