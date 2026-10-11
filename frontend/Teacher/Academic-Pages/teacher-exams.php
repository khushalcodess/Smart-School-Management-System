<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$teacherId = $_SESSION['user_id'];
$today = date('Y-m-d');
$classOptions = array_map('strval', range(1, 12));

function validDate($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

// Teacher's own subject (pre-fills the form)
$stmt = $pdo->prepare("SELECT subject FROM teachers WHERE teacher_id = :id");
$stmt->execute(['id' => $teacherId]);
$mySubject = $stmt->fetchColumn() ?: '';

/* ---------- DELETE exam (own exams only) ---------- */
if (isset($_GET['delete'])) {
    $examId = (int) $_GET['delete'];

    $stmt = $pdo->prepare("SELECT id FROM exams WHERE id = :id AND created_by = :tid");
    $stmt->execute(['id' => $examId, 'tid' => $teacherId]);

    if ($stmt->fetchColumn()) {
        $pdo->beginTransaction();
        // Marks first (child), then the exam (parent) because of the foreign key
        $pdo->prepare("DELETE FROM marks WHERE exam_id = :id")->execute(['id' => $examId]);
        $pdo->prepare("DELETE FROM exams WHERE id = :id")->execute(['id' => $examId]);
        $pdo->commit();
    }
    header('Location: teacher-exams.php?deleted=1');
    exit;
}

/* ---------- PUBLISH / UNPUBLISH results (own exams only) ---------- */
if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare(
        "UPDATE exams SET is_published = 1 - is_published WHERE id = :id AND created_by = :tid"
    );
    $stmt->execute(['id' => (int) $_GET['toggle'], 'tid' => $teacherId]);
    header('Location: teacher-exams.php?toggled=1');
    exit;
}

/* ---------- CREATE exam ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['exam_name'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $class   = $_POST['class'] ?? '';
    $date    = $_POST['exam_date'] ?? '';
    $total   = $_POST['total_marks'] ?? '';

    $validTotal = ctype_digit((string) $total) && (int) $total >= 1 && (int) $total <= 500;

    if ($name === '' || $subject === '' || !in_array($class, $classOptions, true) || !validDate($date) || !$validTotal) {
        header('Location: teacher-exams.php?error=invalid');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO exams (exam_name, class, subject, exam_date, total_marks, created_by)
         VALUES (:n, :c, :s, :d, :t, :tid)"
    );
    $stmt->execute([
        'n' => $name, 'c' => $class, 's' => $subject,
        'd' => $date, 't' => (int) $total, 'tid' => $teacherId
    ]);

    header('Location: teacher-exams.php?saved=1');
    exit;
}

/* ---------- LIST this teacher's exams ---------- */
$stmt = $pdo->prepare(
    "SELECT e.id, e.exam_name, e.class, e.subject, e.exam_date, e.total_marks, e.is_published,
            (SELECT COUNT(*) FROM marks m WHERE m.exam_id = e.id) AS entered,
            (SELECT COUNT(*) FROM students s WHERE s.class = e.class) AS total_students
     FROM exams e
     WHERE e.created_by = :tid
     ORDER BY e.exam_date DESC"
);
$stmt->execute(['tid' => $teacherId]);
$exams = $stmt->fetchAll();

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Exams</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .alert{padding:13px 18px; border-radius:var(--radius); margin-bottom:18px; font-size:14px;}
  .alert.ok{background:#E8F3EA; border:1px solid #C5DEC9; color:#2F6B3A;}
  .alert.err{background:#FDECEA; border:1px solid #F5C6C3; color:#B3261E;}
  .badge{font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:12px; white-space:nowrap; display:inline-block;}
  .badge.upcoming{background:rgba(200,150,62,0.2); color:#8A6116;}
  .badge.done{background:var(--cream); color:var(--ink-soft);}
  .badge.pub{background:#E8F3EA; color:#2F6B3A;}
  .badge.draft{background:#FDECEA; color:#B3261E;}
  .row-actions{display:flex; gap:6px; flex-wrap:wrap;}
  .row-actions .action-btn{margin-right:0;}
  .action-btn.pub{background:rgba(84,114,90,0.15); color:#3D5A43;}
  .action-btn.pub:hover{background:rgba(84,114,90,0.25);}
  .mini-bar{height:6px; background:var(--cream); border-radius:3px; overflow:hidden; width:90px; margin-top:4px;}
  .mini-fill{height:100%; background:var(--gold); border-radius:3px;}
  .sub{font-size:12px; color:var(--ink-soft);}
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
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Manage Exams</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Manage Exams</h1><br>

      <?php if (isset($_GET['saved'])): ?>
        <div class="alert ok">Exam created. Use the "Marks" button to enter student marks.</div>
      <?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert ok">Exam and its marks were deleted.</div>
      <?php endif; ?>
      <?php if (isset($_GET['toggled'])): ?>
        <div class="alert ok">Result visibility updated.</div>
      <?php endif; ?>
      <?php if (($_GET['error'] ?? '') === 'invalid'): ?>
        <div class="alert err">Please fill every field correctly. Total marks must be between 1 and 500.</div>
      <?php endif; ?>

      <div class="feed-layout">

        <!-- LEFT: create exam -->
        <div class="admin-form" style="margin-bottom:0;">
          <h3 style="margin-bottom:16px;">Create New Exam</h3>
          <form method="POST" autocomplete="off">
            <div class="field">
              <label>Exam Name</label>
              <input type="text" name="exam_name" maxlength="100" placeholder="e.g. Unit Test 1" required>
            </div>
            <div class="field">
              <label>Subject</label>
              <input type="text" name="subject" maxlength="100" value="<?php echo htmlspecialchars($mySubject); ?>" required>
            </div>
            <div class="field">
              <label>Class</label>
              <select name="class" required>
                <option value="">-- Select Class --</option>
                <?php foreach ($classOptions as $c): ?>
                  <option value="<?php echo $c; ?>">Class <?php echo $c; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Exam Date</label>
              <input type="date" name="exam_date" required>
            </div>
            <div class="field">
              <label>Total Marks</label>
              <input type="number" name="total_marks" min="1" max="500" placeholder="e.g. 50" required>
            </div>
            <button type="submit">Create Exam</button>
          </form>
        </div>

        <!-- RIGHT: exam list -->
        <div>
          <?php if (empty($exams)): ?>
            <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
              No exams created yet.
            </div>
          <?php else: ?>
            <div class="table-wrap" style="overflow-x:auto;">
              <table class="admin-table">
                <tr><th>Exam</th><th>Class</th><th>Date</th><th>Marks Entered</th><th>Results</th><th>Actions</th></tr>
                <?php foreach ($exams as $e): ?>
                  <?php
                    $isUpcoming = $e['exam_date'] >= $today;
                    $entered    = (int) $e['entered'];
                    $totalStu   = (int) $e['total_students'];
                    $pct        = $totalStu > 0 ? round($entered / $totalStu * 100) : 0;
                  ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($e['exam_name']); ?></strong><br>
                      <span class="sub"><?php echo htmlspecialchars($e['subject']); ?> · out of <?php echo (int) $e['total_marks']; ?></span>
                    </td>
                    <td>Class <?php echo htmlspecialchars($e['class']); ?></td>
                    <td>
                      <?php echo date('d M Y', strtotime($e['exam_date'])); ?><br>
                      <span class="badge <?php echo $isUpcoming ? 'upcoming' : 'done'; ?>"><?php echo $isUpcoming ? 'Upcoming' : 'Completed'; ?></span>
                    </td>
                    <td>
                      <?php echo $entered; ?> / <?php echo $totalStu; ?>
                      <div class="mini-bar"><div class="mini-fill" style="width:<?php echo $pct; ?>%;"></div></div>
                    </td>
                    <td>
                      <span class="badge <?php echo $e['is_published'] ? 'pub' : 'draft'; ?>">
                        <?php echo $e['is_published'] ? 'Published' : 'Not published'; ?>
                      </span>
                    </td>
                    <td>
                      <div class="row-actions">
                        <a href="teacher-marks.php?exam=<?php echo $e['id']; ?>" class="action-btn edit">Marks</a>
                        <a href="teacher-exams.php?toggle=<?php echo $e['id']; ?>" class="action-btn pub"
                           <?php if (!$e['is_published']): ?>onclick="return confirm('Students will be able to see their marks for this exam. Publish now?');"<?php endif; ?>>
                          <?php echo $e['is_published'] ? 'Unpublish' : 'Publish'; ?>
                        </a>
                        <a href="teacher-exams.php?delete=<?php echo $e['id']; ?>" class="action-btn delete"
                           onclick="return confirm('Delete this exam and ALL its marks? This cannot be undone.');">Delete</a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>