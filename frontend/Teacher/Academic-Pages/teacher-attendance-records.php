<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

// Class + section options
$combos = $pdo->query(
    "SELECT DISTINCT class, COALESCE(section,'') AS section
     FROM students ORDER BY CAST(class AS UNSIGNED), section"
)->fetchAll();

$selectedCombo = $_GET['class_sec'] ?? '';
$selectedMonth = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$monthStart = $selectedMonth . '-01';
$monthEnd   = date('Y-m-t', strtotime($monthStart)); // last day of that month

$report = [];
$dates = [];
$daysRecorded = 0;
$classPercent = null;

if ($selectedCombo !== '') {
    [$cls, $sec] = array_pad(explode('|', $selectedCombo, 2), 2, '');

    // Per-student summary for the month
    $stmt = $pdo->prepare(
        "SELECT users.id, users.full_name, students.roll_no,
                COALESCE(SUM(a.status = 'Present'), 0) AS present,
                COALESCE(SUM(a.status = 'Absent'), 0)  AS absent,
                COUNT(a.id) AS total
         FROM users
         JOIN students ON users.id = students.student_id
         LEFT JOIN attendance a
                ON a.student_id = users.id
               AND a.attendance_date BETWEEN :start AND :end
         WHERE students.class = :c AND COALESCE(students.section,'') = :s
         GROUP BY users.id, users.full_name, students.roll_no
         ORDER BY students.roll_no, users.full_name"
    );
    $stmt->execute(['start' => $monthStart, 'end' => $monthEnd, 'c' => $cls, 's' => $sec]);
    $report = $stmt->fetchAll();

    // Each date on which attendance was taken for this class
    $stmt = $pdo->prepare(
        "SELECT a.attendance_date,
                SUM(a.status = 'Present') AS present,
                SUM(a.status = 'Absent')  AS absent
         FROM attendance a
         JOIN students s ON a.student_id = s.student_id
         WHERE s.class = :c AND COALESCE(s.section,'') = :s
           AND a.attendance_date BETWEEN :start AND :end
         GROUP BY a.attendance_date
         ORDER BY a.attendance_date DESC"
    );
    $stmt->execute(['c' => $cls, 's' => $sec, 'start' => $monthStart, 'end' => $monthEnd]);
    $dates = $stmt->fetchAll();
    $daysRecorded = count($dates);

    // Class-wide average
    $sumPresent = 0; $sumTotal = 0;
    foreach ($report as $r) { $sumPresent += $r['present']; $sumTotal += $r['total']; }
    $classPercent = $sumTotal > 0 ? round(($sumPresent / $sumTotal) * 100) : null;
}

// Colour helper for percentages
function pctColor($p) {
    if ($p === null) return 'var(--ink-soft)';
    if ($p >= 75) return '#2F6B3A';
    if ($p >= 50) return '#B07D1E';
    return '#B3261E';
}

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Records</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .filter-bar{display:flex; gap:14px; align-items:flex-end; flex-wrap:wrap; background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:18px 20px; margin-bottom:24px;}
  .filter-bar .field{margin:0;}
  .filter-bar label{display:block; font-size:13px; font-weight:600; color:var(--navy-deep); margin-bottom:5px;}
  .filter-bar select, .filter-bar input{padding:9px 12px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:14px;}
  .btn-primary{background:var(--navy-deep); color:#fff; border:none; padding:10px 22px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer;}
  .btn-primary:hover{background:var(--navy);}
  .pct-wrap{display:flex; align-items:center; gap:10px; min-width:170px;}
  .pct-bar{flex:1; height:8px; background:var(--cream); border-radius:4px; overflow:hidden;}
  .pct-fill{height:100%; border-radius:4px;}
  .low-flag{display:inline-block; background:#FDECEA; color:#B3261E; font-size:11px; font-weight:700; padding:2px 8px; border-radius:10px; margin-left:8px;}
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
      <li><a href="../Academic-Pages/teacher-attendance.php">Mark Attendance</a></li>
      <li><a href="../Academic-Pages/teacher-attendance-records.php" class="active">Attendance Records</a></li>
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
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Attendance Records</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Attendance Records</h1><br>

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
          <label>Month</label>
          <input type="month" name="month" value="<?php echo htmlspecialchars($selectedMonth); ?>" max="<?php echo date('Y-m'); ?>" required>
        </div>
        <button type="submit" class="btn-primary">View Records</button>
      </form>

      <?php if ($selectedCombo === ''): ?>
        <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
          Choose a class and month to see attendance records.
        </div>

      <?php else: ?>

        <div class="stat-cards">
          <div class="stat-card">
            <h3><?php echo $daysRecorded; ?></h3>
            <p>Days Recorded</p>
            <div class="bar"></div>
          </div>
          <div class="stat-card">
            <h3><?php echo count($report); ?></h3>
            <p>Students</p>
            <div class="bar"></div>
          </div>
          <div class="stat-card">
            <h3 style="color:<?php echo pctColor($classPercent); ?>;"><?php echo $classPercent !== null ? $classPercent . '%' : '—'; ?></h3>
            <p>Class Average</p>
            <div class="bar"></div>
          </div>
        </div>

        <div class="dash-grid">

          <!-- LEFT: per-student summary -->
          <div class="panel-card">
            <h2>Student-wise Summary — <?php echo date('F Y', strtotime($monthStart)); ?></h2>
            <?php if (empty($report)): ?>
              <p style="color:var(--ink-soft); font-size:13.5px;">No students found in this class.</p>
            <?php else: ?>
              <table class="admin-table">
                <tr><th>Roll</th><th>Student</th><th>Present</th><th>Absent</th><th>Attendance</th></tr>
                <?php foreach ($report as $r): ?>
                  <?php $pct = $r['total'] > 0 ? round(($r['present'] / $r['total']) * 100) : null; ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['roll_no'] ?: '—'); ?></td>
                    <td>
                      <?php echo htmlspecialchars($r['full_name']); ?>
                      <?php if ($pct !== null && $pct < 75): ?><span class="low-flag">Low</span><?php endif; ?>
                    </td>
                    <td><?php echo (int)$r['present']; ?></td>
                    <td><?php echo (int)$r['absent']; ?></td>
                    <td>
                      <?php if ($pct === null): ?>
                        <span style="color:var(--ink-soft);">No data</span>
                      <?php else: ?>
                        <div class="pct-wrap">
                          <div class="pct-bar"><div class="pct-fill" style="width:<?php echo $pct; ?>%; background:<?php echo pctColor($pct); ?>;"></div></div>
                          <strong style="color:<?php echo pctColor($pct); ?>; font-size:13px;"><?php echo $pct; ?>%</strong>
                        </div>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </table>
            <?php endif; ?>
          </div>

          <!-- RIGHT: dates recorded -->
          <div class="panel-card">
            <h2>Dates Recorded</h2>
            <?php if (empty($dates)): ?>
              <p style="color:var(--ink-soft); font-size:13.5px;">No attendance recorded this month.</p>
            <?php else: ?>
              <table class="admin-table">
                <tr><th>Date</th><th>P</th><th>A</th><th></th></tr>
                <?php foreach ($dates as $d): ?>
                  <tr>
                    <td><?php echo date('d M, D', strtotime($d['attendance_date'])); ?></td>
                    <td><?php echo (int)$d['present']; ?></td>
                    <td><?php echo (int)$d['absent']; ?></td>
                    <td>
                      <a href="teacher-attendance.php?class_sec=<?php echo urlencode($selectedCombo); ?>&date=<?php echo $d['attendance_date']; ?>" class="action-btn edit">Edit</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </table>
            <?php endif; ?>
          </div>

        </div>

      <?php endif; ?>

    </div>
  </div>

</div>
</body>
</html> 