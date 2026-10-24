<?php
require __DIR__ . '/../student-init.php';

$age = $me['dob'] ? (new DateTime($me['dob']))->diff(new DateTime())->y : null;

$pageTitle = 'My Profile'; $activePage = 'profile'; $crumb = 'My Profile';
require __DIR__ . '/../student-header.php';
?>

<h1>My Profile</h1><br>

<div class="detail-panel" style="max-width:820px;">
  <div class="detail-header">
    <div class="avatar-lg"><?php echo initialsOf($me['full_name']); ?></div>
    <div>
      <h2><?php echo htmlspecialchars($me['full_name']); ?></h2>
      <p><?php echo htmlspecialchars($classLabel); ?> &nbsp;|&nbsp; Student ID: <?php echo $studentId; ?></p>
    </div>
  </div>
  <div class="detail-body">
    <h3>Basic Details</h3>
    <div class="detail-grid">
      <div class="detail-field"><span>Email</span><strong><?php echo htmlspecialchars($me['email']); ?></strong></div>
      <div class="detail-field"><span>Class</span><strong><?php echo htmlspecialchars($myClass); ?></strong></div>
      <div class="detail-field"><span>Section</span><strong><?php echo htmlspecialchars($mySection ?: '—'); ?></strong></div>
      <div class="detail-field"><span>Roll No</span><strong><?php echo htmlspecialchars($me['roll_no'] ?: '—'); ?></strong></div>
      <div class="detail-field"><span>Date of Birth</span>
        <strong><?php echo $me['dob'] ? date('d M Y', strtotime($me['dob'])) . ' (' . $age . ' yrs)' : '—'; ?></strong></div>
      <div class="detail-field"><span>Parent Contact</span><strong><?php echo htmlspecialchars($me['parent_contact'] ?: '—'); ?></strong></div>
    </div>
    <p class="sub">To correct any of these details, please contact the school office.</p>
  </div>
</div>

<?php require __DIR__ . '/../student-footer.php'; ?>