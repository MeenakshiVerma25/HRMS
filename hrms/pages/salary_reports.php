<?php 
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; 
    exit();
}

/* ── DEFAULT MONTH = PREVIOUS MONTH ── */
$today    = new DateTime();
$lastMo   = (clone $today)->modify('-1 month');

$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval($lastMo->format('n'));
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval($lastMo->format('Y'));

/* prevent future/current running month */
$selectedDate = new DateTime(sprintf('%04d-%02d-01', $sel_year, $sel_month));
$runningMonth = new DateTime(date('Y-m-01'));

if ($selectedDate >= $runningMonth) {
    $sel_month = intval($lastMo->format('n'));
    $sel_year  = intval($lastMo->format('Y'));
}

$sel_label = date('F Y', mktime(0, 0, 0, $sel_month, 1, $sel_year));

/* ── MONTH LIST: ONLY TILL LAST MONTH ── */
$months_list = [];
$cursor = (clone $runningMonth)->modify('-1 month');
for ($i = 0; $i < 24; $i++) {
    $months_list[] = [
        'month' => intval($cursor->format('n')),
        'year'  => intval($cursor->format('Y')),
        'label' => $cursor->format('F Y'),
    ];
    $cursor->modify('-1 month');
}

/* ── FETCH SAVED SALARY REPORT DATA ── */
$sql = "SELECT report_id, user_id, user_name, dept, desig,
               report_month, report_year,
               total_present, total_absent,
               net_salary, deduction, payable_salary
        FROM salary_reports
        WHERE report_month = '$sel_month'
          AND report_year = '$sel_year'
        ORDER BY user_id ASC";

$res = mysqli_query($conn, $sql);

$total_net = 0;
$total_deduction = 0;
$total_inhand = 0;

if ($res && mysqli_num_rows($res) > 0) {
    mysqli_data_seek($res, 0); // reset pointer
    while ($row = mysqli_fetch_assoc($res)) {
        $total_net += (float)$row['net_salary'];
        $total_deduction += (float)$row['deduction'];
        $total_inhand += (float)$row['payable_salary'];
    }
    mysqli_data_seek($res, 0); // reset again for table
}
?>


<div class="dashboard-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h2 class="dash-section-title">Salary Report</h2>
            <p class="dash-section-sub">Salary records for <?= htmlspecialchars($sel_label) ?></p>
        </div>

        <form method="GET" action="salary_report.php" id="monthForm" class="d-flex align-items-center" style="gap:8px;">
            <label style="font-size:0.83rem;color:var(--text-secondary);white-space:nowrap;">Select Month</label>

            <select id="monthSelect" class="form-select form-select-sm"
                    style="min-width:170px;"
                    onchange="applyMonth(this)">
                <?php 
                $current_val = $sel_year . '-' . str_pad($sel_month, 2, '0', STR_PAD_LEFT);
                foreach ($months_list as $m):
                    $val = $m['year'] . '-' . str_pad($m['month'], 2, '0', STR_PAD_LEFT);
                ?>
                    <option value="<?= $val ?>" <?= ($val === $current_val) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="hidden" name="month" id="hMonth" value="<?= $sel_month ?>">
            <input type="hidden" name="year" id="hYear" value="<?= $sel_year ?>">
        </form>
    </div>

    <div class="row g-4 mb-3">
        <div class="col-xl-4 col-md-6">
            <div class="dash-card">
                <div class="card-icon">
                    <i class="fa-solid fa-wallet"></i>
                </div>  
                <div class="card-info">
                    <div class="stat-value">₹<?= number_format($total_net, 2) ?></div>
                    <div class="stat-label">Total Net Salary</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="dash-card">
                <div class="card-icon">
                    <i class="fa-solid fa-minus-circle"></i>
                </div>  
                <div class="card-info">
                    <div class="stat-value" style="color:var(--rose-mid);">
                        ₹<?= number_format($total_deduction, 2) ?>
                    </div>
                    <div class="stat-label">Total Deduction</div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="dash-card">
                <div class="card-icon">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>  
                <div class="card-info">
                    <div class="stat-value" style="color:#2e7d52;">
                        ₹<?= number_format($total_inhand, 2) ?>
                    </div>
                    <div class="stat-label">Total In-Hand Salary</div>
                </div>
            </div>
        </div>

    </div>

    <div class="content-card">
        <div class="table-responsive">
            <table id="salaryReportTable" class="table dataTable">
                <thead>
                    <tr>
                        <th>Emp ID</th>
                        <th>Name</th>
                        <th>Dept</th>
                        <th>Desig</th>
                        <th>Total Present</th>
                        <th>Total Absent</th>
                        <th>Net Salary</th>
                        <th>Deduction</th>
                        <th>In Hand (Payable)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && mysqli_num_rows($res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res)): ?>
                            <tr style="font-size:0.875rem;">
                                <td><?= (int)$row['user_id'] ?></td>
                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                <td><?= htmlspecialchars($row['dept'] ?: '—') ?></td>
                                <td><?= htmlspecialchars($row['desig'] ?: '—') ?></td>
                                <td><?= number_format((float)$row['total_present'], 2) ?></td>
                                <td><?= number_format((float)$row['total_absent'], 2) ?></td>
                                <td>&#8377;<?= number_format((float)$row['net_salary'], 2) ?></td>
                                <td style="color:var(--rose-mid);font-weight:600;">
                                    &#8377;<?= number_format((float)$row['deduction'], 2) ?>
                                </td>
                                <td style="color:#2e7d52;font-weight:700;">
                                    &#8377;<?= number_format((float)$row['payable_salary'], 2) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include '../includes/footer.php'; ?>

<script>
    $('#salary_report').addClass('active');

    function applyMonth(sel) {
        var parts = sel.value.split('-');
        document.getElementById('hYear').value = parts[0];
        document.getElementById('hMonth').value = parseInt(parts[1], 10);
        document.getElementById('monthForm').submit();
    }

    $(document).ready(function () {
        $('#salaryReportTable').DataTable({
            pageLength: 15,
            order: [[0, 'asc']],
            language: {
                emptyTable: 'No salary records found for selected month.'
            }
        });
    });
</script>