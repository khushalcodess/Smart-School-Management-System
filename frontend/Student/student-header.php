<?php
// Top part of every Student page. Set these BEFORE including it:
//   $pageTitle, $activePage, $crumb   (and optionally $extraCss)
if (!isset($me)) { exit; }
$on = function ($key) use ($activePage) { return $key === $activePage ? ' class="active"' : ''; };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($pageTitle); ?> — Vidya Bharati</title>
<link rel="stylesheet" href="../../Style/admin.css">
<style>
  .badge{font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:12px; white-space:nowrap; display:inline-block; background:var(--cream); color:var(--navy-deep);}
  .badge.green{background:#E8F3EA; color:#2F6B3A;}
  .badge.red{background:#FDECEA; color:#B3261E;}
  .badge.gold{background:rgba(200,150,62,0.2); color:#8A6116;}
  .badge.navy{background:rgba(19,42,70,0.08);}
  .sub{font-size:12px; color:var(--ink-soft);}
  .note{padding:13px 18px; border-radius:var(--radius); margin-bottom:18px; font-size:14px;}
  .note.warn{background:#FDECEA; border:1px solid #F5C6C3; color:#B3261E;}
  .tabs{display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap;}
  .tab{padding:7px 16px; border-radius:20px; font-size:13.5px; font-weight:600; background:var(--paper); border:1px solid var(--line); color:var(--ink-soft);}
  .tab.active{background:var(--navy-deep); color:#fff; border-color:var(--navy-deep);}
  .pct-wrap{display:flex; align-items:center; gap:10px; min-width:150px;}
  .pct-bar{flex:1; height:8px; background:var(--cream); border-radius:4px; overflow:hidden;}
  .pct-fill{height:100%; border-radius:4px;}
  .chip-class{background:var(--cream); color:var(--navy-deep); font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:12px;}
  <?php echo $extraCss ?? ''; ?>
</style>
</head>
<body>
<div class="admin-layout">

  <aside class="admin-sidebar">
    <div class="brand"><span class="dot"></span> Vidya Bharati</div>

    <div class="nav-label">Overview</div>
    <ul>
      <li><a href="../Overview-Pages/student-dashboard.php"<?php echo $on('dashboard'); ?>>Dashboard</a></li>
      <li><a href="../Overview-Pages/student-profile.php"<?php echo $on('profile'); ?>>My Profile</a></li>
    </ul>

    <div class="nav-label">Academics</div>
    <ul>
      <li><a href="../Academic-Pages/student-attendance.php"<?php echo $on('attendance'); ?>>My Attendance</a></li>
      <li><a href="../Academic-Pages/student-homework.php"<?php echo $on('homework'); ?>>Homework</a></li>
      <li><a href="../Academic-Pages/student-exams.php"<?php echo $on('exams'); ?>>Exams &amp; Results</a></li>
    </ul>

    <div class="nav-label">School</div>
    <ul>
      <li><a href="../School-Pages/student-timetable.php"<?php echo $on('timetable'); ?>>My Timetable</a></li>
      <li><a href="../School-Pages/student-announcements.php"<?php echo $on('announcements'); ?>>Announcements</a></li>
      <li><a href="../School-Pages/student-notices.php"<?php echo $on('notices'); ?>>Notices</a></li>
      <li><a href="../School-Pages/student-events.php"<?php echo $on('events'); ?>>Events</a></li>
      <li><a href="../School-Pages/student-gallery.php"<?php echo $on('gallery'); ?>>Gallery</a></li>
      <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
    </ul>
  </aside>

  <div class="admin-main">
    <div class="admin-topbar">
      <div class="crumb"><a href="../Overview-Pages/student-dashboard.php">Student</a> / <?php echo htmlspecialchars($crumb); ?></div>
      <div class="user-chip">
        <div class="avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
        <span class="chip-class"><?php echo htmlspecialchars($classLabel); ?></span>
      </div>
    </div>
    
    <div class="admin-content student-content" style="padding:32px 48px 60px;">