<?php
require __DIR__ . '/../student-init.php';

$todayName = date('l');
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

// Attendance over the last 30 days
$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(status = 'Present'), 0) AS p, COUNT(*) AS t
     FROM attendance
     WHERE student_id = :id AND attendance_date >= (CURDATE() - INTERVAL 30 DAY)"
);
$stmt->execute(['id' => $studentId]);
$att = $stmt->fetch();
$attPct = $att['t'] > 0 ? round($att['p'] / $att['t'] * 100) : null;

// Homework still to do
$stmt = $pdo->prepare("SELECT COUNT(*) FROM homework WHERE class = :c AND due_date >= CURDATE()");
$stmt->execute(['c' => $myClass]);
$pendingHw = $stmt->fetchColumn();

// Upcoming exams
$stmt = $pdo->prepare(
    "SELECT exam_name, subject, exam_date, total_marks FROM exams
     WHERE class = :c AND exam_date >= CURDATE() ORDER BY exam_date ASC LIMIT 4"
);
$stmt->execute(['c' => $myClass]);
$upcomingExams = $stmt->fetchAll();

// Average score (published results only)
$stmt = $pdo->prepare(
    "SELECT AVG(m.marks_obtained / e.total_marks * 100)
     FROM marks m JOIN exams e ON m.exam_id = e.id
     WHERE m.student_id = :id AND e.is_published = 1 AND e.total_marks > 0"
);
$stmt->execute(['id' => $studentId]);
$avg = $stmt->fetchColumn();
$avgScore = ($avg === null || $avg === false) ? null : round($avg);

// Today's periods
$stmt = $pdo->prepare(
    "SELECT t.period, t.subject, u.full_name
     FROM timetable t JOIN users u ON t.teacher_id = u.id
     WHERE t.class = :c AND t.day = :d ORDER BY t.period"
);
$stmt->execute(['c' => $timetableKey, 'd' => $todayName]);
$todaySlots = $stmt->fetchAll();

// Homework due soon
$stmt = $pdo->prepare(
    "SELECT title, subject, due_date FROM homework
     WHERE class = :c AND due_date >= CURDATE() ORDER BY due_date ASC LIMIT 5"
);
$stmt->execute(['c' => $myClass]);
$dueSoon = $stmt->fetchAll();

// Latest notices (school-wide + my class)
$stmt = $pdo->prepare(
    "SELECT title, posted_at FROM notices
     WHERE target_class = 'All' OR target_class = :c
     ORDER BY posted_at DESC LIMIT 4"
);
$stmt->execute(['c' => $myClass]);
$latestNotices = $stmt->fetchAll();

$pageTitle = 'Dashboard'; $activePage = 'dashboard'; $crumb = 'Dashboard';
require __DIR__ . '/../student-header.php';
?>

<h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($_SESSION['full_name']); ?></h1>
<p style="color:var(--ink-soft); margin-top:4px;">
  <?php echo htmlspecialchars($classLabel); ?><?php echo $me['roll_no'] ? ' · Roll No ' . htmlspecialchars($me['roll_no']) : ''; ?>
</p><br>

<div class="icon-stat-cards">
  <div class="icon-stat-card">
    <div class="icon-circle sage">✅</div>
    <div class="stat-info"><span>Attendance (30 days)</span>
      <strong style="color:<?php echo pctColor($attPct); ?>;"><?php echo $attPct !== null ? $attPct . '%' : '—'; ?></strong></div>
  </div>
  <div class="icon-stat-card">
    <div class="icon-circle gold">📚</div>
    <div class="stat-info"><span>Homework Pending</span><strong><?php echo $pendingHw; ?></strong></div>
  </div>
  <div class="icon-stat-card">
    <div class="icon-circle rose">📝</div>
    <div class="stat-info"><span>Upcoming Exams</span><strong><?php echo count($upcomingExams); ?></strong></div>
  </div>
  <div class="icon-stat-card">
    <div class="icon-circle blue">🏆</div>
    <div class="stat-info"><span>Average Score</span><strong><?php echo $avgScore !== null ? $avgScore . '%' : '—'; ?></strong></div>
  </div>
</div>

<div class="dash-grid">
  <div class="panel-card">
    <h2>Today's Schedule — <?php echo $todayName; ?></h2>
    <table class="admin-table">
      <tr><th>Period</th><th>Subject</th><th>Teacher</th></tr>
      <?php foreach ($todaySlots as $s): ?>
        <tr>
          <td><?php echo htmlspecialchars($s['period']); ?></td>
          <td><?php echo htmlspecialchars($s['subject']); ?></td>
          <td><?php echo htmlspecialchars($s['full_name']); ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($todaySlots)): ?>
        <tr><td colspan="3" style="text-align:center; color:var(--ink-soft);">No periods scheduled for today.</td></tr>
      <?php endif; ?>
    </table>
  </div>

  <div class="panel-card">
    <h2>Homework Due Soon</h2>
    <?php if (empty($dueSoon)): ?>
      <p style="color:var(--ink-soft); font-size:13.5px;">Nothing pending. Well done!</p>
    <?php endif; ?>
    <?php foreach ($dueSoon as $h): ?>
      <?php $d = new DateTime($h['due_date']); [$txt, $cls] = dueText($h['due_date']); ?>
      <div class="event-item">
        <div class="event-date-badge"><span class="day"><?php echo $d->format('d'); ?></span><span class="mon"><?php echo $d->format('M'); ?></span></div>
        <div style="flex:1;"><p><?php echo htmlspecialchars($h['title']); ?></p><span class="sub"><?php echo htmlspecialchars($h['subject']); ?></span></div>
        <span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="dash-grid">
  <div class="panel-card">
    <h2>Upcoming Exams</h2>
    <?php if (empty($upcomingExams)): ?>
      <p style="color:var(--ink-soft); font-size:13.5px;">No exams scheduled.</p>
    <?php endif; ?>
    <?php foreach ($upcomingExams as $e): ?>
      <?php $d = new DateTime($e['exam_date']); [$txt, $cls] = dueText($e['exam_date']); ?>
      <div class="event-item">
        <div class="event-date-badge"><span class="day"><?php echo $d->format('d'); ?></span><span class="mon"><?php echo $d->format('M'); ?></span></div>
        <div style="flex:1;"><p><?php echo htmlspecialchars($e['exam_name']); ?> — <?php echo htmlspecialchars($e['subject']); ?></p><span class="sub">Out of <?php echo (int) $e['total_marks']; ?> marks</span></div>
        <span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="panel-card">
    <h2>Latest Notices</h2>
    <?php if (empty($latestNotices)): ?>
      <p style="color:var(--ink-soft); font-size:13.5px;">No notices.</p>
    <?php endif; ?>
    <?php foreach ($latestNotices as $n): ?>
      <div class="event-item">
        <div><p><?php echo htmlspecialchars($n['title']); ?></p><span class="sub"><?php echo date('d M Y', strtotime($n['posted_at'])); ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/../student-footer.php'; ?>