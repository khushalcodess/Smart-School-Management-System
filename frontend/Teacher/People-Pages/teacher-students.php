<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$classOptions = array_map('strval', range(1, 12));
$cls = $_GET['class'] ?? '';
if (!in_array($cls, $classOptions, true)) { $cls = ''; }
$q = trim($_GET['q'] ?? '');

$stmt = $pdo->prepare(
    "SELECT users.full_name, users.email, students.class, students.section,
            students.roll_no, students.dob, students.parent_contact
     FROM users JOIN students ON users.id = students.student_id
     WHERE (:c1 = '' OR students.class = :c2)
       AND (:q1 = '' OR users.full_name LIKE :q2)
     ORDER BY CAST(students.class AS UNSIGNED), students.section, students.roll_no, users.full_name"
);
$stmt->execute(['c1' => $cls, 'c2' => $cls, 'q1' => $q, 'q2' => '%' . $q . '%']);
$students = $stmt->fetchAll();

function initials($name) {
    $parts = explode(' ', trim($name));
    $first = $parts[0][0] ?? '';
    $last = isset($parts[1]) ? $parts[1][0] : '';
    return strtoupper($first . $last);
}

$activePage = 'students';
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Students</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .filter-bar{display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap; background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:18px 20px; margin-bottom:24px;}
  .filter-bar label{display:block; font-size:13px; font-weight:600; color:var(--navy-deep); margin-bottom:5px;}
  .filter-bar select, .filter-bar input{padding:9px 12px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:14px;}
  .btn-primary{background:var(--navy-deep); color:#fff; border:none; padding:10px 22px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer;}
  .btn-primary:hover{background:var(--navy);}
  .person{display:flex; align-items:center; gap:10px;}
  .sub{font-size:12px; color:var(--ink-soft);}
</style>
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/../teacher-sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / My Students</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>My Students</h1><br>

      <form method="GET" class="filter-bar">
        <div>
          <label>Class</label>
          <select name="class">
            <option value="">All classes</option>
            <?php foreach ($classOptions as $c): ?>
              <option value="<?php echo $c; ?>" <?php echo ($cls === $c) ? 'selected' : ''; ?>>Class <?php echo $c; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label>Search by name</label>
          <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="e.g. Riya">
        </div>
        <button type="submit" class="btn-primary">Filter</button>
        <span class="sub"><?php echo count($students); ?> student<?php echo count($students) === 1 ? '' : 's'; ?> found</span>
      </form>

      <?php if (empty($students)): ?>
        <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
          No students match your filter.
        </div>
      <?php else: ?>
        <div class="table-wrap" style="overflow-x:auto;">
          <table class="admin-table">
            <tr><th>Student</th><th>Class</th><th>Roll</th><th>Date of Birth</th><th>Parent Contact</th></tr>
            <?php foreach ($students as $s): ?>
              <tr>
                <td>
                  <div class="person">
                    <div class="avatar-sm"><?php echo initials($s['full_name']); ?></div>
                    <div>
                      <?php echo htmlspecialchars($s['full_name']); ?><br>
                      <span class="sub"><?php echo htmlspecialchars($s['email']); ?></span>
                    </div>
                  </div>
                </td>
                <td>Class <?php echo htmlspecialchars($s['class']); ?><?php echo $s['section'] ? '-' . htmlspecialchars($s['section']) : ''; ?></td>
                <td><?php echo htmlspecialchars($s['roll_no'] ?: '—'); ?></td>
                <td>
                  <?php if ($s['dob']): ?>
                    <?php echo date('d M Y', strtotime($s['dob'])); ?>
                    <span class="sub">(<?php echo (new DateTime($s['dob']))->diff(new DateTime())->y; ?> yrs)</span>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($s['parent_contact'] ?: '—'); ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>

    </div>
  </div>

</div>
</body>
</html>