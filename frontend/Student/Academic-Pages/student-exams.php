<?php
require __DIR__ . '/../student-init.php';

// Upcoming exam schedule for my class
$stmt = $pdo->prepare(
    "SELECT exam_name, subject, exam_date, total_marks FROM exams
     WHERE class = :c AND exam_date >= CURDATE() ORDER BY exam_date ASC"
);
$stmt->execute(['c' => $myClass]);
$upcoming = $stmt->fetchAll();

// MY marks, and ONLY for exams the teacher has published
$stmt = $pdo->prepare(
    "SELECT e.exam_name, e.subject, e.exam_date, e.total_marks, m.marks_obtained, m.grade
     FROM marks m JOIN exams e ON m.exam_id = e.id
     WHERE m.student_id = :id AND e.is_published = 1
     ORDER BY e.exam_date DESC"
);
$stmt->execute(['id' => $studentId]);
$results = $stmt->fetchAll();

$pcts = [];
foreach ($results as $i => $r) {
    $results[$i]['pct'] = $r['total_marks'] > 0 ? round($r['marks_obtained'] / $r['total_marks'] * 100) : 0;
    $pcts[] = $results[$i]['pct'];
}
$avg  = $pcts ? round(array_sum($pcts) / count($pcts)) : null;
$best = $pcts ? max($pcts) : null;

$pageTitle = 'Exams & Results'; $activePage = 'exams'; $crumb = 'Exams & Results';
require __DIR__ . '/../student-header.php';
?>

<h1>Exams &amp; Results</h1><br>

<div class="stat-cards">
  <div class="stat-card"><h3><?php echo count($upcoming); ?></h3><p>Upcoming Exams</p><div class="bar"></div></div>
  <div class="stat-card"><h3><?php echo count($results); ?></h3><p>Results Available</p><div class="bar"></div></div>
  <div class="stat-card"><h3 style="color:<?php echo pctColor($avg); ?>;"><?php echo $avg !== null ? $avg . '%' : '—'; ?></h3><p>Average Score</p><div class="bar"></div></div>
  <div class="stat-card"><h3><?php echo $best !== null ? $best . '%' : '—'; ?></h3><p>Best Score</p><div class="bar"></div></div>
</div>

<h2>Upcoming Exams</h2>
<?php if (empty($upcoming)): ?>
  <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); margin-bottom:28px;">No exams scheduled for your class.</div>
<?php else: ?>
  <div class="table-wrap" style="margin-bottom:28px;">
    <table class="admin-table">
      <tr><th>Exam</th><th>Subject</th><th>Date</th><th>Total Marks</th><th></th></tr>
      <?php foreach ($upcoming as $e): ?>
        <?php [$txt, $cls] = dueText($e['exam_date']); ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($e['exam_name']); ?></strong></td>
          <td><?php echo htmlspecialchars($e['subject']); ?></td>
          <td><?php echo date('l, d M Y', strtotime($e['exam_date'])); ?></td>
          <td><?php echo (int) $e['total_marks']; ?></td>
          <td><span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>

<h2>My Results</h2>
<?php if (empty($results)): ?>
  <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">No results have been published yet.</div>
<?php else: ?>
  <div class="table-wrap" style="overflow-x:auto;">
    <table class="admin-table">
      <tr><th>Exam</th><th>Subject</th><th>Date</th><th>Marks</th><th>Score</th><th>Grade</th><th>Result</th></tr>
      <?php foreach ($results as $r): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($r['exam_name']); ?></strong></td>
          <td><?php echo htmlspecialchars($r['subject']); ?></td>
          <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
          <td><?php echo fmtMarks($r['marks_obtained']); ?> / <?php echo (int) $r['total_marks']; ?></td>
          <td>
            <div class="pct-wrap">
              <div class="pct-bar"><div class="pct-fill" style="width:<?php echo $r['pct']; ?>%; background:<?php echo pctColor($r['pct']); ?>;"></div></div>
              <strong style="color:<?php echo pctColor($r['pct']); ?>; font-size:13px;"><?php echo $r['pct']; ?>%</strong>
            </div>
          </td>
          <td><strong style="font-family:'Fraunces',serif; font-size:16px; color:var(--navy-deep);"><?php echo htmlspecialchars($r['grade'] ?? '—'); ?></strong></td>
          <td><span class="badge <?php echo $r['pct'] >= 40 ? 'green' : 'red'; ?>"><?php echo $r['pct'] >= 40 ? 'Pass' : 'Fail'; ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../student-footer.php'; ?>