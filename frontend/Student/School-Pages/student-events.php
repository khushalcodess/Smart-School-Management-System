<?php
require __DIR__ . '/../student-init.php';

$upcoming = $pdo->query(
    "SELECT title, description, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC"
)->fetchAll();
$past = $pdo->query(
    "SELECT title, event_date FROM events WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 10"
)->fetchAll();

$today = new DateTime(date('Y-m-d'));

$extraCss = <<<'CSS'
.ev-card{display:flex; gap:16px; align-items:flex-start;}
.ev-card .event-date-badge{width:56px; padding:8px 0;}
.ev-card .event-date-badge .day{font-size:20px;}
CSS;

$pageTitle = 'Events'; $activePage = 'events'; $crumb = 'Events';
require __DIR__ . '/../student-header.php';
?>

<h1>Events</h1><br>

<div style="max-width:760px;">

  <h2>Upcoming</h2>
  <?php if (empty($upcoming)): ?>
    <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); margin-bottom:24px;">No upcoming events.</div>
  <?php endif; ?>
  <?php foreach ($upcoming as $e): ?>
    <?php [$txt, $cls] = dueText($e['event_date']); $d = new DateTime($e['event_date']); ?>
    <div class="feed-card ev-card">
      <div class="event-date-badge"><span class="day"><?php echo $d->format('d'); ?></span><span class="mon"><?php echo $d->format('M'); ?></span></div>
      <div>
        <h3><?php echo htmlspecialchars($e['title']); ?></h3>
        <?php if (!empty($e['description'])): ?>
          <p class="content"><?php echo nl2br(htmlspecialchars($e['description'])); ?></p>
        <?php endif; ?>
        <span class="badge <?php echo $cls; ?>"><?php echo $txt; ?> · <?php echo $d->format('l, d M Y'); ?></span>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if (!empty($past)): ?>
    <br><h2>Past Events</h2>
    <div style="opacity:.65;">
      <?php foreach ($past as $e): ?>
        <?php $d = new DateTime($e['event_date']); ?>
        <div class="feed-card ev-card">
          <div class="event-date-badge"><span class="day"><?php echo $d->format('d'); ?></span><span class="mon"><?php echo $d->format('M'); ?></span></div>
          <div><h3><?php echo htmlspecialchars($e['title']); ?></h3><p class="sub"><?php echo $d->format('l, d M Y'); ?></p></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<?php require __DIR__ . '/../student-footer.php'; ?>