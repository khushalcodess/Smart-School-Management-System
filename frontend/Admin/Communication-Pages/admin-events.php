<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $eventDate = $_POST['event_date'];

    $stmt = $pdo->prepare(
        "INSERT INTO events (title, description, event_date, posted_by) VALUES (:title, :description, :event_date, :posted_by)"
    );
    $stmt->execute([
        'title' => $title,
        'description' => $description,
        'event_date' => $eventDate,
        'posted_by' => $_SESSION['user_id']
    ]);

    header('Location: admin-events.php');
    exit;
}

$events = $pdo->query(
    "SELECT events.id, events.title, events.description, events.event_date, users.full_name
     FROM events
     JOIN users ON events.posted_by = users.id
     ORDER BY events.event_date ASC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Events</title>
<link rel="stylesheet" href="../../Style/admin.css">
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
      <li><a href="admin-announcements.php">Announcements</a></li>
      <li><a href="admin-notices.php">Notices</a></li>
      <li><a href="admin-events.php" class="active">Events</a></li>
    </ul>
    <div class="nav-label">School</div>
    <ul>
      <li><a href="../School-Pages/admin-timetable.php">Timetable</a></li>
      <li><a href="../School-Pages/admin-gallery.php">Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Events</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'],0,1))); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Events</h1><br>

      <div class="feed-layout">

        <div class="admin-form" style="margin-bottom:0;">
          <h3 style="margin-bottom:16px;">Add New Event</h3>
          <form method="POST">
            <div class="field">
              <label>Title</label>
              <input type="text" name="title" required>
            </div>
            <div class="field">
              <label>Description</label>
              <textarea name="description"></textarea>
            </div>
            <div class="field">
              <label>Event Date</label>
              <input type="date" name="event_date" required>
            </div>
            <button type="submit">Add Event</button>
          </form>
        </div>

        <div>
          <?php if (empty($events)): ?>
            <div class="empty-state" style="background:var(--paper); border:1px solid var(--line); border-radius:var(--radius);">
              No events added yet.
            </div>
          <?php endif; ?>

          <?php foreach ($events as $e): ?>
            <?php $d = new DateTime($e['event_date']); ?>
            <div class="feed-card">
              <h3><?php echo htmlspecialchars($e['title']); ?></h3>
              <p class="content"><?php echo nl2br(htmlspecialchars($e['description'])); ?></p>
              <div class="feed-meta">
                <span><?php echo $d->format('d M Y'); ?> · Posted by <?php echo htmlspecialchars($e['full_name']); ?></span>
                <a href="admin-delete-event.php?id=<?php echo $e['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this event?');">Delete</a>
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