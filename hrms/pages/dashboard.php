<?php
include '../includes/header.php';


$total_employees = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM users"
))['n'] ?? 0;

$total_depts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM departments"
))['n'] ?? 0;

$total_open_jobs = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM jobs WHERE status = 'Open'"
))['n'] ?? 0;

$total_candidates = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS n FROM candidates WHERE isdeleted = '0'"
))['n'] ?? 0;
?>

    <div class="dashboard-wrapper">
        <div class="container-fluid">

            <div class="dash-section-title">Overview</div>
            <div class="dash-section-sub">A glance at your organisation today</div>

            <div class="row g-4">

                <div class="col-xl-3 col-md-6">
                    <div class="dash-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div class="card-info">
                            <h4 class="count-up" data-target="<?= (int)$total_employees ?>">0</h4>
                            <p>Total Employees</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="dash-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div class="card-info">
                            <h4 class="count-up" data-target="<?= (int)$total_depts ?>">0</h4>
                            <p>Departments</p>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="dash-card">
                        <div class="card-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div class="card-info">
                            <h4 class="count-up" data-target="<?= (int)$total_open_jobs ?>">0</h4>
                            <p>Jobs</p>
                        </div>
                        <!-- <div class="card-trend"><span class="trend-pct trend-down">Pending</span></div> -->
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="dash-card">
                        <div class="card-icon"><i class="fa-solid fa-user-check"></i></div>
                        <div class="card-info">
                            <h4 class="count-up" data-target="<?= (int)$total_candidates ?>">0</h4>
                            <p>Candidates</p>
                        </div>
                        <!-- <div class="card-trend"><span class="trend-pct trend-down">Pending</span></div> -->
                    </div>
                </div>

            </div>
            <hr class="dash-divider mt-5">
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<script>
    $('#dashboard').addClass("active");
</script>
