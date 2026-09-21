<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = trim($_POST['class']);
    $day = $_POST['day'];
    $period = $_POST['period'];
    $subject = trim($_POST['subject']);
    $teacherId = $_POST['teacher_id'];

    $stmt = $pdo->prepare(
        "INSERT INTO timetable (class, day, period, subject, teacher_id) VALUES (:class, :day, :period, :subject, :teacher_id)"
    );
    $stmt->execute([
        'class' => $class,
        'day' => $day,
        'period' => $period,
        'subject' => $subject,
        'teacher_id' => $teacherId
    ]);

    header('Location: admin-timetable.php?class=' . urlencode($class));
    exit;
}

$teachers = $pdo->query(
    "SELECT users.id, users.full_name, teachers.subject
     FROM users JOIN teachers ON users.id = teachers.teacher_id"
)->fetchAll();

$timetable = $pdo->query(
    "SELECT timetable.id, timetable.class, timetable.day, timetable.period, timetable.subject, users.full_name
     FROM timetable
     JOIN users ON timetable.teacher_id = users.id
     ORDER BY timetable.class, FIELD(timetable.day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), timetable.period"
)->fetchAll();

// Distinct list of classes that actually have timetable entries
$classList = array_values(array_unique(array_column($timetable, 'class')));
sort($classList);

// Which class is currently being viewed in the grid
$selectedClass = $_GET['class'] ?? ($classList[0] ?? null);

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// Work out how many periods to show (based on the highest period number used)
$maxPeriod = 0;
foreach ($timetable as $row) {
    if ($row['period'] > $maxPeriod) $maxPeriod = $row['period'];
}
if ($maxPeriod < 1) $maxPeriod = 6; // sensible default if table is empty

// Build a lookup: grid[period][day] = row data, only for the selected class
$grid = [];
foreach ($timetable as $row) {
    if ($row['class'] === $selectedClass) {
        $grid[$row['period']][$row['day']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Timetable</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .class-tabs{display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap;}
  .class-tab{
    padding:7px 16px; border-radius:20px; font-size:13.5px; font-weight:600;
    background:var(--paper); border:1px solid var(--line); color:var(--ink-soft);
  }
  .class-tab.active{background:var(--navy-deep); color:#fff; border-color:var(--navy-deep);}
  .tt-grid{width:100%; border-collapse:collapse; background:var(--paper); border-radius:var(--radius); overflow:hidden;}
  .tt-grid th{background:var(--cream); padding:10px; font-size:12.5px; text-transform:uppercase; color:var(--navy-deep); border:1px solid var(--line);}
  .tt-grid td{border:1px solid var(--line); padding:8px; vertical-align:top; min-width:120px; height:70px;}
  .tt-period-label{background:var(--cream); font-weight:700; text-align:center; color:var(--navy-deep); width:70px;}
  .tt-slot{background:rgba(200,150,62,0.08); border-radius:6px; padding:6px 8px; font-size:13px; height:100%;}
  .tt-slot strong{display:block; color:var(--navy-deep); font-size:13.5px;}
  .tt-slot span{color:var(--ink-soft); font-size:12px;}
  .tt-slot .action-btn.delete{margin-top:4px; padding:2px 8px; font-size:11px;}
  .tt-empty-cell{color:var(--line); text-align:center; font-size:12px;}
</style>
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>
    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/admin-dashboard.php">Dashboard</a></li>
      <li><a href="../Overview-Pages/admin-analytics.php">Analytics & Reports</a></li>
    </ul>
    <div class="nav-label">People</div>
    <ul>
      <li><a href="../People-Pages/admin-students.php">Manage Students</a></li>
      <li><a href="../People-Pages/admin-teachers.php">Manage Teachers</a></li>
    </ul>
    <div class="nav-label">Communication</div>
    <ul>
      <li><a href="../Communication-Pages/admin-announcements.php">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-notices.php">Notices</a></li>
      <li><a href="../Communication-Pages/admin-events.php">Events</a></li>
    </ul>
    <div class="nav-label">School</div>
    <ul>
      <li><a href="admin-timetable.php" class="active">Timetable</a></li>
      <li><a href="admin-gallery.php">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Timetable</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'],0,1))); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Timetable</h1><br>

      <div class="admin-form">
        <h3 style="margin-bottom:16px;">Add Timetable Slot</h3>
        <form method="POST">
          <div class="field">
            <label>Class</label>
            <input type="text" name="class" placeholder="e.g. 10-A" required>
          </div>
          <div class="field">
            <label>Day</label>
            <select name="day" required>
              <option value="">-- Select Day --</option>
              <?php foreach ($days as $d): ?>
                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Period</label>
            <input type="number" name="period" placeholder="e.g. 1" required>
          </div>
          <div class="field">
            <label>Subject</label>
            <input type="text" name="subject" required>
          </div>
          <div class="field">
            <label>Teacher</label>
            <select name="teacher_id" required>
              <option value="">-- Select Teacher --</option>
              <?php foreach ($teachers as $t): ?>
                <option value="<?php echo $t['id']; ?>">
                  <?php echo htmlspecialchars($t['full_name']) . ' (' . htmlspecialchars($t['subject']) . ')'; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit">Add Slot</button>
        </form>
      </div>

      <h2>Weekly Timetable</h2>

      <?php if (empty($classList)): ?>
        <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
          No timetable slots added yet.
        </div>
      <?php else: ?>

        <div class="class-tabs">
          <?php foreach ($classList as $c): ?>
            <a href="admin-timetable.php?class=<?php echo urlencode($c); ?>"
               class="class-tab <?php echo ($c === $selectedClass) ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($c); ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div style="overflow-x:auto;">
          <table class="tt-grid">
            <tr>
              <th>Period</th>
              <?php foreach ($days as $d): ?>
                <th><?php echo $d; ?></th>
              <?php endforeach; ?>
            </tr>
            <?php for ($p = 1; $p <= $maxPeriod; $p++): ?>
              <tr>
                <td class="tt-period-label"><?php echo $p; ?></td>
                <?php foreach ($days as $d): ?>
                  <td>
                    <?php if (isset($grid[$p][$d])): ?>
                      <?php $slot = $grid[$p][$d]; ?>
                      <div class="tt-slot">
                        <strong><?php echo htmlspecialchars($slot['subject']); ?></strong>
                        <span><?php echo htmlspecialchars($slot['full_name']); ?></span><br>
                        <a href="admin-delete-timetable.php?id=<?php echo $slot['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this slot?');">Delete</a>
                      </div>
                    <?php else: ?>
                      <div class="tt-empty-cell">—</div>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endfor; ?>
          </table>
        </div>

      <?php endif; ?>

    </div>
  </div>

</div>
</body>
</html>