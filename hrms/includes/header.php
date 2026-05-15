<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$role = $_SESSION['user_role'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);

$page_names = [
    'dashboard.php' => 'Dashboard',
    'employees.php' => 'Employees',
    'departments.php' => 'Departments',
    'designations.php' => 'Designations',
    'location.php' => 'Locations',
    'jobs.php' => 'Jobs',
    'candidates.php' => 'Candidates',
    'show_users.php' => 'Permissions',
    'interviews.php' => 'Interviews',
    'offers.php'          => 'Offers',
    'onboarding.php'      => 'Onboarding',
    'onboarding_form.php' => 'Joining Form',
    'onboarding_docs.php' => 'Documents & Checklist',
    'salary_master.php'   => 'Salary Master',
    'shifts_holidays.php' => 'Shifts & Holidays',
    'attendance.php'      => 'Attendance',
    'leave_approval.php' => 'Leave Approval',
    'payroll.php'         => 'Payroll',
    'payroll_run.php'     => 'Payroll Run',
    'loan.php'            => 'Loans',
    'salary_reports.php'  => 'Salary Reports',
    'resignation.php'     => 'Resignation',

];

$current_page_name = $page_names[$current_page] ?? 'HRMS';
?>

<?php
$master_pages = ['departments.php', 'designation.php', 'location.php'];
$is_master_active = in_array($current_page, $master_pages);
$is_recruit_active = in_array($current_page, ['jobs.php', 'candidates.php', 'interviews.php', 'offers.php', 'onboarding.php', 'onboarding_form.php', 'onboarding_docs.php']);

$is_reports_active = in_array($current_page, ['salary_reports.php']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($current_page_name) ?> — HRMS</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <!-- DataTables Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond&family=DM+Sans&display=swap">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/content.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        (function() {
            const t = localStorage.getItem('hrms-theme') || 'dark';
            document.documentElement.classList.add('theme-' + t);
        })();
    </script>
    
    <style>
        .profile-img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--border-hi);
        }
    </style>

</head>
<body class="theme-dark">

    <section id="menu">
        <div class="logo">
            <img src="../images/hrms_logo.png" alt="HRMS Logo">
            <h2>HRMS</h2>
        </div>

        <ul class="items">
            <?php if(in_array($role, ['Super_admin', 'HR_admin', 'Manager'])): ?>
                <li id="dashboard">
                    <a href="../pages/dashboard.php"><i class="fa-solid fa-gauge"></i><span>Dashboard</span></a>
                </li>
                <li id="jobs">
                    <a href="../pages/jobs.php"><i class="fa-solid fa-clipboard-list"></i><span>Jobs</span></a>
                </li>
                <li id="candidates">
                    <a href="../pages/candidates.php"><i class="fa-solid fa-users"></i><span>Candidates</span></a>
                </li>
                <li id="interviews">
                    <a href="../pages/interviews.php"><i class="fa-solid fa-calendar-check"></i><span>Interviews</span></a>
                </li>
                <li id="offers">
                    <a href="../pages/offers.php"><i class="fa-solid fa-file-alt"></i><span>Offers</span></a>
                </li>
            <?php endif; ?>

            <?php if(in_array($role, ['Super_admin', 'HR_admin'])): ?>
                <li id="onboarding">
                    <a href="../pages/onboarding.php"><i class="fa-solid fa-user-plus"></i><span>Onboarding</span></a>
                </li>

                <li id="salary_master">
                    <a href="../pages/salary_master.php"><i class="fa-solid fa-money-bill-wave"></i><span>Salary Master</span></a>
                </li>

                <li id="shifts_holidays">
                    <a href="../pages/shifts_holidays.php"><i class="fa-solid fa-clock"></i><span>Shifts & Holidays</span></a>
                </li>

                <li class="has-submenu <?= $is_master_active ? 'open' : '' ?>">
                    <a href="javascript:void(0)">
                        <i class="fa-solid fa-cogs"></i>
                        <span>Master</span>
                        <i class="fa fa-chevron-down ms-auto"></i>
                    </a>

                    <ul class="submenu <?= $is_master_active ? 'show' : '' ?>">
                        <li id="department">
                            <a href="../pages/departments.php"><i class="fa-solid fa-building"></i><span>Departments</span></a>
                        </li>
                        <li id="designation">
                            <a href="../pages/designation.php"><i class="fa-solid fa-briefcase"></i><span>Designations</span></a>
                        </li>
                        <li id="location">
                            <a href="../pages/location.php"><i class="fa-solid fa-map-marker-alt"></i><span>Location</span></a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if(in_array($role, ['Super_admin', 'HR_admin', 'Manager'])): ?>

                <li  id="attendance">
                    <a href="../pages/attendance.php"><i class="fa-solid fa-user-tie"></i><span>Attendance</span></a>
                </li>

                <li  id="leave">
                    <a href="../pages/leave_approval.php"><i class="fa-solid fa-calendar-alt"></i><span>Leave</span></a>
                </li>

                <li id="payroll">
                    <a href="../pages/payroll.php"><i class="fa-solid fa-file-invoice-dollar"></i><span>Payroll</span></a>
                </li>

                <li id="loan">
                    <a href="../pages/loan.php"><i class="fa-solid fa-hand-holding-dollar"></i><span>Loans</span></a>
                </li>

                <li class="has-submenu <?= $is_master_active ? 'open' : '' ?>">
                    <a href="javascript:void(0)">
                        <i class="fa-solid fa-cogs"></i>
                        <span>Reports</span>
                        <i class="fa fa-chevron-down ms-auto"></i>
                    </a>

                    <ul class="submenu <?= $is_reports_active ? 'show' : '' ?>">
                        <li id="salary_report">
                            <a href="../pages/salary_reports.php"><i class="fa-solid fa-file-alt"></i><span>Salary Report</span></a>
                        </li>
                    </ul>
                </li>

                <li id="resignation">
                    <a href="../pages/resignation.php"><i class="fa-solid fa-sign-out-alt"></i><span>Resignation</span></a>
                </li>

            <?php endif; ?>

            <?php if ( $role == 'Super_admin' ): ?>
                <li id="permissions">
                    <a href="../pages/show_users.php"><i class="fa fa-user-shield"></i></i><span>Permissions</span></a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="logout">
            <a href="../logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </div>

    </section>

    <!-- Add User Modal -->

    <div id="interface">
        <div class="navigation">
            <div class="n1">
                <div><i id="menu-btn" class="fas fa-bars"></i></div>
                <div class="nav-page-name">
                    <span><?= htmlspecialchars($current_page_name) ?></span>
                </div>
            </div>
            <div class="profile">
                <div class="theme-toggle" id="themeToggle" title="Switch Theme">
                    <div class="tt-pill"></div>
                    <div class="tt-option" data-theme="dark">
                        <i class="fa-solid fa-moon"></i>
                        <span>Dark</span>
                    </div>
                    <div class="tt-option" data-theme="light">
                        <i class="fa-solid fa-sun"></i>
                        <span>Light</span>
                    </div>
                </div>
                
                <i class="far fa-bell"></i>
                <span class="fw-bold me-2"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <small class="text-muted">(<?= htmlspecialchars($role) ?>)</small>
            </div>
        </div>
