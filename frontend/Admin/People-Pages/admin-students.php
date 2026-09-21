<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

// Handle Add Student form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $class = trim($_POST['class']);
    $section = trim($_POST['section']);
    $rollNo = $_POST['roll_no'];
    $dob = $_POST['dob'];
    $parentContact = trim($_POST['parent_contact']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password, role) VALUES (:name, :email, :password, 'student')"
    );
    $stmt->execute(['name' => $fullName, 'email' => $email, 'password' => $hashedPassword]);
    $newUserId = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare(
        "INSERT INTO students (student_id, class, section, roll_no, dob, parent_contact) VALUES (:id, :class, :section, :roll_no, :dob, :parent_contact)"
    );
    $stmt2->execute([
        'id' => $newUserId, 'class' => $class, 'section' => $section,
        'roll_no' => $rollNo, 'dob' => $dob ?: null, 'parent_contact' => $parentContact
    ]);

    header('Location: admin-students.php?id=' . $newUserId);
    exit;
}

$search = trim($_GET['q'] ?? '');
$showAddForm = isset($_GET['add']);

// Fetch list (filtered by search if provided)
if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT users.id, users.full_name, students.class, students.section
         FROM users JOIN students ON users.id = students.student_id
         WHERE users.full_name LIKE :search OR students.class LIKE :search
         ORDER BY users.full_name"
    );
    $stmt->execute(['search' => '%' . $search . '%']);
    $studentList = $stmt->fetchAll();
} else {
    $studentList = $pdo->query(
        "SELECT users.id, users.full_name, students.class, students.section
         FROM users JOIN students ON users.id = students.student_id
         ORDER BY users.full_name"
    )->fetchAll();
}

// Decide which student is selected
$selectedId = $_GET['id'] ?? ($studentList[0]['id'] ?? null);

$selectedStudent = null;
if ($selectedId) {
    $stmt = $pdo->prepare(
        "SELECT users.id, users.full_name, users.email, students.class, students.section,
                students.roll_no, students.dob, students.parent_contact
         FROM users JOIN students ON users.id = students.student_id
         WHERE users.id = :id"
    );
    $stmt->execute(['id' => $selectedId]);
    $selectedStudent = $stmt->fetch();
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
<title>Manage Students</title>
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
      <li><a href="../People-Pages/admin-students.php" class="active">Manage Students</a></li>
      <li><a href="../People-Pages/admin-teachers.php">Manage Teachers</a></li>
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
      <div class="crumb"><a href="../Overview-Pages/admin-dashboard.php">Admin</a> / Manage Students</div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['full_name'],0,1))); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Manage Students</h1><br>

      <div class="split-panel">

        <!-- LEFT: student list -->
        <div class="list-panel">
          <div class="list-panel-header">
            <h2>Students</h2>
            <a href="admin-students.php?add=1" class="add-btn">+</a>
          </div>
          <div class="search-box">
            <form method="GET">
              <input type="text" name="q" placeholder="Search by name or class..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
          </div>
          <?php foreach ($studentList as $s): ?>
            <a href="admin-students.php?id=<?php echo $s['id']; ?>" style="text-decoration:none; color:inherit;">
              <div class="student-row <?php echo ($s['id'] == $selectedId && !$showAddForm) ? 'active' : ''; ?>">
                <div class="avatar-sm"><?php echo initials($s['full_name']); ?></div>
                <div class="student-row-info">
                  <strong><?php echo htmlspecialchars($s['full_name']); ?></strong>
                  <span>Class <?php echo htmlspecialchars($s['class']); ?><?php echo $s['section'] ? '-' . htmlspecialchars($s['section']) : ''; ?></span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
          <?php if (empty($studentList)): ?>
            <div class="empty-state">No students found.</div>
          <?php endif; ?>
        </div>

        <!-- RIGHT: detail panel or add form -->
        <div class="detail-panel">
          <?php if ($showAddForm): ?>
            <div class="detail-body">
              <h3>Add New Student</h3>
              <form method="POST" class="admin-form" style="max-width:100%;" autocomplete="off">
                <div class="field"><label>Full Name</label><input type="text" name="full_name" required></div>
                <div class="field"><label>Email</label><input type="email" name="email" required></div>
                <div class="field"><label>Password</label><input type="password" name="password" required autocomplete="new-password"></div>
                <div class="field"><label>Class</label><input type="text" name="class" required></div>
                <div class="field"><label>Section</label><input type="text" name="section"></div>
                <div class="field"><label>Roll No</label><input type="number" name="roll_no"></div>
                <div class="field"><label>Date of Birth</label><input type="date" name="dob"></div>
                <div class="field"><label>Parent Contact</label><input type="text" name="parent_contact"></div>
                <button type="submit">Add Student</button>
              </form>
            </div>
          <?php elseif ($selectedStudent): ?>
            <div class="detail-header">
              <div class="avatar-lg"><?php echo initials($selectedStudent['full_name']); ?></div>
              <div>
                <h2><?php echo htmlspecialchars($selectedStudent['full_name']); ?></h2>
                <p>Class <?php echo htmlspecialchars($selectedStudent['class']); ?><?php echo $selectedStudent['section'] ? '-' . htmlspecialchars($selectedStudent['section']) : ''; ?> &nbsp;|&nbsp; Student ID: <?php echo $selectedStudent['id']; ?></p>
              </div>
            </div>
            <div class="detail-body">
              <h3>Basic Details</h3>
              <div class="detail-grid">
                <div class="detail-field"><span>Email</span><strong><?php echo htmlspecialchars($selectedStudent['email']); ?></strong></div>
                <div class="detail-field"><span>Roll No</span><strong><?php echo htmlspecialchars($selectedStudent['roll_no'] ?: '—'); ?></strong></div>
                <div class="detail-field"><span>Date of Birth</span><strong><?php echo htmlspecialchars($selectedStudent['dob'] ?: '—'); ?></strong></div>
                <div class="detail-field"><span>Parent Contact</span><strong><?php echo htmlspecialchars($selectedStudent['parent_contact'] ?: '—'); ?></strong></div>
              </div>
              <a href="admin-edit-student.php?id=<?php echo $selectedStudent['id']; ?>" class="action-btn edit">Edit</a>
              <a href="admin-delete-student.php?id=<?php echo $selectedStudent['id']; ?>" class="action-btn delete" onclick="return confirm('Delete this student?');">Delete</a>
            </div>
          <?php else: ?>
            <div class="empty-state">No student selected. Click a student from the list, or add a new one.</div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

</div>
</body>
</html>
