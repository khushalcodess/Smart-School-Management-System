<?php
// Shared start-up for every Student page: login check + the student's own profile.
session_start();
require __DIR__ . '/../../backend/config.php';

// Every Student page sits two folders deep, so '../../' reaches frontend/
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$studentId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT users.full_name, users.email, students.class, students.section,
            students.roll_no, students.dob, students.parent_contact
     FROM users JOIN students ON users.id = students.student_id
     WHERE users.id = :id"
);
$stmt->execute(['id' => $studentId]);
$me = $stmt->fetch();

if (!$me) {
    die('Your student profile was not found. Please contact the school office.');
}

$myClass      = (string) $me['class'];
$mySection    = (string) ($me['section'] ?? '');
$classLabel   = 'Class ' . $myClass . ($mySection !== '' ? '-' . $mySection : '');
$timetableKey = $myClass . ($mySection !== '' ? '-' . $mySection : '');   // e.g. "10-A"
$initial      = strtoupper(substr($_SESSION['full_name'], 0, 1));

/* ---------- helpers used by several pages ---------- */
function pctColor($p) {
    if ($p === null) return 'var(--ink-soft)';
    if ($p >= 75) return '#2F6B3A';
    if ($p >= 50) return '#B07D1E';
    return '#B3261E';
}
function fmtMarks($v) {
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
}
function initialsOf($name) {
    $parts = explode(' ', trim($name));
    return strtoupper(($parts[0][0] ?? '') . (isset($parts[1]) ? $parts[1][0] : ''));
}
// Returns [label, badge colour] for an upcoming date
function dueText($date) {
    $d = (int) (new DateTime(date('Y-m-d')))->diff(new DateTime($date))->format('%r%a');
    if ($d < 0)   return ['Overdue', 'red'];
    if ($d === 0) return ['Today', 'gold'];
    if ($d === 1) return ['Tomorrow', 'gold'];
    return ['In ' . $d . ' days', 'green'];
}