<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

// Handle Add Teacher form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $subject = trim($_POST['subject']);
    $department = trim($_POST['department']);
    $qualification = trim($_POST['qualification']);
    $contact = trim($_POST['contact']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role) VALUES (:name, :email, :password, 'teacher')"
    );
    $stmt->execute(['name' => $fullName, 'email' => $email, 'password' => $hashedPassword]);
    $newUserId = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare(
        "INSERT INTO teachers (teacher_id, subject, department, qualification, contact) VALUES (:id, :subject, :department, :qualification, :contact)"
    );
    $stmt2->execute([
        'id' => $newUserId, 'subject' => $subject, 'department' => $department,
        'qualification' => $qualification, 'contact' => $contact
    ]);

    header('Location: admin-teachers.php?id=' . $newUserId);
    exit;
}

$search = trim($_GET['q'] ?? '');
$showAddForm = isset($_GET['add']);

if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT users.id, users.full_name, teachers.subject
         FROM users JOIN teachers ON users.id = teachers.teacher_id
         WHERE users.full_name LIKE :search OR teachers.subject LIKE :search
         ORDER BY users.full_name"
    );
    $stmt->execute(['search' => '%' . $search . '%']);
    $teacherList = $stmt->fetchAll();
} else {
    $teacherList = $pdo->query(
        "SELECT users.id, users.full_name, teachers.subject
         FROM users JOIN teachers ON users.id = teachers.teacher_id
         ORDER BY users.full_name"
    )->fetchAll();
}

$selectedId = $_GET['id'] ?? ($teacherList[0]['id'] ?? null);

$selectedTeacher = null;
if ($selectedId) {
    $stmt = $pdo->prepare(
        "SELECT users.id, users.full_name, users.email, teachers.subject,
                teachers.department, teachers.qualification, teachers.contact
         FROM users JOIN teachers ON users.id = teachers.teacher_id
         WHERE users.id = :id"
    );
    $stmt->execute(['id' => $selectedId]);
    $selectedTeacher = $stmt->fetch();
}

function initials($name) {
    $parts = explode(' ', trim($name));
    $first = $parts[0][0] ?? '';
    $last = isset($parts[1]) ? $parts[1][0] : '';
    return strtoupper($first . $last);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Teachers</title>
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
      <li><a href="../People-Pages/admin-teachers.php" class="active">Manage Teachers</a></li>
    </ul>
    <div class="nav-label">Communication</div>
    <ul>
      <li><a href="../Communication-Pages/admin-announcements.php">Announcements</a></li>
      <li><a href="../Communication-Pages/admin-notices.php">Notices</a></li>
      <li><a href="../Communication-Pages/admin-events.php">Events</a></li>
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
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Manage Teachers</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'],0,1))); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Manage Teachers</h1><br>

      <div class="split-panel">

        <!-- LEFT: teacher list -->
        <div class="list-panel">
          <div class="list-panel-header">
            <h2>Teachers</h2>
            <a href="admin-teachers.php?add=1" class="add-btn">+</a>
          </div>
          <div class="search-box">
            <form method="GET">
              <input type="text" name="q" placeholder="Search by name or subject..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
          </div>
          <?php foreach ($teacherList as $t): ?>
            <a href="admin-teachers.php?id=<?php echo $t['id']; ?>" style="text-decoration:none; color:inherit;">
              <div class="student-row <?php echo ($t['id'] == $selectedId && !$showAddForm) ? 'active' : ''; ?>">
                <div class="avatar-sm"><?php echo initials($t['full_name']); ?></div>
                <div class="student-row-info">
                  <strong><?php echo htmlspecialchars($t['full_name']); ?></strong>
                  <span><?php echo htmlspecialchars($t['subject']); ?></span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
          <?php if (empty($teacherList)): ?>
            <div class="empty-state">No teachers found.</div>
          <?php endif; ?>
        </div>

        <!-- RIGHT: detail panel or add form -->
        <div class="detail-panel">
          <?php if ($showAddForm): ?>
            <div class="detail-body">
              <h3>Add New Teacher</h3>
              <form method="POST" class="admin-form" style="max-width:100%;" autocomplete="off">
                <div class="field"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" required></div>
                <div class="field"><label>Password</label><input type="password" name="password" required autocomplete="new-password"></div>
                <div class="field"><label>Subject</label><input type="text" name="subject" required></div>
                <div class="field"><label>Department</label><input type="text" name="department"></div>
                <div class="field"><label>Qualification</label><input type="text" name="qualification"></div>
                <div class="field"><label>Contact</label><input type="text" name="contact"></div>
                <button type="submit">Add Teacher</button>
              </form>
            </div>
          <?php elseif ($selectedTeacher): ?>
            <div class="detail-header">
              <div class="avatar-lg"><?php echo initials($selectedTeacher['full_name']); ?></div>
              <div>
                <h2><?php echo htmlspecialchars($selectedTeacher['full_name']); ?></h2>
                <p><?php echo htmlspecialchars($selectedTeacher['subject']); ?> &nbsp;|&nbsp; Teacher ID: <?php echo $selectedTeacher['id']; ?></p>
              </div>
            </div>
            <div class="detail-body">
              <h3>Basic Details</h3>
              <div class="detail-grid">
                <div class="detail-field"><span>Email</span><strong><?php echo htmlspecialchars($selectedTeacher['email']); ?></strong></div>
                <div class="detail-field"><span>Department</span><strong><?php echo htmlspecialchars($selectedTeacher['department'] ?: '—'); ?></strong></div>
                <div class="detail-field"><span>Qualification</span><strong><?php echo htmlspecialchars($selectedTeacher['qualification'] ?: '—'); ?></strong></div>
                <div class="detail-field"><span>Contact</span><strong><?php echo htmlspecialchars($selectedTeacher['contact'] ?: '—'); ?></strong></div>
              </div>
              <a href="admin-edit-teacher.php?id=<?php echo $selectedTeacher['id']; ?>" class="action-btn edit">Edit</a>
              <a href="admin-delete-teacher.php?id=<?php echo $selectedTeacher['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this teacher?');">Delete</a>
            </div>
          <?php else: ?>
            <div class="empty-state">No teacher selected. Click a teacher from the list, or add a new one.</div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>