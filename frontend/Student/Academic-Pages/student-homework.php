<?php
require __DIR__ . '/../student-init.php';

$view = (($_GET['view'] ?? '') === 'past') ? 'past' : 'upcoming';

// These two fragments are fixed strings from this file, never user input
$cond  = $view === 'past' ? 'h.due_date < CURDATE()' : 'h.due_date >= CURDATE()';
$order = $view === 'past' ? 'h.due_date DESC' : 'h.due_date ASC';

$stmt = $pdo->prepare(
    "SELECT h.title, h.subject, h.description, h.due_date, u.full_name
     FROM homework h JOIN users u ON h.assigned_by = u.id
     WHERE h.class = :c AND $cond
     ORDER BY $order"
);
$stmt->execute(['c' => $myClass]);
$list = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT COALESCE(SUM(due_date >= CURDATE()), 0) AS up, COALESCE(SUM(due_date < CURDATE()), 0) AS past
     FROM homework WHERE class = :c"
);
$stmt->execute(['c' => $myClass]);
$counts = $stmt->fetch();

$pageTitle = 'Homework'; $activePage = 'homework'; $crumb = 'Homework';
require __DIR__ . '/../student-header.php';
?>

<h1>Homework</h1><br>

<div style="max-width:760px;">
  <div class="tabs">
    <a href="?view=upcoming" class="tab <?php echo $view === 'upcoming' ? 'active' : ''; ?>">To Do (<?php echo (int) $counts['up']; ?>)</a>
    <a href="?view=past" class="tab <?php echo $view === 'past' ? 'active' : ''; ?>">Past (<?php echo (int) $counts['past']; ?>)</a>
  </div>

  <?php if (empty($list)): ?>
    <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
      <?php echo $view === 'past' ? 'No past homework.' : 'No homework pending. Enjoy!'; ?>
    </div>
  <?php endif; ?>

  <?php foreach ($list as $h): ?>
    <?php $due = new DateTime($h['due_date']); [$txt, $cls] = dueText($h['due_date']); ?>
    <div class="feed-card" <?php echo $view === 'past' ? 'style="opacity:.7;"' : ''; ?>>
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
        <h3><?php echo htmlspecialchars($h['title']); ?></h3>
        <?php if ($view === 'upcoming'): ?>
          <span class="badge <?php echo $cls; ?>"><?php echo $txt; ?></span>
        <?php endif; ?>
      </div>
      <p class="sub" style="margin-bottom:6px;"><?php echo htmlspecialchars($h['subject']); ?></p>
      <?php if (!empty($h['description'])): ?>
        <p class="content"><?php echo nl2br(htmlspecialchars($h['description'])); ?></p>
      <?php endif; ?>
      <div class="feed-meta">
        <span>Due <?php echo $due->format('l, d M Y'); ?> · Assigned by <?php echo htmlspecialchars($h['full_name']); ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../student-footer.php'; ?>