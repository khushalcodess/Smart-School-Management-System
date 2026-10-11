<?php
// Shared Teacher sidebar. Set $activePage (e.g. 'timetable') BEFORE including this file.
$activePage = $activePage ?? '';
$on = function ($key) use ($activePage) {
    return $key === $activePage ? ' class="active"' : '';
};
?>
<aside class="admin-sidebar">
  <div class="brand"><span class="dot"></span> Vidya Bharati</div>

  <div class="nav-label">Overview</div>
  <ul>
    <li><a href="../Overview-Pages/teacher-dashboard.php"<?php echo $on('dashboard'); ?>>Dashboard</a></li>
  </ul>

  <div class="nav-label">Academics</div>
  <ul>
    <li><a href="../Academic-Pages/teacher-attendance.php"<?php echo $on('attendance'); ?>>Mark Attendance</a></li>
    <li><a href="../Academic-Pages/teacher-attendance-records.php"<?php echo $on('records'); ?>>Attendance Records</a></li>
    <li><a href="../Academic-Pages/teacher-homework.php"<?php echo $on('homework'); ?>>Homework</a></li>
    <li><a href="../Academic-Pages/teacher-exams.php"<?php echo $on('exams'); ?>>Manage Exams</a></li>
  </ul>

  <div class="nav-label">Students</div>
  <ul>
    <li><a href="../People-Pages/teacher-students.php"<?php echo $on('students'); ?>>My Students</a></li>
  </ul>

  <div class="nav-label">School</div>
  <ul>
    <li><a href="../School-Pages/teacher-timetable.php"<?php echo $on('timetable'); ?>>My Timetable</a></li>
    <li><a href="../School-Pages/teacher-announcements.php"<?php echo $on('announcements'); ?>>Announcements</a></li>
    <li><a href="../School-Pages/teacher-notices.php"<?php echo $on('notices'); ?>>Notices</a></li>
    <li><a href="../School-Pages/teacher-events.php"<?php echo $on('events'); ?>>Events</a></li>
    <li><a href="../School-Pages/teacher-gallery.php"<?php echo $on('gallery'); ?>>Gallery</a></li>
    <li><a href="../../../backend/logout.php" class="logout">Logout</a></li>
  </ul>
</aside>