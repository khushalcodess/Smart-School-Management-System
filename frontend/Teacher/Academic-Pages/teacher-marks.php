<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$teacherId = $_SESSION['user_id'];
$examId = (int) ($_GET['exam'] ?? $_POST['exam_id'] ?? 0);

// The exam must exist AND belong to this teacher
$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = :id AND created_by = :tid");
$stmt->execute(['id' => $examId, 'tid' => $teacherId]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: teacher-exams.php');
    exit;
}

$total = (int) $exam['total_marks'];

function gradeFor($pct) {
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 40) return 'D';
    return 'F';
}

function fmtMarks($v) {
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
}

// Students of the exam's class
$stmt = $pdo->prepare(
    "SELECT users.id, users.full_name, students.section, students.roll_no
     FROM users JOIN students ON users.id = students.student_id
     WHERE students.class = :c
     ORDER BY students.section, students.roll_no, users.full_name"
);
$stmt->execute(['c' => $exam['class']]);
$students = $stmt->fetchAll();

$error = '';
$formValues = [];

/* ---------- SAVE MARKS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputs = $_POST['marks'] ?? [];
    $rows = [];
    $toClear = [];
    $bad = [];

    // First pass: validate EVERYTHING before saving anything
    foreach ($students as $s) {
        $sid = $s['id'];
        $raw = trim((string) ($inputs[$sid] ?? ''));
        $formValues[$sid] = $raw;

        if ($raw === '') { $toClear[] = $sid; continue; }

        if (!is_numeric($raw) || (float) $raw < 0 || (float) $raw > $total) {
            $bad[] = $s['full_name'];
            continue;
        }

        $m = round((float) $raw, 2);
        $rows[$sid] = ['marks' => $m, 'grade' => gradeFor($m / $total * 100)];
    }

    if (!empty($bad)) {
        $error = 'Invalid marks for: ' . implode(', ', $bad) . '. Marks must be between 0 and ' . $total . '.';
    } else {
        $upsert = $pdo->prepare(
            "INSERT INTO marks (exam_id, student_id, marks_obtained, grade, entered_by)
             VALUES (:e, :s, :m, :g, :tid)
             ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained),
                                     grade = VALUES(grade),
                                     entered_by = VALUES(entered_by)"
        );
        $clear = $pdo->prepare("DELETE FROM marks WHERE exam_id = :e AND student_id = :s");

        $pdo->beginTransaction();
        foreach ($rows as $sid => $r) {
            $upsert->execute(['e' => $examId, 's' => $sid, 'm' => $r['marks'], 'g' => $r['grade'], 'tid' => $teacherId]);
        }
        foreach ($toClear as $sid) {
            $clear->execute(['e' => $examId, 's' => $sid]);
        }
        $pdo->commit();

        header('Location: teacher-marks.php?exam=' . $examId . '&saved=1');
        exit;
    }
}

/* ---------- LOAD existing marks ---------- */
$stmt = $pdo->prepare("SELECT student_id, marks_obtained, grade FROM marks WHERE exam_id = :e");
$stmt->execute(['e' => $examId]);
$existing = [];
foreach ($stmt->fetchAll() as $r) {
    $existing[$r['student_id']] = $r;
}

// Summary statistics
$vals = array_map(fn($r) => (float) $r['marks_obtained'], $existing);
$entered = count($vals);
$avg  = $entered ? round(array_sum($vals) / $entered, 1) : null;
$high = $entered ? max($vals) : null;
$passCount = count(array_filter($vals, fn($v) => $total > 0 && ($v / $total) >= 0.4));
$passPct = $entered ? round($passCount / $entered * 100) : null;

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Enter Marks</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .alert{padding:13px 18px; border-radius:var(--radius); margin-bottom:18px; font-size:14px;}
  .alert.ok{background:#E8F3EA; border:1px solid #C5DEC9; color:#2F6B3A;}
  .alert.err{background:#FDECEA; border:1px solid #F5C6C3; color:#B3261E;}
  .badge{font-size:12px; font-weight:600; padding:4px 12px; border-radius:12px; white-space:nowrap; display:inline-block; background:var(--cream); color:var(--navy-deep);}
  .badge.pub{background:#E8F3EA; color:#2F6B3A;}
  .badge.draft{background:#FDECEA; color:#B3261E;}
  .exam-head{background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); padding:20px 24px; margin-bottom:22px; display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap;}
  .exam-head h2{margin-bottom:4px;}
  .exam-head p{color:var(--ink-soft); font-size:13.5px;}
  .mark-input{width:100px; padding:8px 10px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:14px;}
  .mark-input:focus{outline:none; border-color:var(--gold);}
  .out-of{color:var(--ink-soft); font-size:13px; margin-left:6px;}
  .grade-cell{font-weight:700; color:var(--navy-deep); font-family:'Fraunces',serif; font-size:16px;}
  .btn-primary{background:var(--navy-deep); color:#fff; border:none; padding:11px 26px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer;}
  .btn-primary:hover{background:var(--navy);}
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
      <li><a href="../Academic-Pages/teacher-attendance-records.php">Attendance Records</a></li>
      <li><a href="../Academic-Pages/teacher-homework.php">Homework</a></li>
      <li><a href="../Academic-Pages/teacher-exams.php" class="active">Manage Exams</a></li>
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
      <div class="crumb">
        <a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> /
        <a href="teacher-exams.php">Manage Exams</a> / Enter Marks
      </div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">

      <div class="exam-head">
        <div>
          <h2><?php echo htmlspecialchars($exam['exam_name']); ?></h2>
          <p>
            <?php echo htmlspecialchars($exam['subject']); ?> ·
            Class <?php echo htmlspecialchars($exam['class']); ?> ·
            <?php echo date('d M Y', strtotime($exam['exam_date'])); ?> ·
            Out of <?php echo $total; ?>
          </p>
        </div>
        <div>
          <span class="badge <?php echo $exam['is_published'] ? 'pub' : 'draft'; ?>">
            <?php echo $exam['is_published'] ? 'Published to students' : 'Not published yet'; ?>
          </span>
        </div>
      </div>

      <?php if (isset($_GET['saved'])): ?>
        <div class="alert ok">Marks saved successfully.</div>
      <?php endif; ?>
      <?php if ($error !== ''): ?>
        <div class="alert err"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <?php if (empty($students)): ?>
        <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
          There are no students in Class <?php echo htmlspecialchars($exam['class']); ?> yet.
        </div>
      <?php else: ?>

        <div class="stat-cards">
          <div class="stat-card"><h3><?php echo $entered; ?> / <?php echo count($students); ?></h3><p>Marks Entered</p><div class="bar"></div></div>
          <div class="stat-card"><h3><?php echo $avg !== null ? fmtMarks($avg) : '—'; ?></h3><p>Class Average</p><div class="bar"></div></div>
          <div class="stat-card"><h3><?php echo $high !== null ? fmtMarks($high) : '—'; ?></h3><p>Highest Score</p><div class="bar"></div></div>
          <div class="stat-card"><h3><?php echo $passPct !== null ? $passPct . '%' : '—'; ?></h3><p>Pass Rate (40%+)</p><div class="bar"></div></div>
        </div>

        <form method="POST" autocomplete="off">
          <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">

          <div class="table-wrap">
            <table class="admin-table">
              <tr><th>Roll</th><th>Student</th><th>Section</th><th>Marks Obtained</th><th>Grade</th></tr>
              <?php foreach ($students as $s): ?>
                <?php
                  $sid = $s['id'];
                  if (isset($formValues[$sid])) {
                      $value = $formValues[$sid];
                  } elseif (isset($existing[$sid])) {
                      $value = fmtMarks($existing[$sid]['marks_obtained']);
                  } else {
                      $value = '';
                  }
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($s['roll_no'] ?: '—'); ?></td>
                  <td><?php echo htmlspecialchars($s['full_name']); ?></td>
                  <td><?php echo htmlspecialchars($s['section'] ?: '—'); ?></td>
                  <td>
                    <input type="number" class="mark-input" name="marks[<?php echo $sid; ?>]"
                           data-sid="<?php echo $sid; ?>" min="0" max="<?php echo $total; ?>" step="0.01"
                           value="<?php echo htmlspecialchars($value); ?>" placeholder="—">
                    <span class="out-of">/ <?php echo $total; ?></span>
                  </td>
                  <td class="grade-cell" id="grade-<?php echo $sid; ?>"><?php echo htmlspecialchars($existing[$sid]['grade'] ?? '—'); ?></td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>

          <br>
          <button type="submit" class="btn-primary">Save Marks</button>
          <a href="teacher-exams.php" style="margin-left:14px; color:var(--ink-soft); font-size:14px;">&larr; Back to exams</a>
          <p style="color:var(--ink-soft); font-size:12.5px; margin-top:10px;">Leave a box empty if a student has not been marked yet (for example, was absent).</p>
        </form>

      <?php endif; ?>

    </div>
  </div>

</div>

<script>
  const TOTAL = <?php echo $total; ?>;

  function gradeFor(p) {
    if (p >= 90) return 'A+';
    if (p >= 80) return 'A';
    if (p >= 70) return 'B+';
    if (p >= 60) return 'B';
    if (p >= 50) return 'C';
    if (p >= 40) return 'D';
    return 'F';
  }

  document.querySelectorAll('.mark-input').forEach(function (input) {
    var cell = document.getElementById('grade-' + input.dataset.sid);

    function update() {
      var v = input.value.trim();
      if (v === '' || isNaN(v) || Number(v) < 0 || Number(v) > TOTAL) {
        cell.textContent = '—';
        return;
      }
      cell.textContent = gradeFor(Number(v) / TOTAL * 100);
    }

    input.addEventListener('input', update);
    update();
  });
</script>
</body>
</html>