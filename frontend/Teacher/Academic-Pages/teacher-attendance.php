<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$teacherId = $_SESSION['user_id'];
$todayDate = date('Y-m-d');

/* ---------- SAVE ATTENDANCE (form submitted) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comboPost = $_POST['class_sec'] ?? '';
    $datePost  = $_POST['date'] ?? '';
    $statuses  = $_POST['status'] ?? [];

    // Server-side validation: valid date format, not in the future, class chosen
    if ($comboPost === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePost) || $datePost > $todayDate) {
        header('Location: teacher-attendance.php?error=invalid');
        exit;
    }

    [$cls, $sec] = array_pad(explode('|', $comboPost, 2), 2, '');

    // Only accept students who really belong to the chosen class/section
    $stmt = $pdo->prepare(
        "SELECT student_id FROM students WHERE class = :c AND COALESCE(section,'') = :s"
    );
    $stmt->execute(['c' => $cls, 's' => $sec]);
    $validIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $upsert = $pdo->prepare(
        "INSERT INTO attendance (student_id, attendance_date, status, marked_by)
         VALUES (:sid, :d, :st, :tid)
         ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)"
    );

    $pdo->beginTransaction();
    foreach ($validIds as $sid) {
        $status = (($statuses[$sid] ?? 'Present') === 'Absent') ? 'Absent' : 'Present';
        $upsert->execute(['sid' => $sid, 'd' => $datePost, 'st' => $status, 'tid' => $teacherId]);
    }
    $pdo->commit();

    header('Location: teacher-attendance.php?class_sec=' . urlencode($comboPost) . '&date=' . $datePost . '&saved=1');
    exit;
}

/* ---------- LOAD STUDENTS (filter form) ---------- */
$combos = $pdo->query(
    "SELECT DISTINCT class, COALESCE(section,'') AS section
     FROM students ORDER BY CAST(class AS UNSIGNED), section"
)->fetchAll();

$selectedCombo = $_GET['class_sec'] ?? '';
$selectedDate  = $_GET['date'] ?? $todayDate;
if ($selectedDate > $todayDate) $selectedDate = $todayDate;

$students = [];
$existing = [];

if ($selectedCombo !== '') {
    [$cls, $sec] = array_pad(explode('|', $selectedCombo, 2), 2, '');

    $stmt = $pdo->prepare(
        "SELECT users.id, users.full_name, students.roll_no
         FROM users JOIN students ON users.id = students.student_id
         WHERE students.class = :c AND COALESCE(students.section,'') = :s
         ORDER BY students.roll_no, users.full_name"
    );
    $stmt->execute(['c' => $cls, 's' => $sec]);
    $students = $stmt->fetchAll();

    // Attendance already saved for this class on this date (so it can be edited)
    $stmt = $pdo->prepare(
        "SELECT a.student_id, a.status
         FROM attendance a JOIN students s ON a.student_id = s.student_id
         WHERE a.attendance_date = :d AND s.class = :c AND COALESCE(s.section,'') = :s"
    );
    $stmt->execute(['d' => $selectedDate, 'c' => $cls, 's' => $sec]);
    foreach ($stmt->fetchAll() as $row) {
        $existing[$row['student_id']] = $row['status'];
    }
}

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Mark Attendance</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .filter-bar{display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap; background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:18px 20px; margin-bottom:24px;}
  .filter-bar .field{margin:0;}
  .filter-bar label{display:block; font-size:13px; font-weight:600; color:var(--navy-deep); margin-bottom:5px;}
  .filter-bar select, .filter-bar input{padding:9px 12px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:14px;}
  .btn-primary{background:var(--navy-deep); color:#fff; border:none; padding:10px 22px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer;}
  .btn-primary:hover{background:var(--navy);}
  .btn-light{background:var(--cream); color:var(--navy-deep); border:1px solid var(--line); padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;}
  .alert{padding:13px 18px; border-radius:var(--radius); margin-bottom:18px; font-size:14px;}
  .alert.ok{background:#E8F3EA; border:1px solid #C5DEC9; color:#2F6B3A;}
  .alert.err{background:#FDECEA; border:1px solid #F5C6C3; color:#B3261E;}
  .alert.info{background:rgba(200,150,62,0.12); border:1px solid rgba(200,150,62,0.35); color:var(--navy-deep);}
  .att-actions{display:flex; gap:10px; margin-bottom:14px; align-items:center;}
  .att-radio{display:inline-block; margin-right:8px;}
  .att-radio input{display:none;}
  .att-radio span{display:inline-block; padding:6px 16px; border-radius:20px; font-size:13px; font-weight:600; border:1px solid var(--line); color:var(--ink-soft); cursor:pointer; background:var(--paper);}
  .att-radio input:checked + span.p{background:#E8F3EA; border-color:#7DB68A; color:#2F6B3A;}
  .att-radio input:checked + span.a{background:#FDECEA; border-color:#E49A95; color:#B3261E;}
</style>
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>

    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/teacher-dashboard.php">Dashboard</a></li>
    </ul>

    <div class="nav-label">Academics</div>
    <ul>
      <li><a href="../Academic-Pages/teacher-attendance.php" class="active">Mark Attendance</a></li>
      <li><a href="../Academic-Pages/teacher-attendance-records.php">Attendance Records</a></li>
      <li><a href="../Academic-Pages/teacher-homework.php">Homework</a></li>
      <li><a href="../Academic-Pages/teacher-exams.php">Manage Exams</a></li>
    </ul>

    <div class="nav-label">Students</div>
    <ul>
      <li><a href="../People-Pages/teacher-students.php">My Students</a></li>
    </ul>

    <div class="nav-label">School</div>
    <ul>
      <li><a href="../School-Pages/teacher-timetable.php">My Timetable</a></li>
      <li><a href="../School-Pages/teacher-announcements.php">Announcements</a></li>
      <li><a href="../School-Pages/teacher-notices.php">Notices</a></li>
      <li><a href="../School-Pages/teacher-events.php">Events</a></li>
      <li><a href="../School-Pages/teacher-gallery.php">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Mark Attendance</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Mark Attendance</h1><br>

      <?php if (isset($_GET['saved'])): ?>
        <div class="alert ok">Attendance saved successfully.</div>
      <?php endif; ?>
      <?php if (($_GET['error'] ?? '') === 'invalid'): ?>
        <div class="alert err">Please choose a class and a valid date (today or earlier).</div>
      <?php endif; ?>

      <!-- Filter: choose class + date -->
      <form method="GET" class="filter-bar">
        <div class="field">
          <label>Class</label>
          <select name="class_sec" required>
            <option value="">-- Select Class --</option>
            <?php foreach ($combos as $c): ?>
              <?php
                $val = $c['class'] . '|' . $c['section'];
                $label = 'Class ' . $c['class'] . ($c['section'] !== '' ? '-' . $c['section'] : '');
              ?>
              <option value="<?php echo htmlspecialchars($val); ?>" <?php echo ($val === $selectedCombo) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Date</label>
          <input type="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" max="<?php echo $todayDate; ?>" required>
        </div>
        <button type="submit" class="btn-primary">Load Students</button>
      </form>

      <?php if ($selectedCombo !== ''): ?>

        <?php if (empty($students)): ?>
          <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
            No students found in this class.
          </div>
        <?php else: ?>

          <?php if (!empty($existing)): ?>
            <div class="alert info">Attendance was already recorded for this class on this date. Saving will update it.</div>
          <?php endif; ?>

          <form method="POST">
            <input type="hidden" name="class_sec" value="<?php echo htmlspecialchars($selectedCombo); ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">

            <div class="att-actions">
              <button type="button" class="btn-light" onclick="setAll('Present')">Mark all Present</button>
              <button type="button" class="btn-light" onclick="setAll('Absent')">Mark all Absent</button>
              <span style="color:var(--ink-soft); font-size:13px;"><?php echo count($students); ?> students</span>
            </div>

            <div class="table-wrap">
              <table class="admin-table">
                <tr><th>Roll No</th><th>Student</th><th>Status</th></tr>
                <?php foreach ($students as $s): ?>
                  <?php $current = $existing[$s['id']] ?? 'Present'; ?>
                  <tr>
                    <td><?php echo htmlspecialchars($s['roll_no'] ?: '—'); ?></td>
                    <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                    <td>
                      <label class="att-radio">
                        <input type="radio" name="status[<?php echo $s['id']; ?>]" value="Present" <?php echo $current === 'Present' ? 'checked' : ''; ?>>
                        <span class="p">Present</span>
                      </label>
                      <label class="att-radio">
                        <input type="radio" name="status[<?php echo $s['id']; ?>]" value="Absent" <?php echo $current === 'Absent' ? 'checked' : ''; ?>>
                        <span class="a">Absent</span>
                      </label>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </table>
            </div>

            <br>
            <button type="submit" class="btn-primary">Save Attendance</button>
          </form>

        <?php endif; ?>
      <?php endif; ?>

    </div>
  </div>

</div>

<script>
  function setAll(value) {
    document.querySelectorAll('input[type="radio"][value="' + value + '"]').forEach(function (r) {
      r.checked = true;
    });
  }
</script>
</body>
</html>