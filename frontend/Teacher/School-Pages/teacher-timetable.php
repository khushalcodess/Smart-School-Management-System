<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$teacherId = $_SESSION['user_id'];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$today = date('l');

// Only THIS teacher's slots
$stmt = $pdo->prepare("SELECT class, day, period, subject FROM timetable WHERE teacher_id = :id");
$stmt->execute(['id' => $teacherId]);
$rows = $stmt->fetchAll();

$grid = [];
$maxPeriod = 0;
foreach ($rows as $r) {
    $grid[$r['period']][$r['day']][] = $r;   // array, in case two classes share a slot
    if ($r['period'] > $maxPeriod) { $maxPeriod = $r['period']; }
}
$classCount = count(array_unique(array_column($rows, 'class')));

$activePage = 'timetable';
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Timetable</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .tt-grid{width:100%; border-collapse:collapse; background:var(--paper); border-radius:var(--radius); overflow:hidden;}
  .tt-grid th{background:var(--cream); padding:10px; font-size:12.5px; text-transform:uppercase; color:var(--navy-deep); border:1px solid var(--line);}
  .tt-grid th.today{background:var(--navy-deep); color:#fff;}
  .tt-grid td{border:1px solid var(--line); padding:8px; vertical-align:top; min-width:120px; height:70px;}
  .tt-grid td.today{background:rgba(200,150,62,0.06);}
  .tt-period-label{background:var(--cream); font-weight:700; text-align:center; color:var(--navy-deep); width:70px;}
  .tt-slot{background:rgba(200,150,62,0.12); border-radius:6px; padding:6px 8px; font-size:13px; margin-bottom:4px;}
  .tt-slot strong{display:block; color:var(--navy-deep); font-size:13.5px;}
  .tt-slot span{color:var(--ink-soft); font-size:12px;}
  .tt-empty-cell{color:var(--line); text-align:center; font-size:12px;}
</style>
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/../teacher-sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / My Timetable</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>My Timetable</h1><br>

      <?php if (empty($rows)): ?>
        <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
          No periods have been assigned to you yet. The admin assigns periods from the Timetable page.
        </div>
      <?php else: ?>

        <div class="stat-cards">
          <div class="stat-card"><h3><?php echo count($rows); ?></h3><p>Periods per Week</p><div class="bar"></div></div>
          <div class="stat-card"><h3><?php echo $classCount; ?></h3><p>Classes</p><div class="bar"></div></div>
        </div>

        <div style="overflow-x:auto;">
          <table class="tt-grid">
            <tr>
              <th>Period</th>
              <?php foreach ($days as $d): ?>
                <th class="<?php echo $d === $today ? 'today' : ''; ?>"><?php echo $d; ?></th>
              <?php endforeach; ?>
            </tr>
            <?php for ($p = 1; $p <= $maxPeriod; $p++): ?>
              <tr>
                <td class="tt-period-label"><?php echo $p; ?></td>
                <?php foreach ($days as $d): ?>
                  <td class="<?php echo $d === $today ? 'today' : ''; ?>">
                    <?php if (!empty($grid[$p][$d])): ?>
                      <?php foreach ($grid[$p][$d] as $slot): ?>
                        <div class="tt-slot">
                          <strong>Class <?php echo htmlspecialchars($slot['class']); ?></strong>
                          <span><?php echo htmlspecialchars($slot['subject']); ?></span>
                        </div>
                      <?php endforeach; ?>
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