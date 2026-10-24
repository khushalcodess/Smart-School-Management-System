<?php
session_start();
require '../../../backend/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../login.html?error=unauthorized');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if (!$id) {
    header('Location: admin-teachers.php');
    exit;
}

// Load the current record
$stmt = $pdo->prepare(
    "SELECT users.id, users.full_name, users.email, teachers.subject,
            teachers.department, teachers.qualification, teachers.contact
     FROM users JOIN teachers ON users.id = teachers.teacher_id
     WHERE users.id = :id"
);
$stmt->execute(['id' => $id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: admin-teachers.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName      = trim($_POST['full_name'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $subject       = trim($_POST['subject'] ?? '');
    $department    = trim($_POST['department'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $contact       = trim($_POST['contact'] ?? '');
    $newPass       = $_POST['new_password'] ?? '';

    // Keep what the admin typed, so the form is not wiped if there is an error
    $teacher = array_merge($teacher, [
        'full_name' => $fullName, 'email' => $email, 'subject' => $subject,
        'department' => $department, 'qualification' => $qualification, 'contact' => $contact
    ]);

    if ($fullName === '' || $subject === '') {
        $error = 'Name and subject are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($newPass !== '' && strlen($newPass) < 6) {
        $error = 'The new password must be at least 6 characters.';
    } else {
        try {
            $pdo->beginTransaction();

            if ($newPass !== '') {
                $stmt1 = $pdo->prepare("UPDATE users SET full_name = :n, email = :e, password = :p WHERE id = :id");
                $stmt1->execute(['n' => $fullName, 'e' => $email, 'p' => password_hash($newPass, PASSWORD_DEFAULT), 'id' => $id]);
            } else {
                $stmt1 = $pdo->prepare("UPDATE users SET full_name = :n, email = :e WHERE id = :id");
                $stmt1->execute(['n' => $fullName, 'e' => $email, 'id' => $id]);
            }

            $stmt2 = $pdo->prepare(
                "UPDATE teachers SET subject = :s, department = :d, qualification = :q, contact = :c
                 WHERE teacher_id = :id"
            );
            $stmt2->execute(['s' => $subject, 'd' => $department, 'q' => $qualification, 'c' => $contact, 'id' => $id]);

            $pdo->commit();
            header('Location: admin-teachers.php?id=' . $id);
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = ($e->getCode() === '23000')
                ? 'That email address is already used by another account.'
                : 'Something went wrong while saving. Please try again.';
        }
    }
}

function initials($name) {
    $parts = explode(' ', trim($name));
    return strtoupper(($parts[0][0] ?? '') . (isset($parts[1]) ? $parts[1][0] : ''));
}
$initial = strtoupper(substr($_SESSION['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Teacher</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .edit-card{background:var(--paper); border:1px solid var(--line); border-radius:var(--radius); max-width:760px; overflow:hidden; box-shadow:0 1px 2px rgba(13,31,53,0.04);}
  .edit-body{padding:26px 30px 30px;}
  .form-grid{display:grid; grid-template-columns:1fr 1fr; gap:0 22px;}
  .form-grid .full{grid-column:1 / -1;}
  .field{margin-bottom:16px;}
  .field label{display:block; font-size:13px; font-weight:600; color:var(--navy-deep); margin-bottom:5px;}
  .field input{width:100%; padding:10px 13px; border:1px solid var(--line); border-radius:6px; font-family:inherit; font-size:14px; color:var(--ink); background:var(--paper);}
  .field input:focus{outline:none; border-color:var(--gold);}
  .hint{font-size:12px; color:var(--ink-soft); margin-top:4px;}
  .section-title{font-size:13px; font-weight:700; color:var(--navy-deep); text-transform:uppercase; letter-spacing:.04em; margin:6px 0 14px; padding-top:14px; border-top:1px solid var(--line);}
  .form-actions{display:flex; gap:12px; align-items:center; margin-top:8px;}
  .btn-save{background:var(--navy-deep); color:#fff; border:none; padding:11px 26px; border-radius:6px; font-weight:600; font-size:14px; cursor:pointer;}
  .btn-save:hover{background:var(--navy);}
  .btn-cancel{padding:10px 20px; border-radius:6px; font-weight:600; font-size:14px; background:var(--cream); color:var(--navy-deep); border:1px solid var(--line);}
  .alert-err{background:#FDECEA; border:1px solid #F5C6C3; color:#B3261E; padding:13px 18px; border-radius:var(--radius); margin-bottom:18px; font-size:14px; max-width:760px;}
  @media (max-width:700px){ .form-grid{grid-template-columns:1fr;} }
</style>
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
      <li><a href="admin-students.php">Manage Students</a></li>
      <li><a href="admin-teachers.php" class="active">Manage Teachers</a></li>
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
      <div class="crumb">
        <a href="../Overview-Pages/admin-dashboard.php">Admin</a> /
        <a href="admin-teachers.php">Manage Teachers</a> / Edit
      </div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
      </div>
    </div>

    <div class="admin-content">
      <h1>Edit Teacher</h1><br>

      <?php if ($error !== ''): ?>
        <div class="alert-err"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <div class="edit-card">
        <div class="detail-header">
          <div class="avatar-lg"><?php echo initials($teacher['full_name']); ?></div>
          <div>
            <h2><?php echo htmlspecialchars($teacher['full_name']); ?></h2>
            <p>Teacher ID: <?php echo $id; ?></p>
          </div>
        </div>

        <div class="edit-body">
          <form method="POST" autocomplete="off">

            <div class="form-grid">
              <div class="field">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($teacher['full_name']); ?>" required>
              </div>
              <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
              </div>
            </div>

            <div class="section-title">Professional Details</div>
            <div class="form-grid">
              <div class="field">
                <label>Subject</label>
                <input type="text" name="subject" value="<?php echo htmlspecialchars($teacher['subject']); ?>" required>
              </div>
              <div class="field">
                <label>Department</label>
                <input type="text" name="department" value="<?php echo htmlspecialchars($teacher['department'] ?? ''); ?>">
              </div>
              <div class="field">
                <label>Qualification</label>
                <input type="text" name="qualification" value="<?php echo htmlspecialchars($teacher['qualification'] ?? ''); ?>">
              </div>
              <div class="field">
                <label>Contact</label>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($teacher['contact'] ?? ''); ?>">
              </div>
            </div>

            <div class="section-title">Login</div>
            <div class="form-grid">
              <div class="field full">
                <label>New Password (optional)</label>
                <input type="password" name="new_password" autocomplete="new-password" placeholder="Leave blank to keep the current password">
                <div class="hint">Enter a new password only if you want to reset it (minimum 6 characters).</div>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save">Save Changes</button>
              <a href="admin-teachers.php?id=<?php echo $id; ?>" class="btn-cancel">Cancel</a>
            </div>

          </form>
        </div>
      </div>

    </div>
  </div>

</div>
</body>
</html>