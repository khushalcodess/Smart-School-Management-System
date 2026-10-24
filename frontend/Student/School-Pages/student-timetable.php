<?php
require __DIR__ . '/../student-init.php';

$days  = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$today = date('l');

$stmt = $pdo->prepare(
    "SELECT t.day, t.period, t.subject, u.full_name
     FROM timetable t JOIN users u ON t.teacher_id = u.id
     WHERE t.class = :c"
);
$stmt->execute(['c' => $timetableKey]);
$rows = $stmt->fetchAll();

$grid = [];
$maxPeriod = 0;
foreach ($rows as $r) {
    $grid[$r['period']][$r['day']] = $r;
    if ($r['period'] > $maxPeriod) { $maxPeriod = $r['period']; }
}

$extraCss = <<<'CSS'
.tt-grid{width:100%; border-collapse:collapse; background:var(--paper); border-radius:var(--radius); overflow:hidden;}
.tt-grid th{background:var(--cream); padding:10px; font-size:12.5px; text-transform:uppercase; color:var(--navy-deep); border:1px solid var(--line);}
.tt-grid th.today{background:var(--navy-deep); color:#fff;}
.tt-grid td{border:1px solid var(--line); padding:8px; vertical-align:top; min-width:120px; height:70px;}
.tt-grid td.today{background:rgba(200,150,62,0.06);}
.tt-period-label{background:var(--cream); font-weight:700; text-align:center; color:var(--navy-deep); width:70px;}
.tt-slot{background:rgba(200,150,62,0.12); border-radius:6px; padding:6px 8px; font-size:13px;}
.tt-slot strong{display:block; color:var(--navy-deep); font-size:13.5px;}
.tt-slot span{color:var(--ink-soft); font-size:12px;}
.tt-empty-cell{color:var(--line); text-align:center; font-size:12px;}
CSS;

$pageTitle = 'My Timetable'; $activePage = 'timetable'; $crumb = 'My Timetable';
require __DIR__ . '/../student-header.php';
?>

<h1>My Timetable</h1>
<p style="color:var(--ink-soft); margin-top:4px;"><?php echo htmlspecialchars($classLabel); ?></p><br>

<?php if (empty($rows)): ?>
  <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
    The timetable for <?php echo htmlspecialchars($timetableKey); ?> has not been set up yet.
  </div>
<?php else: ?>
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
              <?php if (isset($grid[$p][$d])): ?>
                <div class="tt-slot">
                  <strong><?php echo htmlspecialchars($grid[$p][$d]['subject']); ?></strong>
                  <span><?php echo htmlspecialchars($grid[$p][$d]['full_name']); ?></span>
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

<?php require __DIR__ . '/../student-footer.php'; ?>