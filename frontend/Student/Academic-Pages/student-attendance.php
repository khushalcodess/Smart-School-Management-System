<?php
require __DIR__ . '/../student-init.php';

$thisMonth = date('Y-m');
$month = $_GET['month'] ?? $thisMonth;
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) || $month > $thisMonth) {
    $month = $thisMonth;
}

$first       = strtotime($month . '-01');
$daysInMonth = (int) date('t', $first);
$lead        = (int) date('N', $first) - 1;          // blank cells before the 1st (Monday = 0)
$prev        = date('Y-m', strtotime('-1 month', $first));
$next        = date('Y-m', strtotime('+1 month', $first));

// This month's records for THIS student only
$stmt = $pdo->prepare(
    "SELECT attendance_date, status FROM attendance
     WHERE student_id = :id AND attendance_date BETWEEN :s AND :e
     ORDER BY attendance_date"
);
$stmt->execute(['id' => $studentId, 's' => $month . '-01', 'e' => date('Y-m-t', $first)]);
$map = [];
foreach ($stmt->fetchAll() as $r) { $map[$r['attendance_date']] = $r['status']; }

$present = count(array_filter($map, fn($s) => $s === 'Present'));
$total   = count($map);
$absent  = $total - $present;
$monthPct = $total > 0 ? round($present / $total * 100) : null;

// Overall (all time)
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(status = 'Present'), 0) AS p, COUNT(*) AS t FROM attendance WHERE student_id = :id"
);
$stmt->execute(['id' => $studentId]);
$all = $stmt->fetch();
$overallPct = $all['t'] > 0 ? round($all['p'] / $all['t'] * 100) : null;

$absentDates = array_keys(array_filter($map, fn($s) => $s === 'Absent'));

$extraCss = <<<'CSS'
.cal-nav{display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;}
.cal-nav a{font-weight:600; color:var(--navy-deep);}
.cal-nav .off{color:var(--line);}
.cal-grid{display:grid; grid-template-columns:repeat(7,1fr); gap:6px;}
.cal-h{text-align:center; font-size:11.5px; font-weight:700; color:var(--ink-soft); text-transform:uppercase; padding-bottom:4px;}
.cal-d{aspect-ratio:1/1; display:flex; align-items:center; justify-content:center; border-radius:8px; font-size:13.5px; background:var(--cream); color:var(--ink-soft);}
.cal-d.p{background:#E8F3EA; color:#2F6B3A; font-weight:700;}
.cal-d.a{background:#FDECEA; color:#B3261E; font-weight:700;}
.cal-d.sun{opacity:.45;}
.cal-d.today{outline:2px solid var(--gold);}
.legend{display:flex; gap:16px; margin-top:14px; font-size:12.5px; color:var(--ink-soft);}
.legend i{display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:6px; vertical-align:-1px;}
CSS;

$pageTitle = 'My Attendance'; $activePage = 'attendance'; $crumb = 'My Attendance';
require __DIR__ . '/../student-header.php';
?>

<h1>My Attendance</h1><br>

<?php if ($overallPct !== null && $overallPct < 75): ?>
  <div class="note warn">Your overall attendance is <strong><?php echo $overallPct; ?>%</strong>, which is below the 75% minimum. Please speak to your class teacher.</div>
<?php endif; ?>

<div class="stat-cards">
  <div class="stat-card"><h3><?php echo $present; ?></h3><p>Days Present</p><div class="bar"></div></div>
  <div class="stat-card"><h3><?php echo $absent; ?></h3><p>Days Absent</p><div class="bar"></div></div>
  <div class="stat-card"><h3 style="color:<?php echo pctColor($monthPct); ?>;"><?php echo $monthPct !== null ? $monthPct . '%' : '—'; ?></h3><p>This Month</p><div class="bar"></div></div>
  <div class="stat-card"><h3 style="color:<?php echo pctColor($overallPct); ?>;"><?php echo $overallPct !== null ? $overallPct . '%' : '—'; ?></h3><p>Overall</p><div class="bar"></div></div>
</div>

<div class="dash-grid">

  <div class="panel-card">
    <div class="cal-nav">
      <a href="?month=<?php echo $prev; ?>">&larr; Previous</a>
      <h2 style="margin:0;"><?php echo date('F Y', $first); ?></h2>
      <?php if ($month < $thisMonth): ?>
        <a href="?month=<?php echo $next; ?>">Next &rarr;</a>
      <?php else: ?>
        <span class="off">Next &rarr;</span>
      <?php endif; ?>
    </div>

    <div class="cal-grid">
      <?php foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $h): ?>
        <div class="cal-h"><?php echo $h; ?></div>
      <?php endforeach; ?>

      <?php for ($i = 0; $i < $lead; $i++): ?><div></div><?php endfor; ?>

      <?php for ($d = 1; $d <= $daysInMonth; $d++): ?>
        <?php
          $date   = sprintf('%s-%02d', $month, $d);
          $status = $map[$date] ?? null;
          $cls = 'cal-d';
          if ($status === 'Present')     { $cls .= ' p'; }
          elseif ($status === 'Absent')  { $cls .= ' a'; }
          elseif ((int) date('N', strtotime($date)) === 7) { $cls .= ' sun'; }
          if ($date === date('Y-m-d')) { $cls .= ' today'; }
        ?>
        <div class="<?php echo $cls; ?>" title="<?php echo $status ?? 'No record'; ?>"><?php echo $d; ?></div>
      <?php endfor; ?>
    </div>

    <div class="legend">
      <span><i style="background:#E8F3EA;"></i>Present</span>
      <span><i style="background:#FDECEA;"></i>Absent</span>
      <span><i style="background:var(--cream);"></i>No record</span>
    </div>
  </div>

  <div class="panel-card">
    <h2>Absent Days</h2>
    <?php if (empty($absentDates)): ?>
      <p style="color:var(--ink-soft); font-size:13.5px;">No absences in <?php echo date('F', $first); ?>.</p>
    <?php else: ?>
      <?php foreach ($absentDates as $ad): ?>
        <div class="event-item"><p><?php echo date('l, d M', strtotime($ad)); ?></p></div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

<?php require __DIR__ . '/../student-footer.php'; ?>