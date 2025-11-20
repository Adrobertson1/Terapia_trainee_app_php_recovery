<div class="sidebar-banner">
  <h3>Dashboard Menu</h3>
  <div class="menu-section">

    <!-- Top section: Dashboard + Calendar -->
    <div class="sidebar-section">
      <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="calendar.php"><i class="fas fa-calendar-alt"></i> My Calendar</a>
    </div>

    <?php if (in_array($_SESSION['role'], ['admin', 'superuser'])): ?>
      <div class="sidebar-section">
        <h4>Personnel</h4>
        <a href="trainees.php"><i class="fas fa-users"></i> Manage Trainees</a>
        <a href="tutors.php"><i class="fas fa-chalkboard-teacher"></i> Manage Tutors</a>
        <a href="staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a>
        <a href="supervisors.php"><i class="fas fa-user-shield"></i> Manage Supervisors</a>
        <a href="manage_admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Courses</h4>
        <a href="course_dashboard.php"><i class="fas fa-chalkboard"></i> Course Dashboard</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Assignments</h4>
        <a href="assign_to_trainee.php"><i class="fas fa-plus-circle"></i> Assign to Trainee</a>
        <a href="view_all_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a>
        <a href="assignment_submissions.php"><i class="fas fa-marker"></i> Grade Submissions</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Supervision</h4>
        <a href="supervisor_allocations.php"><i class="fas fa-user-tag"></i> Supervisor/Trainee Allocation</a>
        <a href="supervision_groups.php"><i class="fas fa-users-cog"></i> Supervision Groups</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Safeguarding</h4>
        <a href="add_safeguarding.php"><i class="fas fa-shield-alt"></i> Log Safeguarding</a>
        <a href="alerts_dashboard.php"><i class="fas fa-exclamation-triangle"></i> Unresolved Alerts</a>
        <a href="safeguarding_summary.php"><i class="fas fa-chart-bar"></i> Safeguarding Summary</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Reports</h4>
        <a href="generate_reports.php"><i class="fas fa-file-alt"></i> Generate Reports</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Account</h4>
        <a href="edit_staff.php?staff_id=<?= $_SESSION['user_id'] ?>"><i class="fas fa-user-cog"></i> Edit My Account</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <hr>
      </div>

    <?php elseif ($_SESSION['role'] === 'staff' || $_SESSION['role'] === 'supervisor'): ?>
      <div class="sidebar-section">
        <h4>Personnel</h4>
        <a href="trainees.php"><i class="fas fa-users"></i> Manage Trainees</a>
        <a href="tutors.php"><i class="fas fa-chalkboard-teacher"></i> Manage Tutors</a>
        <a href="staff.php"><i class="fas fa-user-tie"></i> Manage Staff</a>
        <a href="supervisors.php"><i class="fas fa-user-shield"></i> Manage Supervisors</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Courses</h4>
        <a href="courses.php"><i class="fas fa-book"></i> Manage Courses</a>
        <a href="course_dashboard.php"><i class="fas fa-chalkboard"></i> Course Dashboard</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Assignments</h4>
        <a href="assign_to_trainee.php"><i class="fas fa-plus-circle"></i> Assign to Trainee</a>
        <a href="view_all_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a>
        <a href="assignment_submissions.php"><i class="fas fa-marker"></i> Grade Submissions</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Supervision</h4>
        <a href="supervisor_allocations.php"><i class="fas fa-user-tag"></i> Supervisor/Trainee Allocation</a>
        <a href="supervision_groups.php"><i class="fas fa-users-cog"></i> Supervision Groups</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <a href="add_event.php"><i class="fas fa-plus-circle"></i> Add Calendar Event</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Safeguarding</h4>
        <a href="add_safeguarding.php"><i class="fas fa-shield-alt"></i> Log Safeguarding</a>
        <a href="alerts_dashboard.php"><i class="fas fa-exclamation-triangle"></i> Unresolved Alerts</a>
        <a href="safeguarding_summary.php"><i class="fas fa-chart-bar"></i> Summary Dashboard</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Reports</h4>
        <a href="generate_reports.php"><i class="fas fa-file-alt"></i> Generate Reports</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Account</h4>
        <a href="edit_staff.php?staff_id=<?= $_SESSION['user_id'] ?>"><i class="fas fa-user-cog"></i> Edit My Account</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        <hr>
      </div>

    <?php elseif ($_SESSION['role'] === 'tutor'): ?>
      <div class="sidebar-section">
        <h4>Assignments</h4>
        <a href="assign_to_trainee.php"><i class="fas fa-plus-circle"></i> Assign to Trainee</a>
        <a href="view_all_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a>
        <a href="assignment_submissions.php"><i class="fas fa-marker"></i> Grade Submissions</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Supervision</h4>
        <a href="supervisor_allocations.php"><i class="fas fa-user-tag"></i> Supervisor/Trainee Allocation</a>
        <a href="supervision_groups.php"><i class="fas fa-users-cog"></i> View Supervision Groups</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <a href="add_event.php"><i class="fas fa-plus-circle"></i> Add Calendar Event</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Safeguarding</h4>
        <a href="add_safeguarding.php"><i class="fas fa-shield-alt"></i> Log Safeguarding</a>
        <hr>
      </div>

    <?php elseif ($_SESSION['role'] === 'trainee'): ?>
      <div class="sidebar-section">
        <h4>Assignments</h4>
        <a href="assignments.php"><i class="fas fa-file-alt"></i> My Assignments</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <a href="courses.php"><i class="fas fa-book-reader"></i> My Courses</a>
        <hr>
      </div>

      <div class="sidebar-section">
        <h4>Supervision</h4>
        <a href="supervision_groups.php"><i class="fas fa-users-cog"></i> My Supervision Groups</a>
<a href="view_individual_trainee_supervisor.php"><i class="fas fa-user-tag"></i> My Individual Supervisor</a>
<hr>
</div>

<div class="sidebar-section">
  <h4>Safeguarding</h4>
  <a href="add_safeguarding.php"><i class="fas fa-shield-alt"></i> Log Safeguarding</a>
  <hr>
</div>
<?php endif; ?>
</div>
</div>