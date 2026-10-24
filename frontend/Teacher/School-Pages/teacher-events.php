<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$upcoming = $pdo->query(
    "SELECT title, description, event_date FROM events
     WHERE event_date >= CURDATE() ORDER BY event_date ASC"
)->fetchAll();

$past = $pdo->query(
    "SELECT title, description, event_date FROM events
     WHERE event_date < CURDATE() ORDER BY event_date DESC LIMIT 10"
)->fetchAll();

$today = new DateTime(date('Y-m-d'));

$activePage = 'events';
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Events</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .ev-card{display:flex; gap:16px; align-items:flex-start;}
  .ev-card .event-date-badge{width:56px; padding:8px 0;}
  .ev-card .event-date-badge .day{font-size:20px;}
  .when{display:inline-block; font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:12px; background:rgba(200,150,62,0.2); color:#8A6116; margin-top:6px;}
  .past-section{opacity:.65;}
</style>
</head>
<body>
<div class="admin-layout">

  <?php include __DIR__ . '/../teacher-sidebar.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/teacher-dashboard.php">Teacher</a> / Events</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Events</h1><br>

      <div style="max-width:760px;">

        <h2>Upcoming</h2>
        <?php if (empty($upcoming)): ?>
          <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); margin-bottom:24px;">
            No upcoming events.
          </div>
        <?php endif; ?>

        <?php foreach ($upcoming as $e): ?>
          <?php
            $d = new DateTime($e['event_date']);
            $left = (int) $today->diff($d)->format('%a');
            $whenText = $left === 0 ? 'Today' : ($left === 1 ? 'Tomorrow' : 'In ' . $left . ' days');
          ?>
          <div class="feed-card ev-card">
            <div class="event-date-badge">
              <span class="day"><?php echo $d->format('d'); ?></span>
              <span class="mon"><?php echo $d->format('M'); ?></span>
            </div>
            <div>
              <h3><?php echo htmlspecialchars($e['title']); ?></h3>
              <?php if (!empty($e['description'])): ?>
                <p class="content"><?php echo nl2br(htmlspecialchars($e['description'])); ?></p>
              <?php endif; ?>
              <span class="when"><?php echo $whenText; ?> · <?php echo $d->format('l, d M Y'); ?></span>
            </div>
          </div>
        <?php endforeach; ?>

        <?php if (!empty($past)): ?>
          <br><h2>Past Events</h2>
          <div class="past-section">
            <?php foreach ($past as $e): ?>
              <?php $d = new DateTime($e['event_date']); ?>
              <div class="feed-card ev-card">
                <div class="event-date-badge">
                  <span class="day"><?php echo $d->format('d'); ?></span>
                  <span class="mon"><?php echo $d->format('M'); ?></span>
                </div>
                <div>
                  <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                  <p style="font-size:12.5px; color:var(--ink-soft);"><?php echo $d->format('l, d M Y'); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>

    </div>
  </div>

</div>
</body>
</html>