<?php
session_start();
require 'backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.html?error=unauthorized');
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
</head>
<body>

  <a href="admin-dashboard.php">&larr; Back to Dashboard</a>
  <h1>Events</h1>

  <h2>Add New Event</h2>
  <form method="POST">
    <input type="text" name="title" placeholder="Title" required><br>
    <textarea name="description" placeholder="Description"></textarea><br>
    <label>Event Date: <input type="date" name="event_date" required></label><br>
    <button type="submit">Add Event</button>
  </form>

  <h2>All Events</h2>
  <table border="1" cellpadding="6">
    <tr>
      <th>Title</th><th>Description</th><th>Event Date</th><th>Posted By</th><th>Actions</th>
    </tr>
    <?php foreach ($events as $e): ?>
    <tr>
      <td><?php echo htmlspecialchars($e['title']); ?></td>
      <td><?php echo htmlspecialchars($e['description']); ?></td>
      <td><?php echo htmlspecialchars($e['event_date']); ?></td>
      <td><?php echo htmlspecialchars($e['full_name']); ?></td>
      <td>
        <a href="admin-delete-event.php?id=<?php echo $e['id']; ?>" onclick="return confirm('Delete this event?');">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

</body>
</html>