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

// The teacher's own subject (pre-fills the form)
$stmt = $pdo->prepare("SELECT subject FROM teachers WHERE teacher_id = :id");
$stmt->execute(['id' => $teacherId]);
$mySubject = $stmt->fetchColumn() ?: '';

/* ---------- DELETE (own homework only) ---------- */
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM homework WHERE id = :id AND assigned_by = :tid");
    $stmt->execute(['id' => $_GET['delete'], 'tid' => $teacherId]);
    header('Location: teacher-homework.php?deleted=1');
    exit;
}

/* ---------- ASSIGN HOMEWORK ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $subject     = trim($_POST['subject'] ?? '');
    $description = mb_substr(trim($_POST['description'] ?? ''), 0, 500);
    $class       = $_POST['class'] ?? '';
    $dueDate     = $_POST['due_date'] ?? '';

    $validDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) && $dueDate >= $today;

    if ($title === '' || $subject === '' || !in_array($class, $classOptions, true) || !$validDate) {
        header('Location: teacher-homework.php?error=invalid');
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO homework (title, subject, description, class, due_date, assigned_by)
         VALUES (:title, :subject, :description, :class, :due, :tid)"
    );
    $stmt->execute([
        'title' => $title, 'subject' => $subject, 'description' => $description,
        'class' => $class, 'due' => $dueDate, 'tid' => $teacherId
    ]);

    header('Location: teacher-homework.php?saved=1');
    exit;
}

/* ---------- LIST (this teacher's homework, upcoming first) ---------- */
$filterClass = $_GET['filter'] ?? '';

$sql = "SELECT id, title, subject, description, class, due_date
        FROM homework
        WHERE assigned_by = :tid";
$params = ['tid' => $teacherId];

if (in_array($filterClass, $classOptions, true)) {
    $sql .= " AND class = :class";
    $params['class'] = $filterClass;
}

$sql .= " ORDER BY (due_date < :today), due_date ASC";
$params['today'] = $today;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$homeworkList = $stmt->fetchAll();

$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Homework</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .alert{padding:13px 18px; border-radius:var(--radius); margin-bottom:18px; font-size:14px;}
  .alert.ok{background:#E8F3EA; border:1px solid #C5DEC9; color:#2F6B3A;}
  .alert.err{background:#FDECEA; border:1px solid #F5C6C3; color:#B3261E;}
  .hw-top{display:flex; justify-content:space-between; align-items:flex-start; gap:10px;}
  .badges{display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;}
  .badge{font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:12px; white-space:nowrap; background:var(--cream); color:var(--navy-deep);}
  .badge.class{background:rgba(19,42,70,0.08);}
  .badge.upcoming{background:#E8F3EA; color:#2F6B3A;}
  .badge.today{background:rgba(200,150,62,0.2); color:#8A6116;}
  .badge.overdue{background:#FDECEA; color:#B3261E;}
  .filter-line{display:flex; align-items:center; gap:10px; margin-bottom:16px;}
  .filter-line select{padding:8px 12px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:13.5px;}
  .feed-card.done{opacity:.65;}
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
      <li><a href="../Academic-Pages/teacher-homework.php" class="active">Homework</a></li>
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
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Homework</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Homework</h1><br>

      <?php if (isset($_GET['saved'])): ?>
        <div class="alert ok">Homework assigned successfully.</div>
      <?php endif; ?>
      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert ok">Homework deleted.</div>
      <?php endif; ?>
      <?php if (($_GET['error'] ?? '') === 'invalid'): ?>
        <div class="alert err">Please fill in every field and choose today or a future due date.</div>
      <?php endif; ?>

      <div class="feed-layout">

        <!-- LEFT: assign form -->
        <div class="admin-form" style="margin-bottom:0;">
          <h3 style="margin-bottom:16px;">Assign New Homework</h3>
          <form method="POST" autocomplete="off">
            <div class="field">
              <label>Title</label>
              <input type="text" name="title" maxlength="150" required>
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
              <label>Due Date</label>
              <input type="date" name="due_date" min="<?php echo $today; ?>" required>
            </div>
            <div class="field">
              <label>Details</label>
              <textarea name="description" maxlength="500" placeholder="Instructions, page numbers, etc."></textarea>
            </div>
            <button type="submit">Assign Homework</button>
          </form>
        </div>

        <!-- RIGHT: my assigned homework -->
        <div>
          <form method="GET" class="filter-line">
            <label style="font-size:13px; font-weight:600; color:var(--navy-deep);">Show:</label>
            <select name="filter" onchange="this.form.submit()">
              <option value="">All my classes</option>
              <?php foreach ($classOptions as $c): ?>
                <option value="<?php echo $c; ?>" <?php echo ($filterClass === $c) ? 'selected' : ''; ?>>Class <?php echo $c; ?></option>
              <?php endforeach; ?>
            </select>
          </form>

          <?php if (empty($homeworkList)): ?>
            <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
              No homework assigned yet.
            </div>
          <?php endif; ?>

          <?php foreach ($homeworkList as $h): ?>
            <?php
              $due = new DateTime($h['due_date']);
              $now = new DateTime($today);
              $daysLeft = (int) $now->diff($due)->format('%r%a');

              if ($daysLeft < 0)       { $statusClass = 'overdue';  $statusText = 'Overdue'; }
              elseif ($daysLeft === 0) { $statusClass = 'today';    $statusText = 'Due today'; }
              elseif ($daysLeft === 1) { $statusClass = 'upcoming'; $statusText = 'Due tomorrow'; }
              else                     { $statusClass = 'upcoming'; $statusText = 'Due in ' . $daysLeft . ' days'; }
            ?>
            <div class="feed-card <?php echo $daysLeft < 0 ? 'done' : ''; ?>">
              <div class="hw-top">
                <h3><?php echo htmlspecialchars($h['title']); ?></h3>
                <div class="badges">
                  <span class="badge class">Class <?php echo htmlspecialchars($h['class']); ?></span>
                  <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                </div>
              </div>
              <p style="font-size:12.5px; color:var(--ink-soft); margin-bottom:6px;"><?php echo htmlspecialchars($h['subject']); ?></p>
              <?php if (!empty($h['description'])): ?>
                <p class="content"><?php echo nl2br(htmlspecialchars($h['description'])); ?></p>
              <?php endif; ?>
              <div class="feed-meta">
                <span>Due <?php echo $due->format('d M Y'); ?></span>
                <a href="teacher-homework.php?delete=<?php echo $h['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this homework?');">Delete</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>