


<?php 
include '../includes/header.php';

if (!isset($_GET['user_id'])) {
    header("Location: attendance.php");
    exit();
}

$user_id = intval($_GET['user_id']);

$month = date('m');
$year = date('Y');

$sql = "SELECT * FROM attendance 
        WHERE user_id='$user_id'
        AND MONTH(attendance_date)='$month'
        AND YEAR(attendance_date)='$year'
        ORDER BY attendance_date ASC";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
    echo "<script>
        window.location.href = 'attendance.php';
    </script>";
    exit();
}

// summary variables
$present = 0;
$absent = 0;
$halfday = 0;
$total_hours = 0;
$total_ot = 0;
$late_days = 0;
$early_days = 0;
$total_in = 0;
$total_out = 0;
$days = 0;

?>

<style>
    .status-P {
        color: green;
    }
    .status-A {
        color: red;
    }
    .status-H {
        color: orange;
    }
</style>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h2 class="dash-section-title">Attendance Report</h2>
            <p class="dash-section-sub">View attendance records</p>
        </div>
        <button type="button" class="btn-rose" onclick="window.history.back()">
            <i class="fa fa-arrow-left me-2"></i>Back
        </button>
    </div>
    <!-- <p class="dash-section-title">Employee Id: <?= $user_id ?></p> -->
    <div class="content-card dataTables_wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <p class="dash-section-title">Employee Id: <?= $user_id ?></p>
            <button type="button" class="btn-rose" onclick="toggleView()" id="toggleBtn">
                Shift View
            </button>
        </div>
        <div class="table-responsive" id="tableContainer">
                <table id="reportTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>In Time</th>
                            <th>Out Time</th>
                            <th>Work Hours</th>
                            <th>Late Minutes</th>
                            <th>Early Out Minutes</th>
                            <th>OT Hours</th>
                            <th>Is Late</th>
                            <th>Is Out Early</th>
                            <th>Is OT</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
<?php 

$start_date = date("$year-$month-01");
$end_date = date("$year-$month-t");

$attendance_data = [];

$holidays = [];
$holiday_sql = "SELECT holiday_date FROM holidays";
$holiday_result = mysqli_query($conn, $holiday_sql);
while ($h = mysqli_fetch_assoc($holiday_result)) {
    $holidays[] = $h['holiday_date'];
}

while ($row = mysqli_fetch_assoc($result)) {
    $attendance_data[$row['attendance_date']] = $row;
}

for ($date = strtotime($start_date); $date <= strtotime($end_date); $date = strtotime('+1 day', $date)) {

    $current_date = date('Y-m-d', $date);
    $day_name = date("l", $date);
    // $day_number = date("j", $date);

    $is_sunday = ($day_name == "Sunday");
    $is_holiday = in_array($current_date, $holidays);

    if ($is_sunday || $is_holiday) {
        continue; // Skip Sundays and holidays
    }

    $days++;

    $display_in = '-';
    $display_out = '-';

    if (isset($attendance_data[$current_date])) {
        $row = $attendance_data[$current_date];

        $display_in = !empty($row['in_time']) ? date('h:i A', strtotime($row['in_time'])) : '-';
        $display_out = !empty($row['out_time']) ? date('h:i A', strtotime($row['out_time'])) : '-';

        $in = strtotime($row['in_time']);
        $out = strtotime($row['out_time']);

        // Night shift handling
        if ($in && $out && $out < $in) {
            $out = strtotime('+1 day', $out);
        }

        if ($in && $out) {
            $work_hours = ($out - $in) / 3600;
            $total_hours += $work_hours;
        } else {
            $work_hours = 0;
        }
        
        // company rules: 9am-6pm shift
        $shift_start = strtotime("09:00:00");
        $shift_end   = strtotime("18:00:00");

        $late_min = ($in > $shift_start) ? round(($in - $shift_start) / 60) : 0;
        $early_min = ($out < $shift_end) ? round(($shift_end - $out) / 60) : 0;

        if($late_min > 0) $late_days++;
        if($early_min > 0) $early_days++;

        $ot = ($work_hours > 9.5) ? round($work_hours - 9.5, 2) : 0;
        $total_ot += $ot;

        $is_late = $late_min >0 ? "Yes ($late_min min)" : "No";
        $is_early = $early_min >0 ? "Yes ($early_min min)" : "No";
        $is_ot = $ot >0 ? "Yes ($ot hours)" : "No";
        
        // status
        if ($work_hours > 8) {
            $status = "Present";
            $present++;
        } elseif ($work_hours >= 4) {
            $status = "Half Day";
            $halfday++;
        } else {
            $status = "Absent";
            $absent++;
        }

        if (!empty($row['in_time'])) {
            $total_in += strtotime("1970-01-01 " . $row['in_time']);
        }
        if (!empty($row['out_time'])) {
            $total_out += strtotime("1970-01-01 " . $row['out_time']);
        }
    } else {
        $work_hours = 0;
        $late_min = 0;
        $early_min = 0;
        $ot = 0;
        $is_late = "No";
        $is_early = "No";
        $is_ot = "No";
        $status = "Absent";
        $absent++;
    }
?>
            <tr style="font-size: 14px;">
                <td><?= date('d', strtotime($current_date)) ?></td>
                <td><?= $display_in ?></td>
                <td><?= $display_out ?></td>
                <td><?= sprintf("%d hr %d min", floor($work_hours), ($work_hours - floor($work_hours)) * 60) ?></td>
                <td><?= $late_min ?></td>
                <td><?= $early_min ?></td>
                <td><?= $ot ?></td>
                <td><?= $is_late ?></td>
                <td><?= $is_early ?></td>
                <td><?= $is_ot ?></td>
                <td><?php if($status == "Absent") {
                    echo "<span class='status-A'>A</span>";
                } elseif ($status == "Half Day") {
                    echo "<span class='status-H'>H</span>";
                } else {
                    echo "<span class='status-P'>P</span>";
                } ?></td>
            </tr>
<?php } ?>
            </tbody>
        </table>
    </div>
</div>
    
<?php
    $avg_in = ($present + $halfday) ? date('h:i A', $total_in / ($present + $halfday)) : '-';
    $avg_out = ($present + $halfday) ? date('h:i A', $total_out / ($present + $halfday)) : '-';
?>

    <div class="content-card dataTables_wrapper">
        <div class="table-responsive">
            <table class="table dataTable">
                <thead>
                    <tr>
                        <th colspan="6" class="text-center">Monthly Summary</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Employee ID</strong></td>
                        <td><?= $user_id ?></td>
                        <td><strong>Month</strong></td>
                        <td><?= date('F') ?></td>
                        <td><strong>Year</strong></td>
                        <td><?= date('Y') ?></td>
                    </tr>

                    <tr>
                        <td><strong>Present Days</strong></td>
                        <td><?= $present ?></td>
                        <td><strong>Absent Days</strong></td>
                        <td><?= $absent ?></td>
                        <td><strong>Half Days</strong></td>
                        <td><?= $halfday ?></td>
                    </tr>

                    <tr>
                        <td><strong>Total Work Hours</strong></td>
                        <td><?= sprintf("%d hr %d min", floor($total_hours), ($total_hours - floor($total_hours)) * 60) ?></td>
                        <td><strong>Total OT Hours</strong></td>
                        <td><?= sprintf("%d hr %d min", floor($total_ot), ($total_ot - floor($total_ot)) * 60) ?></td>
                        <td><strong>Late Days</strong></td>
                        <td><?= $late_days ?></td>
                    </tr>

                    <tr>
                        <td><strong>Early Exit Days</strong></td>
                        <td><?= $early_days ?></td>
                        <td><strong>Avg In Time</strong></td>
                        <td><?= $avg_in ?></td>
                        <td><strong>Avg Out Time</strong></td>
                        <td><?= $avg_out ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
    let isShiftView = false;
    let originalTable = "";

    function toggleView() {
        let container = document.getElementById('tableContainer');

        if (!isShiftView) {
            originalTable = container.innerHTML;

            let table = document.getElementById('reportTable');
            let rows = table.rows;

            let colCount = rows[0].cells.length;
            
            let newHTML = "<table id='attendanceTable' class='table dataTable' style='font-size:12px'>";

            // ✅ FIX: Proper THEAD
            newHTML += "<thead><tr><th>Field</th>";

            for (let i = 1; i < rows.length; i++) {
                newHTML += "<th>" + rows[i].cells[0].innerText + "</th>";
            }

            newHTML += "</tr></thead>";

            // ✅ BODY
            newHTML += "<tbody>";

            for (let j = 1; j < colCount; j++) {

                let colName = rows[0].cells[j].innerText.trim();
                if(colName === "IS LATE" || colName === "IS OUT EARLY" || colName === "IS OT") {
                    continue; // skip these columns in shift view
                }

                newHTML += "<tr>";

                // header name
                newHTML += "<td><b>" + colName + "</b></td>";

                for (let i = 1; i < rows.length; i++) {
                    newHTML += "<td>" + rows[i].cells[j].innerHTML + "</td>";
                }

                newHTML += "</tr>";
            }

            // newHTML += "<tr><td><b>In Time</b></td>";
            // for (let i = 1; i < rows.length; i++) {
            //     newHTML += "<td>"+rows[i].cells[colCount-2].innerText+"</td>";
            // }
            // newHTML += "</tr>"; 
            // newHTML += "<tr><td><b>Out Time</b></td>";
            // for (let i = 1; i < rows.length; i++) {
            //     newHTML += "<td>"+rows[i].cells[colCount-1].innerText+"</td>";
            // }

            newHTML += "</tbody></table>";

            container.innerHTML = newHTML;

            $('#attendanceTable').DataTable({
                paging: true,
                searching: true,
                info: true
            });

            document.getElementById('toggleBtn').innerText = "Normal View";
            isShiftView = true;

        } else {
            container.innerHTML = originalTable;

            $('#attendanceTable').DataTable({
                paging: true,
                searching: true,
                info: true
            });

            document.getElementById('toggleBtn').innerText = "Shift View";
            isShiftView = false;
        }
    }
</script>











<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ─── GET EMPLOYEE DETAILS ─────────────────────────────────────────── */
if ($action === 'get_employee') {
    $uid = intval($_GET['user_id'] ?? 0);
    if (!$uid) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
        exit();
    }

    $sql = "SELECT u.user_id, u.user_name, u.doj,
                   d.designation_name,
                   dept.dept_name
            FROM users u
            LEFT JOIN designations d   ON u.designation_id = d.designation_id
            LEFT JOIN departments dept ON d.dept_id = dept.dept_id
            WHERE u.user_id = $uid AND u.dele_te = '0' AND u.is_left='no' 
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo json_encode([
            'success'  => true,
            'user_name'=> $row['user_name'],
            'dept'     => $row['dept_name'] ?? '',
            'desig'    => $row['designation_name'] ?? '',
            'doj'      => $row['doj'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    exit();
}

/* ─── ADD LOAN / ADVANCE ────────────────────────────────────────────── */
if ($action === 'add_loan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = intval($_POST['user_id'] ?? 0);
    $user_name    = mysqli_real_escape_string($conn, trim($_POST['user_name'] ?? ''));
    $dept         = mysqli_real_escape_string($conn, trim($_POST['dept'] ?? ''));
    $desig        = mysqli_real_escape_string($conn, trim($_POST['desig'] ?? ''));
    $doj          = mysqli_real_escape_string($conn, trim($_POST['doj'] ?? ''));
    $loan_type    = in_array($_POST['loan_type'] ?? '', ['Loan','Advance']) ? $_POST['loan_type'] : 'Loan';
    $amount       = floatval($_POST['amount'] ?? 0);
    $emi_amount   = floatval($_POST['emi_amount'] ?? 0);
    $interest_rate = floatval($_POST['interest_rate'] ?? 12);
    $start_month  = mysqli_real_escape_string($conn, trim($_POST['start_month'] ?? ''));
    $end_month    = mysqli_real_escape_string($conn, trim($_POST['end_month'] ?? ''));

    if (!$user_id || !$user_name || !$amount || !$emi_amount || !$start_month) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit();
    }

    // Format start/end to YYYY-MM-01 for DATE field
    $start_db = $start_month . '-01';
    $end_db   = !empty($end_month) ? $end_month . '-01' : 'NULL';

    $doj_db = !empty($doj) ? "'$doj'" : 'NULL';
    $end_val = !empty($end_month) ? "'" . $end_month . "-01'" : 'NULL';

    $ins = "INSERT INTO loan_advances
                (user_id, user_name, dept, desig, doj, loan_type, amount, emi_amount, interest_rate, start_month, end_month, status)
            VALUES
                ($user_id, '$user_name', '$dept', '$desig', $doj_db, '$loan_type', $amount, $emi_amount, $interest_rate, '$start_db', $end_val, 'Ongoing')";

    if (mysqli_query($conn, $ins)) {
        $new_id = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'loan_id' => $new_id, 'message' => 'Loan added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . mysqli_error($conn)]);
    }
    exit();
}

/* ─── GET LOAN DEDUCTIONS ───────────────────────────────────────────── */
if ($action === 'get_deductions') {
    $loan_id_filter = intval($_GET['loan_id'] ?? 0);
    $where = $loan_id_filter ? "WHERE ld.loan_id = $loan_id_filter" : '';

    $sql = "SELECT ld.*, la.loan_type
            FROM loan_deduction ld
            LEFT JOIN loan_advances la ON ld.loan_id = la.loan_id
            $where
            ORDER BY ld.loan_id DESC, ld.Month DESC";

    $res = mysqli_query($conn, $sql);
    $rows = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
    }
    echo json_encode(['success' => true, 'data' => $rows]);
    exit();
}

/* ─── GET SINGLE LOAN FOR PDF ───────────────────────────────────────── */
if ($action === 'get_loan') {
    $loan_id = intval($_GET['loan_id'] ?? 0);
    $sql = "SELECT * FROM loan_advances WHERE loan_id = $loan_id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Loan not found']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);


include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; 
    exit();
}

$curFirst  = new DateTime(date('Y-m-01'));
$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval($curFirst->format('n'));
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval($curFirst->format('Y'));
$filter    = $_GET['filter'] ?? 'all';

$months_list = [];
$cursor = new DateTime(date('Y-m-01'));
for ($i = 0; $i < 24; $i++) {
    $months_list[] = [
        'month' => intval($cursor->format('n')),
        'year'  => intval($cursor->format('Y')),
        'label' => $cursor->format('F Y'),
    ];
    $cursor->modify('+1 month');
}

$sel_label   = date('F Y', mktime(0, 0, 0, $sel_month, 1, $sel_year));
$selected_ym = sprintf('%04d-%02d', $sel_year, $sel_month);

$where_filter = "";
if ($filter === 'Loan')    $where_filter = " AND la.loan_type = 'Loan' ";
elseif ($filter === 'Advance') $where_filter = " AND la.loan_type = 'Advance' ";

$sql = "SELECT la.loan_id, la.user_id, la.user_name, la.dept, la.desig,
               la.doj, la.loan_type, la.amount, la.emi_amount,
               la.interest_rate, la.start_month, la.end_month, la.status
        FROM loan_advances la
        WHERE la.start_month <= '{$selected_ym}-01'
          AND (la.end_month IS NULL OR la.end_month = '' OR la.end_month >= '{$selected_ym}-01')
          $where_filter
        ORDER BY la.loan_id DESC";

$res = mysqli_query($conn, $sql);
?>

<style>
.filter-btn {
    border: 1px solid var(--border-hi);
    background: var(--bg-card);
    color: var(--text-primary);
    padding: 6px 14px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 500;
    cursor: pointer;
    transition: 0.25s ease;
}
.filter-btn:hover, .filter-btn.active {
    background: var(--rose-deep);
    color: #fff;
    border-color: var(--rose-deep);
}
.badge-loan {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600;
    background: rgba(194,99,122,0.12); color: var(--rose-mid, #c2637a);
    border: 1px solid rgba(194,99,122,0.20);
}
.badge-advance {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600;
    background: rgba(46,125,82,0.12); color: #2e7d52;
    border: 1px solid rgba(46,125,82,0.20);
}
.badge-ongoing {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600;
    background: rgba(46,125,82,0.12); color: #2e7d52;
}
.badge-completed {
    display: inline-block; padding: 4px 10px; border-radius: 20px;
    font-size: 0.72rem; font-weight: 600;
    background: rgba(120,120,120,0.12); color: var(--text-secondary);
}
.act-btn {
    width: 34px; height: 34px; border-radius: 10px;
    display: inline-flex; align-items: center; justify-content: center;
    border: 1px solid var(--icon-border);
    background: var(--icon-bg);
    color: var(--icon-color);
    text-decoration: none;
    cursor: pointer;
    transition: all .25s ease;
}
.act-btn:hover {
    transform: translateY(-2px);
    color: var(--icon-hover);
    box-shadow: 0 6px 22px rgba(194,99,122,0.25);
}
.form-control[readonly] {
    opacity: 0.72;
    cursor: not-allowed;
}
.interest-info {
    background: var(--bg-card-hi);
    border: 1px solid var(--border-hi);
    border-radius: 10px;
    padding: 8px 12px;
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 6px;
}
.interest-info strong { color: var(--rose-mid); }
.months-info {
    background: rgba(194,99,122,0.07);
    border: 1px solid rgba(194,99,122,0.18);
    border-radius: 10px;
    padding: 7px 11px;
    font-size: 0.78rem;
    color: var(--text-secondary);
    margin-top: 6px;
}
.ded-table th {
    background: rgba(194,99,122,0.10);
    color: var(--rose-mid);
    font-size: 0.73rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    padding: 9px 10px;
    border-bottom: 1px solid var(--border-hi) !important;
}
.ded-table td {
    font-size: 0.8rem;
    color: var(--text-primary);
    padding: 8px 10px;
    border-bottom: 1px solid var(--border) !important;
    white-space: nowrap;
}
.ded-table tbody tr:last-child td { border-bottom: none !important; }
.ded-table tbody tr:hover td { background: rgba(194,99,122,0.04); }
.modal-loader {
    display: flex; align-items: center; justify-content: center;
    padding: 40px 0; color: var(--text-secondary); gap: 10px;
}
.repay-box {
    width: 100%;
    padding: 10px 14px;
    background: var(--bg-card-hi);
    border: 1px solid var(--border-hi);
    border-radius: 12px;
    font-size: 0.82rem;
}
</style>


<div class="dashboard-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h2 class="dash-section-title">Loans &amp; Advances</h2>
            <p class="dash-section-sub">Manage employee loans and advances &mdash; <?= htmlspecialchars($sel_label) ?></p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#addLoanModal">
                <i class="fa fa-plus me-2"></i>Add Loan / Advance
            </button>
            <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#loanDeductionModal">
                <i class="fa fa-list me-2"></i>Loan Deduction
            </button>
        </div>
    </div>

    <div class="content-card dataTables_wrapper">
        <div class="d-flex align-items-center mb-3 flex-wrap" style="gap:12px;">
            <label for="monthYearSelect" class="mb-0" style="font-size:0.83rem;color:var(--text-secondary);">Select Month &amp; Year:</label>
            <select id="monthYearSelect" class="form-control form-control-sm" style="width:auto;" onchange="applyMonthFilter()">
                <?php foreach ($months_list as $item): ?>
                    <option value="<?= $item['year'] . '-' . str_pad($item['month'], 2, '0', STR_PAD_LEFT) ?>"
                        <?= ($item['month'] == $sel_month && $item['year'] == $sel_year) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($item['label']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="filter-btn <?= $filter==='all'     ? 'active':'' ?>" onclick="setLoanFilter('all')">All</button>
                <button type="button" class="filter-btn <?= $filter==='Loan'    ? 'active':'' ?>" onclick="setLoanFilter('Loan')">Loan</button>
                <button type="button" class="filter-btn <?= $filter==='Advance' ? 'active':'' ?>" onclick="setLoanFilter('Advance')">Advance</button>
            </div>
        </div>

        <div class="table-responsive">
            <table id="loanTable" class="table dataTable">
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>DOJ</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>EMI</th>
                        <th>Interest %</th>
                        <th>Start Month</th>
                        <th>End Month</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($res && mysqli_num_rows($res) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($res)): ?>
                        <?php
                            $typeBadge   = ($row['loan_type'] === 'Advance') ? 'badge-advance' : 'badge-loan';
                            $statusBadge = (strtolower($row['status']) === 'completed') ? 'badge-completed' : 'badge-ongoing';
                            $pdfData = htmlspecialchars(json_encode([
                                'loan_id'       => (int)$row['loan_id'],
                                'user_id'       => (int)$row['user_id'],
                                'user_name'     => $row['user_name'],
                                'dept'          => $row['dept'],
                                'desig'         => $row['desig'],
                                'doj'           => $row['doj'],
                                'loan_type'     => $row['loan_type'],
                                'amount'        => (float)$row['amount'],
                                'emi_amount'    => (float)$row['emi_amount'],
                                'interest_rate' => (float)$row['interest_rate'],
                                'start_month'   => $row['start_month'],
                                'end_month'     => $row['end_month'],
                                'status'        => $row['status'],
                            ]), ENT_QUOTES);
                        ?>
                        <tr style="font-size:0.8rem;">
                            <td><?= (int)$row['loan_id'] ?></td>
                            <td>
                                <div style="font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($row['user_name'] ?: '—') ?></div>
                                <div style="font-size:0.74rem;color:var(--text-secondary)">ID: <?= (int)$row['user_id'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['dept'] ?: '—') ?></td>
                            <td><?= htmlspecialchars($row['desig'] ?: '—') ?></td>
                            <td><?= !empty($row['doj']) ? date('d M Y', strtotime($row['doj'])) : '—' ?></td>
                            <td><span class="<?= $typeBadge ?>"><?= htmlspecialchars($row['loan_type']) ?></span></td>
                            <td>&#8377;<?= number_format((float)$row['amount'], 2) ?></td>
                            <td>&#8377;<?= number_format((float)$row['emi_amount'], 2) ?></td>
                            <td><?= number_format((float)$row['interest_rate'], 2) ?>%</td>
                            <td><?= !empty($row['start_month']) ? date('M Y', strtotime($row['start_month'])) : '—' ?></td>
                            <td><?= !empty($row['end_month'])   ? date('M Y', strtotime($row['end_month']))   : '—' ?></td>
                            <td><span class="<?= $statusBadge ?>"><?= htmlspecialchars($row['status'] ?: 'Ongoing') ?></span></td>
                            <td>
                                <button type="button" class="act-btn" title="Download PDF"
                                        onclick="downloadLoanPDF(<?= $pdfData ?>)">
                                    <i class="fa fa-file-pdf"></i>
                                </button>
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


<!-- =========================================================
     ADD LOAN / ADVANCE MODAL
     ========================================================= -->
<div class="modal fade" id="addLoanModal" tabindex="-1" aria-labelledby="addLoanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addLoanModalLabel">Add Loan / Advance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addLoanForm" autocomplete="off">
                    <input type="hidden" id="al_doj_val" name="doj">

                    <!-- Employee ID + Name + Type -->
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="al_user_id" name="user_id"
                                   placeholder="e.g. 1006" min="1000" required>
                            <div id="al_emp_feedback" style="font-size:0.74rem;min-height:18px;margin-top:4px;"></div>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="al_user_name" name="user_name"
                                   placeholder="Auto-filled on ID entry" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="al_loan_type" name="loan_type" required>
                                <option value="Loan">Loan</option>
                                <option value="Advance">Advance</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dept / Desig / DOJ -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="al_dept" name="dept"
                                   placeholder="Auto-filled" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="al_desig" name="desig"
                                   placeholder="Auto-filled" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Date of Joining</label>
                            <input type="text" class="form-control" id="al_doj_display"
                                   placeholder="Auto-filled" readonly>
                        </div>
                    </div>

                    <!-- Amount / EMI / Interest -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Amount (&#8377;) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="al_amount" name="amount"
                                   placeholder="e.g. 50000" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">EMI Amount (&#8377;) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="al_emi" name="emi_amount"
                                   placeholder="e.g. 5000" min="1" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Interest Rate (%)</label>
                            <input type="number" class="form-control" id="al_interest" name="interest_rate"
                                   value="12" min="0" max="100" step="0.01" readonly>
                            <div class="interest-info">
                                Monthly Interest: <strong id="al_monthly_interest">&#8377;0.00</strong>
                                &nbsp;|&nbsp; Annual Interest: <strong id="al_annual_interest">&#8377;0.00</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Start Month / End Month / Summary -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Month <span class="text-danger">*</span></label>
                            <input type="month" class="form-control" id="al_start_month" name="start_month" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Month (auto-calculated)</label>
                            <input type="month" class="form-control" id="al_end_month" name="end_month" readonly>
                            <div class="months-info" id="al_months_info" style="display:none;">
                                EMI months: <strong id="al_emi_months">—</strong>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 d-flex align-items-end">
                            <div class="repay-box">
                                <div style="color:var(--text-secondary);font-size:0.76rem;margin-bottom:3px;">Total Repayment</div>
                                <div style="font-size:1.05rem;font-weight:700;color:var(--rose-mid);">
                                    &#8377;<span id="al_total_repay">0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" id="saveLoanBtn" onclick="saveLoan()">
                    <i class="fa fa-save me-1"></i> Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================
     LOAN DEDUCTION MODAL
     ========================================================= -->
<div class="modal fade" id="loanDeductionModal" tabindex="-1" aria-labelledby="loanDeductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loanDeductionModalLabel">
                    <i class="fa fa-list me-2" style="color:var(--rose-mid);"></i>Loan Deduction Records
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding:0;">
                <div id="deductionLoader" class="modal-loader">
                    <i class="fa fa-spinner fa-spin"></i>&nbsp;Loading records&hellip;
                </div>
                <div id="deductionContent" style="display:none;">
                    <div style="padding:12px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <label style="font-size:0.82rem;color:var(--text-secondary);margin:0;">Filter by Loan ID:</label>
                        <input type="number" id="dedLoanIdFilter" class="form-control form-control-sm" style="width:130px;" placeholder="All">
                        <button type="button" class="btn-rose" style="padding:6px 14px;font-size:0.82rem;" onclick="loadDeductions()">
                            <i class="fa fa-search me-1"></i>Filter
                        </button>
                        <button type="button" class="filter-btn" onclick="document.getElementById('dedLoanIdFilter').value='';loadDeductions()">Clear</button>
                        <span id="dedCount" style="margin-left:auto;font-size:0.78rem;color:var(--text-secondary);"></span>
                    </div>
                    <div style="overflow-x:auto;max-height:60vh;padding:14px 18px;">
                        <table class="table ded-table" style="width:100%;min-width:950px;">
                            <thead>
                                <tr>
                                    <th>Loan ID</th>
                                    <th>Emp ID</th>
                                    <th>Employee Name</th>
                                    <th>Loan Amount (&#8377;)</th>
                                    <th>Last Month Balance (&#8377;)</th>
                                    <th>EMI (&#8377;)</th>
                                    <th>Interest (&#8377;)</th>
                                    <th>Final Deduction (&#8377;)</th>
                                    <th>Balance Amount (&#8377;)</th>
                                    <th>Month</th>
                                </tr>
                            </thead>
                            <tbody id="dedTableBody">
                                <tr>
                                    <td colspan="10" class="text-center" style="color:var(--text-secondary);padding:30px 0;">
                                        No deduction records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script>
    $('#loan').addClass('active');

    /* ── Month filter ── */
    function applyMonthFilter() {
        const parts  = document.getElementById('monthYearSelect').value.split('-');
        const month  = parseInt(parts[1], 10);
        const year   = parts[0];
        const filter = new URLSearchParams(window.location.search).get('filter') || 'all';
        window.location.href = '?month=' + month + '&year=' + year + '&filter=' + encodeURIComponent(filter);
    }
    function setLoanFilter(type) {
        const p     = new URLSearchParams(window.location.search);
        const month = p.get('month') || <?= $sel_month ?>;
        const year  = p.get('year')  || <?= $sel_year ?>;
        window.location.href = '?month=' + month + '&year=' + year + '&filter=' + encodeURIComponent(type);
    }

    /* ── DataTable ── */
    $(document).ready(function () {
        $('#loanTable').DataTable({
            pageLength: 15,
            order: [[0, 'desc']],
            language: { emptyTable: 'No loan / advance records found for <?= addslashes($sel_label) ?>.' }
        });
    });

    /* ════════════════════════════
       ADD LOAN MODAL
       ════════════════════════════ */
    let empTimer = null;
    $('#al_user_id').on('input', function () {
        clearTimeout(empTimer);
        const uid = $(this).val().trim();
        if (!uid || uid.length < 4) { clearEmpFields(); return; }
        empTimer = setTimeout(function () { fetchEmployee(uid); }, 500);
    });

    function fetchEmployee(uid) {
        const fb = $('#al_emp_feedback');
        fb.html('<span style="color:var(--text-secondary);"><i class="fa fa-spinner fa-spin"></i> Fetching&hellip;</span>');
        $.ajax({
            url: 'loan_db.php',
            type: 'GET',
            data: { action: 'get_employee', user_id: uid },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#al_user_name').val(res.user_name);
                    $('#al_dept').val(res.dept || '');
                    $('#al_desig').val(res.desig || '');
                    $('#al_doj_val').val(res.doj || '');
                    var dojTxt = '—';
                    if (res.doj) {
                        var d = new Date(res.doj);
                        dojTxt = d.toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
                    }
                    $('#al_doj_display').val(dojTxt);
                    fb.html('<span style="color:#2e7d52;"><i class="fa fa-check-circle"></i> Found: ' + res.user_name + '</span>');
                } else {
                    clearEmpFields();
                    fb.html('<span style="color:var(--rose-deep);"><i class="fa fa-exclamation-circle"></i> ' + res.message + '</span>');
                }
            },
            error: function () { fb.html('<span style="color:var(--rose-deep);">Request failed</span>'); }
        });
    }

    function clearEmpFields() {
        $('#al_user_name, #al_dept, #al_desig, #al_doj_display, #al_doj_val').val('');
        $('#al_emp_feedback').html('');
    }

    /* Auto-calc interest + end month */
    $('#al_amount, #al_emi, #al_start_month').on('input change', recalcLoan);

    function recalcLoan() {
        var amount     = parseFloat($('#al_amount').val()) || 0;
        var emi        = parseFloat($('#al_emi').val()) || 0;
        var startMonth = $('#al_start_month').val();
        var rate       = 12;

        var payable = (amount * rate / 100) + amount;

        var monthly = (amount * rate / 100) / 12;
        var annual  = amount * rate / 100;
        $('#al_monthly_interest').html('₹' + monthly.toFixed(2));
        $('#al_annual_interest').html('₹' + annual.toFixed(2));

        if (amount > 0 && emi > 0) {
            var numMonths = Math.ceil(payable / emi);
            $('#al_emi_months').text(numMonths + ' month' + (numMonths !== 1 ? 's' : ''));
            $('#al_months_info').show();
            var totalRepay = (emi * numMonths).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
            $('#al_total_repay').text(totalRepay);

            if (startMonth) {
                var parts   = startMonth.split('-');
                var yr      = parseInt(parts[0]);
                var mo      = parseInt(parts[1]);
                var endDate = new Date(yr, mo - 1 + (numMonths - 1), 1);
                var endYr   = endDate.getFullYear();
                var endMo   = String(endDate.getMonth() + 1).padStart(2, '0');
                $('#al_end_month').val(endYr + '-' + endMo);
            }
        } else {
            $('#al_months_info').hide();
            $('#al_emi_months').text('—');
            $('#al_end_month').val('');
            $('#al_total_repay').text('0.00');
        }
    }

    /* Reset on close */
    $('#addLoanModal').on('hidden.bs.modal', function () {
        $('#addLoanForm')[0].reset();
        clearEmpFields();
        $('#al_interest').val('12');
        $('#al_monthly_interest').text('₹0.00');
        $('#al_annual_interest').text('₹0.00');
        $('#al_months_info').hide();
        $('#al_end_month').val('');
        $('#al_total_repay').text('0.00');
    });

    /* Save loan */
    function saveLoan() {
        var uid  = $('#al_user_id').val().trim();
        var name = $('#al_user_name').val().trim();
        var amt  = $('#al_amount').val().trim();
        var emi  = $('#al_emi').val().trim();
        var sm   = $('#al_start_month').val().trim();

        if (!uid || !name) {
            Swal.fire({ icon:'warning', title:'Missing Employee', text:'Enter a valid Employee ID and wait for autofill.',
                background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a' });
            return;
        }
        if (!amt || !emi) {
            Swal.fire({ icon:'warning', title:'Missing Fields', text:'Please enter Amount and EMI Amount.',
                background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a' });
            return;
        }
        if (!sm) {
            Swal.fire({ icon:'warning', title:'Missing Start Month', text:'Please select a start month.',
                background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a' });
            return;
        }

        $('#saveLoanBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Saving&hellip;');

        $.ajax({
            url: 'loan_db.php',
            type: 'POST',
            data: {
                action:        'add_loan',
                user_id:       uid,
                user_name:     name,
                dept:          $('#al_dept').val(),
                desig:         $('#al_desig').val(),
                doj:           $('#al_doj_val').val(),
                loan_type:     $('#al_loan_type').val(),
                amount:        amt,
                emi_amount:    emi,
                interest_rate: $('#al_interest').val(),
                start_month:   sm,
                end_month:     $('#al_end_month').val()
            },
            dataType: 'json',
            success: function (res) {
                $('#saveLoanBtn').prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save');
                if (res.success) {
                    Swal.fire({
                        icon:'success', title:'Loan Added!', text: res.message,
                        background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a'
                    }).then(function () {
                        bootstrap.Modal.getInstance(document.getElementById('addLoanModal')).hide();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon:'error', title:'Error', text: res.message,
                        background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a' });
                }
            },
            error: function () {
                $('#saveLoanBtn').prop('disabled', false).html('<i class="fa fa-save me-1"></i> Save');
                Swal.fire({ icon:'error', title:'Request Failed', text:'Could not reach the server.',
                    background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a' });
            }
        });
    }

    /* ════════════════════════════
       LOAN DEDUCTION MODAL
       ════════════════════════════ */
    $('#loanDeductionModal').on('show.bs.modal', function () { loadDeductions(); });

    function loadDeductions() {
        var loanId = $('#dedLoanIdFilter').val().trim();
        $('#deductionLoader').show();
        $('#deductionContent').hide();

        $.ajax({
            url: 'loan_db.php',
            type: 'GET',
            data: { action: 'get_deductions', loan_id: loanId || 0 },
            dataType: 'json',
            success: function (res) {
                $('#deductionLoader').hide();
                $('#deductionContent').show();
                var tbody = $('#dedTableBody');
                tbody.empty();

                if (res.success && res.data.length > 0) {
                    $('#dedCount').text(res.data.length + ' record(s)');
                    $.each(res.data, function (i, r) {
                        tbody.append(
                            '<tr>' +
                            '<td><strong>' + r.loan_id + '</strong></td>' +
                            '<td>' + r.empid + '</td>' +
                            '<td>' + escHtml(r.empname) + '</td>' +
                            '<td>&#8377;' + Number(r.loan_amt).toLocaleString('en-IN') + '</td>' +
                            '<td>&#8377;' + Number(r.last_month_balance).toLocaleString('en-IN') + '</td>' +
                            '<td>&#8377;' + Number(r.emi).toLocaleString('en-IN') + '</td>' +
                            '<td>&#8377;' + Number(r.intrest).toLocaleString('en-IN') + '</td>' +
                            '<td style="font-weight:600;color:var(--rose-mid);">&#8377;' + Number(r.final_deduction).toLocaleString('en-IN') + '</td>' +
                            '<td style="font-weight:600;">&#8377;' + Number(r.balance_amt).toLocaleString('en-IN') + '</td>' +
                            '<td>' + escHtml(r.Month) + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    $('#dedCount').text('0 record(s)');
                    tbody.html('<tr><td colspan="10" class="text-center" style="color:var(--text-secondary);padding:30px 0;">No deduction records found.</td></tr>');
                }
            },
            error: function () {
                $('#deductionLoader').hide();
                $('#deductionContent').show();
                $('#dedTableBody').html('<tr><td colspan="10" class="text-center" style="color:var(--rose-deep);padding:20px 0;">Failed to load records.</td></tr>');
            }
        });
    }

    function escHtml(str) {
        if (!str) return '&mdash;';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /* ════════════════════════════
       PDF GENERATION (pdfmake)
       ════════════════════════════ */
    function downloadLoanPDF(data) {
        var fmtCurr = function (v) {
            return '\u20B9' + Number(v).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        };
        var fmtMonth = function (ym) {
            if (!ym) return '—';
            var d = new Date(ym);
            return d.toLocaleDateString('en-IN', {month:'long', year:'numeric'});
        };
        var fmtDate = function (dt) {
            if (!dt || dt === '0000-00-00') return '—';
            return new Date(dt).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'});
        };
        var monthlyInterest = ((data.amount * data.interest_rate) / 100) / 12;
        var numMonths = data.emi_amount > 0 ? Math.ceil(data.amount / data.emi_amount) : 0;

        var docDefinition = {
            pageSize: 'A4',
            pageMargins: [40, 55, 40, 50],
            content: [
                {
                    columns: [
                        { stack: [
                            { text: 'HRMS', style: 'companyName' },
                            { text: 'Human Resource Management System', style: 'companyTag' }
                        ]},
                        { stack: [
                            { text: 'LOAN / ADVANCE RECORD', style: 'docTitle' },
                            { text: 'Loan ID: #' + data.loan_id, style: 'loanIdText' }
                        ], alignment: 'right' }
                    ]
                },
                { canvas: [{ type:'line', x1:0, y1:5, x2:515, y2:5, lineWidth:1.5, lineColor:'#c2637a' }], margin:[0,12,0,16] },

                { text: 'Employee Details', style: 'sectionTitle' },
                {
                    table: {
                        widths: ['35%','65%'],
                        body: [
                            [{ text:'Employee ID',    style:'lbl' }, { text: String(data.user_id),       style:'val' }],
                            [{ text:'Employee Name',  style:'lbl' }, { text: data.user_name || '—',      style:'val' }],
                            [{ text:'Department',     style:'lbl' }, { text: data.dept || '—',           style:'val' }],
                            [{ text:'Designation',    style:'lbl' }, { text: data.desig || '—',          style:'val' }],
                            [{ text:'Date of Joining',style:'lbl' }, { text: fmtDate(data.doj),          style:'val' }]
                        ]
                    },
                    layout: 'lightHorizontalLines',
                    margin: [0, 5, 0, 18]
                },

                { text: 'Loan Details', style: 'sectionTitle' },
                {
                    table: {
                        widths: ['35%','65%'],
                        body: [
                            [{ text:'Type',             style:'lbl' }, { text: data.loan_type || '—',                           style:'val' }],
                            [{ text:'Loan Amount',      style:'lbl' }, { text: fmtCurr(data.amount),                             style:'valHi' }],
                            [{ text:'EMI Amount',       style:'lbl' }, { text: fmtCurr(data.emi_amount),                         style:'val' }],
                            [{ text:'Interest Rate',    style:'lbl' }, { text: Number(data.interest_rate).toFixed(2) + '% p.a.', style:'val' }],
                            [{ text:'Monthly Interest', style:'lbl' }, { text: fmtCurr(monthlyInterest),                         style:'val' }],
                            [{ text:'No. of EMI Months',style:'lbl' }, { text: numMonths + ' months',                            style:'val' }],
                            [{ text:'Start Month',      style:'lbl' }, { text: fmtMonth(data.start_month),                       style:'val' }],
                            [{ text:'End Month',        style:'lbl' }, { text: fmtMonth(data.end_month),                         style:'val' }],
                            [{ text:'Status',           style:'lbl' }, { text: data.status || 'Ongoing',                         style:'val' }]
                        ]
                    },
                    layout: 'lightHorizontalLines',
                    margin: [0, 5, 0, 24]
                },

                { canvas: [{ type:'line', x1:0, y1:0, x2:515, y2:0, lineWidth:0.7, lineColor:'#ddd' }], margin:[0,0,0,8] },
                { text: 'This document is system-generated.', style: 'footNote' },
                { text: 'Generated on: ' + new Date().toLocaleString('en-IN'), style: 'footNote' }
            ],
            styles: {
                companyName: { fontSize:22, bold:true, color:'#c2637a' },
                companyTag:  { fontSize:9,  color:'#888', margin:[0,2,0,0] },
                docTitle:    { fontSize:13, bold:true, color:'#333' },
                loanIdText:  { fontSize:10, color:'#c2637a', margin:[0,4,0,0] },
                sectionTitle:{ fontSize:11, bold:true, color:'#c2637a', margin:[0,0,0,4], decoration:'underline' },
                lbl:         { fontSize:9,  bold:true, color:'#666' },
                val:         { fontSize:10, color:'#222' },
                valHi:       { fontSize:11, bold:true, color:'#c2637a' },
                footNote:    { fontSize:8,  color:'#aaa', margin:[0,2,0,0] }
            },
            defaultStyle: { font: 'Roboto' }
        };

        try {
            pdfMake.createPdf(docDefinition).download(
                'Loan_' + data.loan_id + '_' + (data.user_name || 'Employee').replace(/\s+/g,'_') + '.pdf'
            );
        } catch(e) {
            Swal.fire({ icon:'error', title:'PDF Error', text: e.message,
                background:'var(--modal-bg)', color:'var(--text-primary)', confirmButtonColor:'#c2637a' });
        }
    }
</script>






<?php 
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$today    = new DateTime();
$lastMo   = (clone $today)->modify('-1 month');

$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval($lastMo->format('n'));
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval($lastMo->format('Y'));

$selDate  = new DateTime("{$sel_year}-{$sel_month}-01");
$curFirst = new DateTime(date('Y-m-01'));
if ($selDate > $curFirst) {
    $sel_month = intval($lastMo->format('n'));
    $sel_year  = intval($lastMo->format('Y'));
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);

/* ── Users (+ designation & dept for payslip) ─────────────────────────── */
$users_q = mysqli_query($conn,
    "SELECT u.user_id, u.user_name, u.user_email, u.doj,
            COALESCE(d.designation_name, '—') AS designation_name,
            COALESCE(dep.dept_name, '—')      AS dept_name
     FROM users u
     LEFT JOIN designations d  ON u.designation_id = d.designation_id
     LEFT JOIN departments dep ON d.dept_id = dep.dept_id
     WHERE u.dele_te='0' AND u.is_left='no' 
     ORDER BY u.user_id ASC");
$users = [];
while ($u = mysqli_fetch_assoc($users_q)) $users[] = $u;

/* ── Attendance ───────────────────────────────────────────────────────── */
$att_data = [];
$att_q = mysqli_query($conn,
    "SELECT user_id,
            DAY(attendance_date) AS day,
            in_time, out_time,
            total_hours, status
     FROM   attendance
     WHERE  MONTH(attendance_date) = $sel_month
       AND  YEAR(attendance_date)  = $sel_year");
while ($a = mysqli_fetch_assoc($att_q)) {
    $att_data[$a['user_id']][$a['day']] = $a;
}

/* ── Salary master (individual components for payslip breakdown) ──────── */
$salary_data = [];
$sal_q = mysqli_query($conn,
    "SELECT sm.user_id, sm.basic, sm.hra, sm.special_allowance, sm.ta, sm.other_allowance,
            (sm.basic + sm.hra + sm.special_allowance + sm.other_allowance) AS gross
     FROM salary_master sm
     INNER JOIN (
         SELECT user_id, MAX(salary_id) AS max_id
         FROM salary_master WHERE isdelete = '0' GROUP BY user_id
     ) latest ON sm.salary_id = latest.max_id");
while ($s = mysqli_fetch_assoc($sal_q)) {
    $salary_data[intval($s['user_id'])] = [
        'basic'             => floatval($s['basic']),
        'hra'               => floatval($s['hra']),
        'special_allowance' => floatval($s['special_allowance']),
        'ta'                => floatval($s['ta']),
        'other_allowance'   => floatval($s['other_allowance']),
        'gross'             => floatval($s['gross']),
    ];
}

/* ── Leave data ───────────────────────────────────────────────────────── */
$leave_data = [];
$lv_q = mysqli_query($conn,
    "SELECT user_id,
            leave_type_id,
            SUM(CASE WHEN MONTH(from_date) = $sel_month THEN total_days ELSE 0 END) AS used_this_month,
            SUM(CASE WHEN MONTH(from_date)  < $sel_month THEN total_days ELSE 0 END) AS used_before
     FROM leave_applications
     WHERE leave_type_id IN (1, 2)
       AND status      = 'Approved'
       AND YEAR(from_date) = $sel_year
     GROUP BY user_id, leave_type_id");
while ($lv = mysqli_fetch_assoc($lv_q)) {
    $leave_data[intval($lv['user_id'])][intval($lv['leave_type_id'])] = [
        'used_this_month' => floatval($lv['used_this_month']),
        'used_before'     => floatval($lv['used_before']),
    ];
}

/* ── Month dropdown ───────────────────────────────────────────────────── */
$months_list = [];
$cursor = (clone $curFirst)->modify('-1 month');
for ($i = 0; $i < 24; $i++) {
    $months_list[] = [
        'month' => intval($cursor->format('n')),
        'year'  => intval($cursor->format('Y')),
        'label' => $cursor->format('F Y'),
    ];
    $cursor->modify('-1 month');
}
$sel_label = date('F Y', mktime(0, 0, 0, $sel_month, 1, $sel_year));
?>

<style>
    /* ── Light-theme: sticky columns opaque ── */
    body:not(.theme-dark) .col-fixed {
        background: #ffffff !important;
        color: #212529 !important;
    }
    body:not(.theme-dark) thead .col-empid,
    body:not(.theme-dark) thead .col-empname,
    body:not(.theme-dark) thead .col-rowlabel {
        background: #1a1a2e !important;
        color: #ffffff !important;
    }

    /* ── Table ── */
    .payroll-cal-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        min-width: 100%;
        font-size: 0.72rem;
        color: var(--text-primary);
    }
    .payroll-cal-table th,
    .payroll-cal-table td { background-clip: padding-box; }

    .payroll-cal-table thead th {
        background: var(--sidebar-bg, #1a1a2e) !important;
        color: var(--text-secondary);
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
        padding: 7px 4px;
        border-bottom: 1px solid var(--border-hi);
        border-right: 1px solid var(--border-hi);
        position: sticky;
        top: 0;
        z-index: 20;
    }
    .payroll-cal-table thead .subhead-row th {
        top: 33px;
        font-size: 0.62rem;
        padding: 3px 4px;
        font-weight: 500;
        z-index: 21;
    }

    .col-day { min-width: 62px; }
    .weekend { color: var(--rose-mid, #c2637a) !important; }

    .col-fixed {
        position: sticky;
        background: var(--bg-card) !important;
        color: var(--text-primary) !important;
        white-space: nowrap;
        border-right: 1px solid var(--border-hi);
        z-index: 20;
        box-shadow: 1px 0 0 var(--border-hi);
    }
    .col-empid    { left: 0;     min-width: 62px;  text-align: center; font-weight: 600; z-index: 15; }
    .col-empname  { left: 62px;  min-width: 140px; text-align: left;   font-weight: 500; z-index: 15; }
    .col-rowlabel { left: 202px; min-width: 90px;  text-align: left;   font-weight: 600; z-index: 15; }

    thead .col-empid,
    thead .col-empname,
    thead .col-rowlabel {
        background: var(--sidebar-bg, #1a1a2e) !important;
        z-index: 30 !important;
    }

    .payroll-cal-table tbody td {
        padding: 3px 4px;
        text-align: center;
        border-bottom: 1px solid var(--border-hi);
        border-right: 1px solid var(--border-hi);
        vertical-align: middle;
        white-space: nowrap;
    }
    .emp-first-row td { border-top: 2px solid var(--border-hi); }
    .emp-last-row  td { border-bottom: 2px solid rgba(194,99,122,0.3); }
    .weekend-cell     { background: rgba(194,99,122,0.04); }

    .s-present { display:inline-block; padding:1px 6px; border-radius:3px; background:rgba(46,125,82,0.14);   color:#2e7d52;                 font-weight:700; }
    .s-absent  { display:inline-block; padding:1px 6px; border-radius:3px; background:rgba(194,99,122,0.14); color:var(--rose-mid,#c2637a); font-weight:700; }
    .s-half    { display:inline-block; padding:1px 6px; border-radius:3px; background:rgba(201,151,90,0.14);  color:#b87a2a;                 font-weight:700; }
    .s-weekend { display:inline-block; padding:1px 5px; border-radius:3px; background:rgba(100,100,120,0.08); color:var(--text-secondary);   font-size:0.62rem; }
    .s-none    { color: var(--text-secondary); }

    .col-sal-hd  { min-width: 84px; text-align: center; white-space: nowrap; line-height: 1.35; }
    .col-sal     { min-width: 84px; padding: 3px 6px !important; text-align: right; vertical-align: middle; white-space: nowrap; font-size: 0.70rem; }
    .col-sal-payable { font-weight: 700; color: #2e7d52; }
    body.theme-dark .col-sal-payable { color: #5dba88; }
    .col-sal-deduct  { color: var(--rose-mid, #c2637a); }

    .col-summary-end { min-width: 72px; text-align: center; vertical-align: middle; white-space: nowrap; padding: 4px 6px !important; }
    .col-summary-end div { font-size: 0.68rem; color: var(--text-secondary); line-height: 1.65; }

    /* ── Payslip column ── */
    .col-payslip-hd   { min-width: 90px; text-align: center; }
    .col-payslip-cell { min-width: 90px; text-align: center; vertical-align: middle; padding: 5px 6px !important; }

    .btn-view-slip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 11px;
        font-size: 0.70rem;
        font-weight: 600;
        border-radius: 5px;
        border: none;
        background: linear-gradient(135deg, #1a1a2e, #c2637a);
        color: #fff;
        cursor: pointer;
        transition: opacity 0.15s;
        white-space: nowrap;
        line-height: 1.4;
    }
    .btn-view-slip:hover { opacity: 0.82; }

    /* ══════════════════════════════════════════
       PAYSLIP MODAL — always light, never themed
    ══════════════════════════════════════════ */
    #psOverlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.62);
        z-index: 99999;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    #psOverlay.ps-open { display: flex; }

    #psWrap {
        background: #f0f2f5;
        border-radius: 12px;
        width: 100%;
        max-width: 840px;
        max-height: 93vh;
        overflow-y: auto;
        box-shadow: 0 24px 72px rgba(0,0,0,0.45);
        font-family: 'DM Sans','Segoe UI',Arial,sans-serif;
        font-size: 13px;
        color: #1a1a2e;
    }

    /* toolbar */
    #psToolbar {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 11px 20px;
        gap: 10px;
        border-bottom: 1px solid #dde1e7;
    }
    #psToolbarTitle { font-size: 0.82rem; font-weight: 700; color: #1a1a2e; }
    .ps-tb-actions  { display: flex; gap: 8px; }
    .ps-tb-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 16px; border-radius: 7px;
        font-size: 0.78rem; font-weight: 600;
        cursor: pointer; border: none; font-family: inherit;
        text-decoration: none; white-space: nowrap;
    }
    .ps-tb-print { background: linear-gradient(135deg,#1a1a2e,#c2637a); color: #fff; }
    .ps-tb-close { background: #fff; color: #555; border: 1px solid #ccc; }
    .ps-tb-print:hover { opacity: 0.88; }
    .ps-tb-close:hover { background: #ececec; }

    /* payslip card */
    #psCard {
        max-width: 820px;
        margin: 20px auto;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.10);
        overflow: hidden;
    }

    .ps-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #c2637a 100%);
        color: #fff;
        padding: 28px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ps-company-name { font-size: 1.5rem; font-weight: 700; letter-spacing: 1px; }
    .ps-company-sub  { font-size: 0.78rem; opacity: 0.72; margin-top: 4px; }
    .ps-month-badge  {
        background: rgba(255,255,255,0.15);
        border: 1px solid rgba(255,255,255,0.30);
        border-radius: 20px;
        padding: 6px 18px;
        font-size: 0.84rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .ps-info {
        background: #f8f9fb;
        border-bottom: 1px solid #e2e6ea;
        padding: 18px 32px;
        display: grid;
        grid-template-columns: repeat(4,1fr);
        gap: 12px;
    }
    .ps-info-item label {
        display: block;
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #888;
        margin-bottom: 2px;
    }
    .ps-info-item span { font-weight: 600; color: #1a1a2e; font-size: 0.82rem; }

    .ps-att {
        padding: 14px 32px;
        border-bottom: 1px solid #e2e6ea;
        display: flex;
        gap: 36px;
        background: #fff;
        flex-wrap: wrap;
    }
    .ps-att-item { text-align: center; }
    .ps-att-val  { font-size: 1.3rem; font-weight: 700; color: #1a1a2e; }
    .ps-att-val.ps-green  { color: #2e7d52; }
    .ps-att-val.ps-red    { color: #c2637a; }
    .ps-att-val.ps-orange { color: #b87a2a; }
    .ps-att-val.ps-blue   { color: #4046c8; }
    .ps-att-item label {
        display: block;
        font-size: 0.67rem;
        text-transform: uppercase;
        letter-spacing: 0.7px;
        color: #888;
        margin-top: 2px;
    }

    .ps-body {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0;
        padding: 0 32px 20px;
    }
    .ps-col { padding: 20px 0; }
    .ps-col:first-child { border-right: 1px dashed #ddd; padding-right: 28px; }
    .ps-col:last-child  { padding-left: 28px; }

    .ps-col-title {
        font-size: 0.68rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        color: #c2637a;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 2px solid #c2637a;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ps-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 5px 0;
        border-bottom: 1px dashed #f0f0f0;
    }
    .ps-row .item-name { color: #555; }
    .ps-row .item-val  { font-weight: 600; color: #1a1a2e; white-space: nowrap; margin-left: 8px; }
    .ps-row.lop-row .item-name { color: #c2637a; font-style: italic; }
    .ps-row.lop-row .item-val  { color: #c2637a; }

    .ps-subtotal {
        display: flex;
        justify-content: space-between;
        margin-top: 12px;
        padding-top: 8px;
        border-top: 2px solid #e2e6ea;
        font-weight: 700;
        font-size: 0.88rem;
    }
    .ps-val-green { color: #2e7d52; }
    .ps-val-red   { color: #c2637a; }

    .ps-net {
        margin: 0 32px 24px;
        background: linear-gradient(135deg, #1a1a2e, #c2637a);
        color: #fff;
        border-radius: 10px;
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ps-net .net-label  { font-size: 0.73rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }
    .ps-net .net-words  { font-size: 0.77rem; opacity: 0.85; margin-top: 3px; }
    .ps-net .net-amount { font-size: 1.9rem; font-weight: 700; letter-spacing: -1px; white-space: nowrap; }

    .ps-footer {
        border-top: 1px solid #e2e6ea;
        padding: 13px 32px;
        font-size: 0.71rem;
        color: #888;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    /* print */
    @media print {
        body * { visibility: hidden !important; }
        #psCard, #psCard * { visibility: visible !important; }
        #psCard {
            position: fixed !important;
            inset: 0 !important;
            margin: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            max-width: 100% !important;
            overflow: visible !important;
        }
        #psToolbar { display: none !important; }
    }
</style>

<div class="dashboard-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h2 class="dash-section-title">Payroll</h2>
            <p class="dash-section-sub">Create and manage monthly payroll for all employees — <?= htmlspecialchars($sel_label) ?></p>
        </div>

        <form method="GET" action="payroll.php" id="monthForm" class="d-flex align-items-center" style="gap:8px;">
            <label style="font-size:0.83rem;color:var(--text-secondary);white-space:nowrap;">Select Month</label>
            <select name="" id="monthSelect" class="form-select form-select-sm"
                    style="min-width:160px;background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border-hi);"
                    onchange="applyMonth(this)">
                <?php foreach ($months_list as $m):
                    $val = $m['year'] . '-' . str_pad($m['month'], 2, '0', STR_PAD_LEFT);
                    $cur = $sel_year  . '-' . str_pad($sel_month,  2, '0', STR_PAD_LEFT);
                ?>
                <option value="<?= $val ?>" <?= ($val === $cur) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="month" id="hMonth" value="<?= $sel_month ?>">
            <input type="hidden" name="year"  id="hYear"  value="<?= $sel_year ?>">
        </form>
    </div>

    <div class="content-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;overflow-y:auto;max-height:72vh;">
            <table class="payroll-cal-table" id="payrollCalTable">
                <thead>
                    <tr>
                        <th class="col-fixed col-empid"    rowspan="2">Emp ID</th>
                        <th class="col-fixed col-empname"  rowspan="2">Emp Name</th>
                        <th class="col-fixed col-rowlabel" rowspan="2">Type</th>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <th class="col-day"><?= $d ?></th>
                        <?php endfor; ?>
                        <th class="col-sal-hd" rowspan="2">Summary</th>
                        <th class="col-sal-hd" rowspan="2">Gross<br>Salary</th>
                        <th class="col-sal-hd" rowspan="2">Per Day</th>
                        <th class="col-sal-hd" rowspan="2">Deductions</th>
                        <th class="col-sal-hd" rowspan="2">PT</th>
                        <th class="col-sal-hd" rowspan="2">Payable</th>
                        <th class="col-payslip-hd" rowspan="2">Payslip</th>
                    </tr>
                    <tr class="subhead-row">
                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                        ?>
                        <th class="col-dow <?= in_array($dow,['Sat','Sun']) ? 'weekend' : '' ?>"><?= $dow ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="<?= 11 + $days_in_month ?>" class="text-center py-4"
                            style="color:var(--text-secondary);">No employees found.</td>
                    </tr>
                    <?php else: foreach ($users as $u):
                        $uid  = $u['user_id'];
                        $present_days = 0;
                        $absent_days  = 0;
                        $half_days    = 0;

                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $a   = $att_data[$uid][$d] ?? null;
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                            if (in_array($dow, ['Sat','Sun'])) continue;
                            if ($a) {
                                if      ($a['status'] === 'present')  $present_days++;
                                elseif  ($a['status'] === 'absent')   $absent_days++;
                                elseif  ($a['status'] === 'half_day') $half_days++;
                            } else {
                                $absent_days++;
                            }
                        }

                        $sal   = $salary_data[$uid] ?? null;
                        $total = $sal ? $sal['gross'] : 0;
                        $basic = $sal ? $sal['basic'] : 0;

                        $pf   = ($basic > 0) ? round($basic * 0.12, 2) : 0;
                        $esic = ($total > 0 && $total <= 21000) ? round($total * 0.0075, 2) : 0;
                        $gross = $total - $pf - $esic;

                        $per_day   = ($days_in_month > 0 && $gross > 0) ? ($gross / $days_in_month) : 0;
                        $deduction = $per_day * ($absent_days + 0.5 * $half_days);

                        if      ($gross <= 0)     $pt = 0;
                        elseif  ($gross <= 7500)  $pt = 0;
                        elseif  ($gross <= 10000) $pt = 175;
                        else                      $pt = 200;

                        $cl_before     = $leave_data[$uid][1]['used_before']     ?? 0;
                        $sl_before     = $leave_data[$uid][2]['used_before']     ?? 0;
                        $cl_this_month = $leave_data[$uid][1]['used_this_month'] ?? 0;
                        $sl_this_month = $leave_data[$uid][2]['used_this_month'] ?? 0;
                        $cl_remaining  = max(0, 12 - $cl_before);
                        $sl_remaining  = max(0, 12 - $sl_before);
                        $cl_covered    = min($cl_this_month, $cl_remaining);
                        $sl_covered    = min($sl_this_month, $sl_remaining);
                        $leave_covered = $cl_covered + $sl_covered;
                        $deduct_absent = max(0, $absent_days - $leave_covered);

                        $payable = ($gross > 0)
                            ? max(0, $gross - ($deduct_absent + $half_days * 0.5) * $per_day - $pt)
                            : 0;

                        /* working days (non-weekend) for payslip attendance strip */
                        $working_days = 0;
                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                            if (!in_array($dow, ['Sat','Sun'])) $working_days++;
                        }
                        $lop_days = max(0, $working_days - $present_days - $half_days);

                        /* payslip data — embedded directly on button via data-ps */
                        $ps = [
                            'uid'               => $uid,
                            'name'              => $u['user_name'],
                            'email'             => $u['user_email'] ?? '—',
                            'doj'               => ($u['doj'] ? date('d M Y', strtotime($u['doj'])) : '—'),
                            'designation'       => $u['designation_name'],
                            'dept'              => $u['dept_name'],
                            'month_label'       => $sel_label,
                            'pay_from'          => date('01 M Y', mktime(0,0,0,$sel_month,1,$sel_year)),
                            'pay_to'            => date('d M Y',  mktime(0,0,0,$sel_month,$days_in_month,$sel_year)),
                            'days_in_month'     => $days_in_month,
                            'working_days'      => $working_days,
                            'present_days'      => $present_days,
                            'absent_days'       => $absent_days,
                            'half_days'         => $half_days,
                            'lop_days'          => $lop_days,
                            'leave_covered'     => $leave_covered,
                            'basic'             => round($basic, 2),
                            'hra'               => round($sal['hra'] ?? 0, 2),
                            'special_allowance' => round($sal['special_allowance'] ?? 0, 2),
                            'ta'                => round($sal['ta'] ?? 0, 2),
                            'other_allowance'   => round($sal['other_allowance'] ?? 0, 2),
                            'gross'             => round($gross, 2),
                            'per_day'           => round($per_day, 2),
                            'lop_deduction'     => round($deduction, 2),
                            'earned_gross'      => round($gross - $deduction, 2),
                            'pf_emp'            => $pf,
                            'pf_er'             => $pf,
                            'esic_emp'          => $esic,
                            'esic_er'           => ($total > 0 && $total <= 21000) ? round($total * 0.0325, 2) : 0,
                            'pt'                => $pt,
                            'total_deductions'  => round($pf + $esic + $pt, 2),
                            'net_pay'           => round($payable, 2),
                            'generated'         => date('d M Y, h:i A'),
                        ];
                        $ps_attr = htmlspecialchars(json_encode($ps), ENT_QUOTES, 'UTF-8');

                        $rows = ['In Time', 'Out Time', 'Total Time', 'Status'];
                    ?>

                    <?php foreach ($rows as $ri => $rlabel): ?>
                    <tr class="emp-row <?= $ri===0 ? 'emp-first-row' : '' ?> <?= $ri===3 ? 'emp-last-row' : '' ?>">

                        <?php if ($ri === 0): ?>
                        <td class="col-fixed col-empid"   rowspan="4"><?= htmlspecialchars($u['user_id']) ?></td>
                        <td class="col-fixed col-empname" rowspan="4"><?= htmlspecialchars($u['user_name']) ?></td>
                        <?php endif; ?>

                        <td class="col-fixed col-rowlabel"><?= htmlspecialchars($rlabel) ?></td>

                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $a    = $att_data[$uid][$d] ?? null;
                            $dow  = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                            $date_str = sprintf('%04d-%02d-%02d', $sel_year, $sel_month, $d);
                            $week_of_month = ceil($d / 7);

                            $isWE = (
                                $dow === 'Sun' ||
                                ($dow === 'Sat' && in_array($week_of_month, [2, 4])) ||
                                in_array($date_str, $holiday_dates)
                            );

                            if ($a) {
                                switch ($ri) {
                                    case 0: $val = $a['in_time']  ? date('h:i A', strtotime($a['in_time']))  : '—'; break;
                                    case 1: $val = $a['out_time'] ? date('h:i A', strtotime($a['out_time'])) : '—'; break;
                                    case 2: $val = ($a['total_hours'] !== null && $a['total_hours'] !== '') ? $a['total_hours'].'h' : '—'; break;
                                    case 3:
                                        $s = $a['status'];
                                        if      ($s === 'present')  $val = '<span class="s-present">P</span>';
                                        elseif  ($s === 'absent')   $val = '<span class="s-absent">A</span>';
                                        elseif  ($s === 'half_day') $val = '<span class="s-half">H</span>';
                                        else                        $val = '<span class="s-none">—</span>';
                                        break;
                                    default: $val = '—';
                                }
                            } else {
                                if ($isWE) {
                                    $val = '<span class="s-weekend">WO</span>';
                                } else {
                                    $val = ($ri === 3) ? '<span class="s-absent">A</span>' : '<span class="s-none">—</span>';
                                }
                            }
                        ?>
                        <td class="cell-day <?= $isWE ? 'weekend-cell' : '' ?>"><?= $val ?></td>
                        <?php endfor; ?>

                        <?php if ($ri === 0): ?>

                        <td class="col-summary-end" rowspan="4">
                            <div>P:&nbsp;<?= $present_days ?></div>
                            <div>A:&nbsp;<?= $absent_days ?></div>
                            <div>H:&nbsp;<?= $half_days ?></div>
                        </td>

                        <td class="col-sal" rowspan="4">
                            <?= $gross > 0 ? '&#8377;'.number_format($gross,0) : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal" rowspan="4">
                            <?= $per_day > 0 ? '&#8377;'.number_format($per_day,2) : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal-hd" rowspan="4">
                            <?= $deduction > 0 ? '&#8377;'.number_format($deduction,2) : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal col-sal-deduct" rowspan="4">
                            <?= $pt > 0 ? '&#8377;'.number_format($pt,0) : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal col-sal-payable" rowspan="4">
                            <?= $gross > 0 ? '&#8377;'.number_format($payable,0) : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <!-- ✅ Payslip button: data-ps holds all employee data inline -->
                        <td class="col-payslip-cell" rowspan="4">
                            <button class="btn-view-slip" data-ps="<?= $ps_attr ?>" onclick="openPayslip(this)">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                    <line x1="16" y1="13" x2="8" y2="13"/>
                                    <line x1="16" y1="17" x2="8" y2="17"/>
                                </svg>
                                View Slip
                            </button>
                        </td>

                        <?php endif; ?>

                    </tr>
                    <?php endforeach; /* rows */ ?>

                    <?php endforeach; /* users */ endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div id="psOverlay">
    <div id="psWrap">

        <div id="psToolbar">
            <span id="psToolbarTitle">Payslip</span>
            <div class="ps-tb-actions">
                <button class="ps-tb-btn ps-tb-print" onclick="window.print()">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"/>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                        <rect x="6" y="14" width="12" height="8"/>
                    </svg>
                    Print / Save PDF
                </button>
                <button class="ps-tb-btn ps-tb-close" onclick="closePayslip()">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    Close
                </button>
            </div>
        </div>

        <div id="psCard"></div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
$('#payroll').addClass('active');

function applyMonth(sel) {
    var parts = sel.value.split('-');
    document.getElementById('hYear').value  = parts[0];
    document.getElementById('hMonth').value = parseInt(parts[1], 10);
    document.getElementById('monthForm').submit();
}

/* ── Indian number to words ── */
function numToWords(n) {
    n = Math.floor(n);
    if (n === 0) return 'Zero';
    var ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
                'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
                'Seventeen','Eighteen','Nineteen'];
    var tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    function chunk(num) {
        var w = '';
        if (num >= 100) { w += ones[Math.floor(num/100)] + ' Hundred '; num %= 100; }
        if (num >= 20)  { w += tens[Math.floor(num/10)]  + ' '; num %= 10; }
        if (num > 0)    { w += ones[num] + ' '; }
        return w;
    }
    var w = '';
    if (n >= 100000) { w += chunk(Math.floor(n/100000)) + 'Lakh ';     n %= 100000; }
    if (n >= 1000)   { w += chunk(Math.floor(n/1000))   + 'Thousand '; n %= 1000; }
    w += chunk(n);
    return w.trim() + ' Rupees Only';
}

/* ── INR formatter ── */
function inr(v) {
    return '&#8377;' + parseFloat(v).toLocaleString('en-IN',
        { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* ── Open payslip modal ── */
function openPayslip(btn) {
    var d = JSON.parse(btn.getAttribute('data-ps'));

    document.getElementById('psToolbarTitle').textContent =
        'Payslip \u2014 ' + d.name + ' \u2014 ' + d.month_label;

    var lopRow = d.lop_deduction > 0
        ? '<div class="ps-row lop-row" style="margin-top:8px;">' +
              '<span class="item-name">LOP / Absence Deduction</span>' +
              '<span class="item-val">&minus; ' + inr(d.lop_deduction) + '</span>' +
          '</div>'
        : '';

    var esicNote = d.esic_emp > 0
        ? '<span style="font-size:0.69rem;color:#aaa;">(0.75% of Gross)</span>'
        : '<span style="font-size:0.69rem;color:#aaa;">(Not applicable)</span>';

    var erEsicRow = d.esic_er > 0
        ? '<div class="ps-row">' +
              '<span class="item-name" style="color:#aaa;">Employer ESIC (3.25%)</span>' +
              '<span class="item-val" style="color:#aaa;">' + inr(d.esic_er) + '</span>' +
          '</div>'
        : '';

    document.getElementById('psCard').innerHTML =

    /* Header */
    '<div class="ps-header">' +
        '<div>' +
            '<div class="ps-company-name">&#x1F3E2; HRMS</div>' +
            '<div class="ps-company-sub">Salary Slip / Pay Stub</div>' +
        '</div>' +
        '<div class="ps-month-badge">&#x1F4C5; ' + d.month_label + '</div>' +
    '</div>' +

    /* Employee info */
    '<div class="ps-info">' +
        '<div class="ps-info-item"><label>Employee Name</label><span>' + d.name + '</span></div>' +
        '<div class="ps-info-item"><label>Employee ID</label><span>' + d.uid + '</span></div>' +
        '<div class="ps-info-item"><label>Designation</label><span>' + d.designation + '</span></div>' +
        '<div class="ps-info-item"><label>Department</label><span>' + d.dept + '</span></div>' +
        '<div class="ps-info-item"><label>Email</label><span style="font-size:0.78rem;">' + (d.email || '&mdash;') + '</span></div>' +
        '<div class="ps-info-item"><label>Date of Joining</label><span>' + d.doj + '</span></div>' +
        '<div class="ps-info-item"><label>Pay Period</label><span>' + d.pay_from + ' &ndash; ' + d.pay_to + '</span></div>' +
        '<div class="ps-info-item"><label>Status</label><span style="color:#b87a2a;">Draft</span></div>' +
    '</div>' +

    /* Attendance strip */
    '<div class="ps-att">' +
        '<div class="ps-att-item"><div class="ps-att-val">' + d.days_in_month + '</div><label>Total Days</label></div>' +
        '<div class="ps-att-item"><div class="ps-att-val">' + d.working_days  + '</div><label>Working Days</label></div>' +
        '<div class="ps-att-item"><div class="ps-att-val ps-green">'  + d.present_days  + '</div><label>Days Present</label></div>' +
        '<div class="ps-att-item"><div class="ps-att-val ps-orange">' + d.half_days      + '</div><label>Half Days</label></div>' +
        '<div class="ps-att-item"><div class="ps-att-val ps-red">'    + d.lop_days       + '</div><label>LOP Days</label></div>' +
        '<div class="ps-att-item"><div class="ps-att-val ps-blue">'   + d.leave_covered  + '</div><label>Leave Covered</label></div>' +
    '</div>' +

    /* Earnings & Deductions */
    '<div class="ps-body">' +

        '<div class="ps-col">' +
            '<div class="ps-col-title">&#x2197; Earnings</div>' +
            '<div class="ps-row"><span class="item-name">Basic Salary</span><span class="item-val">' + inr(d.basic) + '</span></div>' +
            '<div class="ps-row"><span class="item-name">HRA</span><span class="item-val">' + inr(d.hra) + '</span></div>' +
            '<div class="ps-row"><span class="item-name">Special Allowance</span><span class="item-val">' + inr(d.special_allowance) + '</span></div>' +
            '<div class="ps-row"><span class="item-name">Travel Allowance (TA)</span><span class="item-val">' + inr(d.ta) + '</span></div>' +
            '<div class="ps-row"><span class="item-name">Other Allowances</span><span class="item-val">' + inr(d.other_allowance) + '</span></div>' +
            '<div class="ps-subtotal"><span>Gross Salary</span><span>' + inr(d.gross) + '</span></div>' +
            lopRow +
            '<div class="ps-subtotal" style="margin-top:8px;"><span>Earned Gross</span><span class="ps-val-green">' + inr(d.earned_gross) + '</span></div>' +
        '</div>' +

        '<div class="ps-col">' +
            '<div class="ps-col-title">&#x2198; Deductions</div>' +
            '<div class="ps-row"><span class="item-name">Provident Fund (PF) <span style="font-size:0.69rem;color:#aaa;">(12% of Basic)</span></span><span class="item-val">' + inr(d.pf_emp) + '</span></div>' +
            '<div class="ps-row"><span class="item-name">ESIC ' + esicNote + '</span><span class="item-val">' + inr(d.esic_emp) + '</span></div>' +
            '<div class="ps-row"><span class="item-name">Professional Tax (PT)</span><span class="item-val">' + inr(d.pt) + '</span></div>' +
            '<div class="ps-subtotal"><span>Total Deductions</span><span class="ps-val-red">' + inr(d.total_deductions) + '</span></div>' +
            '<div style="margin-top:18px;padding-top:12px;border-top:1px dashed #e2e6ea;">' +
                '<div style="font-size:0.67rem;text-transform:uppercase;letter-spacing:0.8px;color:#aaa;margin-bottom:8px;">Employer Contributions (informational)</div>' +
                '<div class="ps-row"><span class="item-name" style="color:#aaa;">Employer PF</span><span class="item-val" style="color:#aaa;">' + inr(d.pf_er) + '</span></div>' +
                erEsicRow +
            '</div>' +
        '</div>' +

    '</div>' +

    /* Net pay */
    '<div class="ps-net">' +
        '<div>' +
            '<div class="net-label">Net Pay (Take Home)</div>' +
            '<div class="net-words">&#x20B9; ' + numToWords(d.net_pay) + '</div>' +
        '</div>' +
        '<div class="net-amount">' + inr(d.net_pay) + '</div>' +
    '</div>' +

    /* Footer */
    '<div class="ps-footer">' +
        '<span>This is a system-generated payslip and does not require a signature.</span>' +
        '<span>Generated: ' + d.generated + '</span>' +
    '</div>';

    document.getElementById('psOverlay').classList.add('ps-open');
    document.body.style.overflow = 'hidden';
}

function closePayslip() {
    document.getElementById('psOverlay').classList.remove('ps-open');
    document.body.style.overflow = '';
}

/* close on backdrop click */
document.getElementById('psOverlay').addEventListener('click', function(e) {
    if (e.target === this) closePayslip();
});

/* close on Escape */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePayslip();
});
</script>












<?php
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

// ── Determine selected month (default = last month) ───────────────────────────
$today    = new DateTime();
$lastMo   = (clone $today)->modify('-1 month');

$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval($lastMo->format('n'));
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval($lastMo->format('Y'));

// Guard: never allow current or future month
$selDate  = new DateTime("{$sel_year}-{$sel_month}-01");
$curFirst = new DateTime(date('Y-m-01'));
if ($selDate >= $curFirst) {
    $sel_month = intval($lastMo->format('n'));
    $sel_year  = intval($lastMo->format('Y'));
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);

// ── Employees ─────────────────────────────────────────────────────────────────
$users_q = mysqli_query($conn,
    "SELECT user_id, user_name FROM users
     WHERE dele_te='0' AND is_left='no' AND user_role='Employee'
     ORDER BY user_id ASC");
$users = [];
while ($u = mysqli_fetch_assoc($users_q)) $users[] = $u;

// ── Attendance for selected month ─────────────────────────────────────────────
$att_data = [];
$att_q = mysqli_query($conn,
    "SELECT user_id,
            DAY(attendance_date)  AS day,
            in_time, out_time,
            total_hours, status
     FROM   attendance
     WHERE  MONTH(attendance_date) = $sel_month
       AND  YEAR(attendance_date)  = $sel_year");
while ($a = mysqli_fetch_assoc($att_q)) {
    $att_data[$a['user_id']][$a['day']] = $a;
}

// ── Month selector options (past 24 months, most-recent first) ────────────────
$months_list = [];
$cursor = (clone $curFirst)->modify('-1 month');
for ($i = 0; $i < 24; $i++) {
    $months_list[] = [
        'month' => intval($cursor->format('n')),
        'year'  => intval($cursor->format('Y')),
        'label' => $cursor->format('F Y'),
    ];
    $cursor->modify('-1 month');
}

$sel_label = date('F Y', mktime(0, 0, 0, $sel_month, 1, $sel_year));
?>

<div class="dashboard-wrapper">

    <!-- Page header + month picker ──────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h2 class="dash-section-title">Payroll</h2>
            <p class="dash-section-sub">Attendance records for all employees — <?= htmlspecialchars($sel_label) ?></p>
        </div>

        <form method="GET" action="payroll.php" id="monthForm" class="d-flex align-items-center" style="gap:8px;">
            <label style="font-size:0.83rem;color:var(--text-secondary);white-space:nowrap;">Select Month</label>
            <select name="" id="monthSelect" class="form-select form-select-sm"
                    style="min-width:160px;background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border-hi);"
                    onchange="applyMonth(this)">
                <?php foreach ($months_list as $m):
                    $val = $m['year'] . '-' . str_pad($m['month'], 2, '0', STR_PAD_LEFT);
                    $cur = $sel_year  . '-' . str_pad($sel_month,  2, '0', STR_PAD_LEFT);
                ?>
                <option value="<?= $val ?>" <?= ($val === $cur) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="month" id="hMonth" value="<?= $sel_month ?>">
            <input type="hidden" name="year"  id="hYear"  value="<?= $sel_year ?>">
        </form>
    </div>

    <!-- Attendance grid ─────────────────────────────────────────────────────── -->
    <div class="content-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;overflow-y:auto;max-height:72vh;">
            <table class="payroll-cal-table" id="payrollCalTable">
                <thead>
                    <tr>
                        <th class="col-fixed col-empid" rowspan="2">Emp ID</th>
                        <th class="col-fixed col-empname" rowspan="2">Emp Name</th>
                        <th class="col-fixed col-type" rowspan="2">Type</th>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <th class="col-day"><?= $d ?></th>
                        <?php endfor; ?>
                    </tr>
                    <tr class="subhead-row">
                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                        ?>
                        <th class="col-dow <?= in_array($dow,['Sat','Sun']) ? 'weekend' : '' ?>"><?= $dow ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="<?= 3 + $days_in_month ?>" class="text-center py-4"
                            style="color:var(--text-secondary);">No employees found.</td>
                    </tr>
                    <?php else: foreach ($users as $u):
                        $uid  = $u['user_id'];
                        $rows = ['In Time', 'Out Time', 'Total Time', 'Status'];
                    ?>

                    <?php foreach ($rows as $ri => $rlabel): ?>
                    <tr class="emp-row <?= $ri===0 ? 'emp-first-row' : '' ?> <?= $ri===3 ? 'emp-last-row' : '' ?>">

                        <?php if ($ri === 0): ?>
                        <td class="col-fixed col-empid" rowspan="4"><?= htmlspecialchars($u['user_id']) ?></td>
                        <td class="col-fixed col-empname" rowspan="4"><?= htmlspecialchars($u['user_name']) ?></td>
                        <?php endif; ?>

                        <td class="col-fixed col-type"><?= htmlspecialchars($rlabel) ?></td>

                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $a   = $att_data[$uid][$d] ?? null;
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                            $date_str = sprintf('%04d-%02d-%02d', $sel_year, $sel_month, $d);
                            $week_of_month = ceil($d / 7);

                            $isWE = (
                                $dow === 'Sun' ||
                                ($dow === 'Sat' && in_array($week_of_month, [2, 4])) ||
                                in_array($date_str, $holiday_dates)
                            );

                            if ($a) {
                                switch ($ri) {
                                    case 0:
                                        $val = $a['in_time']    ? date('h:i A', strtotime($a['in_time']))    : '—';
                                        break;
                                    case 1:
                                        $val = $a['out_time']   ? date('h:i A', strtotime($a['out_time']))   : '—';
                                        break;
                                    case 2:
                                        $val = ($a['total_hours'] !== null && $a['total_hours'] !== '')
                                               ? $a['total_hours'].'h' : '—';
                                        break;
                                    case 3:
                                        $s = $a['status'];
                                        if      ($s === 'present')  $val = '<span class="s-present">P</span>';
                                        elseif  ($s === 'absent')   $val = '<span class="s-absent">A</span>';
                                        elseif  ($s === 'half_day') $val = '<span class="s-half">H</span>';
                                        else                        $val = '<span class="s-none">—</span>';
                                        break;
                                    default: $val = '—';
                                }
                            } else {
                                $val = $isWE
                                     ? '<span class="s-weekend">WO</span>'
                                     : '<span class="s-none">—</span>';
                            }
                        ?>
                        <td class="cell-day <?= $isWE ? 'weekend-cell' : '' ?>"><?= $val ?></td>
                        <?php endfor; ?>

                    </tr>
                    <?php endforeach; /* rows */ ?>

                    <?php endforeach; /* users */ endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- .dashboard-wrapper -->

<?php include '../includes/footer.php'; ?>

<style>
/* ── Table shell ──────────────────────────────────────────────────────────── */
.payroll-cal-table {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    min-width: 100%;
    font-size: 0.72rem;
    color: var(--text-primary);
}

/* ── Header ───────────────────────────────────────────────────────────────── */
.payroll-cal-table thead th {
    background: var(--sidebar-bg, #1a1a2e);
    color: var(--text-secondary);
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
    padding: 7px 4px;
    border-bottom: 1px solid var(--border-hi);
    border-right:  1px solid var(--border-hi);
    position: sticky;
    top: 0;
    z-index: 3;
}
.payroll-cal-table thead .subhead-row th {
    top: 33px;
    font-size: 0.62rem;
    padding: 3px 4px;
    font-weight: 500;
}
.col-day { min-width: 62px; }
.weekend { color: var(--rose-mid, #c2637a) !important; }

/* ── Fixed left columns ───────────────────────────────────────────────────── */
.col-fixed {
    position: sticky;
    background: var(--sidebar-bg, #1a1a2e);
    z-index: 2;
    border-right: 1px solid var(--border-hi);
    white-space: nowrap;
}
.col-empid   { left: 0;     min-width: 62px;  text-align: center; font-weight: 600; }
.col-empname { left: 62px;  min-width: 140px; font-weight: 500; }
.col-type    { left: 202px; min-width: 80px;  font-weight: 500; color: var(--text-secondary); font-size: 0.68rem; text-align: left; padding-left: 6px !important; }

thead .col-empid, thead .col-empname, thead .col-type {
    z-index: 4;
    background: var(--sidebar-bg, #1a1a2e);
}

tbody .col-empid, tbody .col-empname, tbody .col-type {
    background: var(--sidebar-bg, #1a1a2e);
}

/* ── Body cells ───────────────────────────────────────────────────────────── */
.payroll-cal-table tbody td {
    padding: 3px 4px;
    text-align: center;
    border-bottom: 1px solid var(--border-hi);
    border-right:  1px solid var(--border-hi);
    vertical-align: middle;
    white-space: nowrap;
}
.col-empid.col-fixed, .col-empname.col-fixed {
    padding: 4px 8px;
    text-align: left;
}
.col-empid.col-fixed { text-align: center; }

.emp-first-row td { border-top: 2px solid var(--border-hi); }
.emp-last-row  td { border-bottom: 2px solid rgba(194,99,122,0.3); }

/* ── Weekend tint ─────────────────────────────────────────────────────────── */
.weekend-cell { background: rgba(194,99,122,0.04); }

/* ── Status badges ────────────────────────────────────────────────────────── */
.s-present { display:inline-block; padding:1px 6px; border-radius:3px;
             background:rgba(46,125,82,0.14); color:#2e7d52; font-weight:700; }
.s-absent  { display:inline-block; padding:1px 6px; border-radius:3px;
             background:rgba(194,99,122,0.14); color:var(--rose-mid,#c2637a); font-weight:700; }
.s-half    { display:inline-block; padding:1px 6px; border-radius:3px;
             background:rgba(201,151,90,0.14); color:#b87a2a; font-weight:700; }
.s-weekend { display:inline-block; padding:1px 5px; border-radius:3px;
             background:rgba(100,100,120,0.08); color:var(--text-secondary); font-size:0.62rem; }
.s-none    { color: var(--text-secondary); }
</style>

<script>
$('#payroll').addClass('active');

function applyMonth(sel) {
    var parts = sel.value.split('-');
    document.getElementById('hYear').value  = parts[0];
    document.getElementById('hMonth').value = parseInt(parts[1], 10);
    document.getElementById('monthForm').submit();
}
</script>













<?php
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

// ── Determine selected month (default = last month) ───────────────────────────
$today    = new DateTime();
$lastMo   = (clone $today)->modify('-1 month');

$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval($lastMo->format('n'));
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval($lastMo->format('Y'));

// Guard: never allow current or future month
$selDate  = new DateTime("{$sel_year}-{$sel_month}-01");
$curFirst = new DateTime(date('Y-m-01'));
if ($selDate >= $curFirst) {
    $sel_month = intval($lastMo->format('n'));
    $sel_year  = intval($lastMo->format('Y'));
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);

// ── Employees ─────────────────────────────────────────────────────────────────
$users_q = mysqli_query($conn,
    "SELECT user_id, user_name FROM users
     WHERE dele_te='0' AND is_left='no' AND user_role='Employee'
     ORDER BY user_id ASC");
$users = [];
while ($u = mysqli_fetch_assoc($users_q)) $users[] = $u;

// ── Attendance for selected month ─────────────────────────────────────────────
$att_data = [];
$att_q = mysqli_query($conn,
    "SELECT user_id,
            DAY(attendance_date)  AS day,
            in_time, out_time,
            total_hours, status
     FROM   attendance
     WHERE  MONTH(attendance_date) = $sel_month
       AND  YEAR(attendance_date)  = $sel_year");
while ($a = mysqli_fetch_assoc($att_q)) {
    $att_data[$a['user_id']][$a['day']] = $a;
}

// ── Month selector options (past 24 months, most-recent first) ────────────────
$months_list = [];
$cursor = (clone $curFirst)->modify('-1 month');
for ($i = 0; $i < 24; $i++) {
    $months_list[] = [
        'month' => intval($cursor->format('n')),
        'year'  => intval($cursor->format('Y')),
        'label' => $cursor->format('F Y'),
    ];
    $cursor->modify('-1 month');
}

$sel_label = date('F Y', mktime(0, 0, 0, $sel_month, 1, $sel_year));
?>

<div class="dashboard-wrapper">

    <!-- Page header + month picker ──────────────────────────────────────────── -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h2 class="dash-section-title">Payroll</h2>
            <p class="dash-section-sub">Attendance records for all employees — <?= htmlspecialchars($sel_label) ?></p>
        </div>

        <form method="GET" action="payroll.php" id="monthForm" class="d-flex align-items-center" style="gap:8px;">
            <label style="font-size:0.83rem;color:var(--text-secondary);white-space:nowrap;">Select Month</label>
            <select name="" id="monthSelect" class="form-select form-select-sm"
                    style="min-width:160px;background:var(--card-bg);color:var(--text-primary);border:1px solid var(--border-hi);"
                    onchange="applyMonth(this)">
                <?php foreach ($months_list as $m):
                    $val = $m['year'] . '-' . str_pad($m['month'], 2, '0', STR_PAD_LEFT);
                    $cur = $sel_year  . '-' . str_pad($sel_month,  2, '0', STR_PAD_LEFT);
                ?>
                <option value="<?= $val ?>" <?= ($val === $cur) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['label']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="month" id="hMonth" value="<?= $sel_month ?>">
            <input type="hidden" name="year"  id="hYear"  value="<?= $sel_year ?>">
        </form>
    </div>

    <!-- Attendance grid ─────────────────────────────────────────────────────── -->
    <div class="content-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;overflow-y:auto;max-height:72vh;">
            <table class="payroll-cal-table" id="payrollCalTable">
                <thead>
                    <tr>
                        <th class="col-fixed col-empid" rowspan="2">Emp ID</th>
                        <th class="col-fixed col-empname" rowspan="2">Emp Name</th>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <th class="col-day"><?= $d ?></th>
                        <?php endfor; ?>
                    </tr>
                    <tr class="subhead-row">
                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                        ?>
                        <th class="col-dow <?= in_array($dow,['Sat','Sun']) ? 'weekend' : '' ?>"><?= $dow ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="<?= 2 + $days_in_month ?>" class="text-center py-4"
                            style="color:var(--text-secondary);">No employees found.</td>
                    </tr>
                    <?php else: foreach ($users as $u):
                        $uid  = $u['user_id'];
                        $rows = ['In Time', 'Out Time', 'Total Time', 'Status'];
                    ?>

                    <?php foreach ($rows as $ri => $rlabel): ?>
                    <tr class="emp-row <?= $ri===0 ? 'emp-first-row' : '' ?> <?= $ri===3 ? 'emp-last-row' : '' ?>">

                        <?php if ($ri === 0): ?>
                        <td class="col-fixed col-empid" rowspan="4"><?= htmlspecialchars($u['user_id']) ?></td>
                        <td class="col-fixed col-empname" rowspan="4"><?= htmlspecialchars($u['user_name']) ?></td>
                        <?php endif; ?>

                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $a   = $att_data[$uid][$d] ?? null;
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                            $date_str = sprintf('%04d-%02d-%02d', $sel_year, $sel_month, $d);
                            $week_of_month = ceil($d / 7);

                            $isWE = (
                                $dow === 'Sun' ||
                                ($dow === 'Sat' && in_array($week_of_month, [2, 4])) ||
                                in_array($date_str, $holiday_dates)
                            );

                            if ($a) {
                                switch ($ri) {
                                    case 0:
                                        $val = $a['in_time']    ? date('h:i A', strtotime($a['in_time']))    : '—';
                                        break;
                                    case 1:
                                        $val = $a['out_time']   ? date('h:i A', strtotime($a['out_time']))   : '—';
                                        break;
                                    case 2:
                                        $val = ($a['total_hours'] !== null && $a['total_hours'] !== '')
                                               ? $a['total_hours'].'h' : '—';
                                        break;
                                    case 3:
                                        $s = $a['status'];
                                        if      ($s === 'present')  $val = '<span class="s-present">P</span>';
                                        elseif  ($s === 'absent')   $val = '<span class="s-absent">A</span>';
                                        elseif  ($s === 'half_day') $val = '<span class="s-half">H</span>';
                                        else                        $val = '<span class="s-none">—</span>';
                                        break;
                                    default: $val = '—';
                                }
                            } else {
                                $val = $isWE
                                     ? '<span class="s-weekend">WO</span>'
                                     : '<span class="s-none">—</span>';
                            }
                        ?>
                        <td class="cell-day <?= $isWE ? 'weekend-cell' : '' ?>"><?= $val ?></td>
                        <?php endfor; ?>

                    </tr>
                    <?php endforeach; /* rows */ ?>

                    <?php endforeach; /* users */ endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- .dashboard-wrapper -->

<?php include '../includes/footer.php'; ?>

<style>
/* ── Table shell ──────────────────────────────────────────────────────────── */
.payroll-cal-table {
    border-collapse: separate;
    border-spacing: 0;
    width: max-content;
    min-width: 100%;
    font-size: 0.72rem;
    color: var(--text-primary);
}

/* ── Header ───────────────────────────────────────────────────────────────── */
.payroll-cal-table thead th {
    background: var(--sidebar-bg, #1a1a2e);
    color: var(--text-secondary);
    font-weight: 600;
    text-align: center;
    white-space: nowrap;
    padding: 7px 4px;
    border-bottom: 1px solid var(--border-hi);
    border-right:  1px solid var(--border-hi);
    position: sticky;
    top: 0;
    z-index: 3;
}
.payroll-cal-table thead .subhead-row th {
    top: 33px;
    font-size: 0.62rem;
    padding: 3px 4px;
    font-weight: 500;
}
.col-day { min-width: 62px; }
.weekend { color: var(--rose-mid, #c2637a) !important; }

/* ── Fixed left columns ───────────────────────────────────────────────────── */
.col-fixed {
    position: sticky;
    background: var(--card-bg);
    z-index: 2;
    border-right: 1px solid var(--border-hi);
    white-space: nowrap;
}
.col-empid   { left: 0;     min-width: 62px;  text-align: center; font-weight: 600; }
.col-empname { left: 62px;  min-width: 140px; font-weight: 500; }

thead .col-empid, thead .col-empname {
    z-index: 4;
    background: var(--sidebar-bg, #1a1a2e);
}

/* ── Body cells ───────────────────────────────────────────────────────────── */
.payroll-cal-table tbody td {
    padding: 3px 4px;
    text-align: center;
    border-bottom: 1px solid var(--border-hi);
    border-right:  1px solid var(--border-hi);
    vertical-align: middle;
    white-space: nowrap;
}
.col-empid.col-fixed, .col-empname.col-fixed {
    padding: 4px 8px;
    text-align: left;
}
.col-empid.col-fixed { text-align: center; }

.emp-first-row td { border-top: 2px solid var(--border-hi); }
.emp-last-row  td { border-bottom: 2px solid rgba(194,99,122,0.3); }

/* ── Weekend tint ─────────────────────────────────────────────────────────── */
.weekend-cell { background: rgba(194,99,122,0.04); }

/* ── Status badges ────────────────────────────────────────────────────────── */
.s-present { display:inline-block; padding:1px 6px; border-radius:3px;
             background:rgba(46,125,82,0.14); color:#2e7d52; font-weight:700; }
.s-absent  { display:inline-block; padding:1px 6px; border-radius:3px;
             background:rgba(194,99,122,0.14); color:var(--rose-mid,#c2637a); font-weight:700; }
.s-half    { display:inline-block; padding:1px 6px; border-radius:3px;
             background:rgba(201,151,90,0.14); color:#b87a2a; font-weight:700; }
.s-weekend { display:inline-block; padding:1px 5px; border-radius:3px;
             background:rgba(100,100,120,0.08); color:var(--text-secondary); font-size:0.62rem; }
.s-none    { color: var(--text-secondary); }
</style>

<script>
$('#payroll').addClass('active');

function applyMonth(sel) {
    var parts = sel.value.split('-');
    document.getElementById('hYear').value  = parts[0];
    document.getElementById('hMonth').value = parseInt(parts[1], 10);
    document.getElementById('monthForm').submit();
}
</script>




<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if(isset($_POST['action']) && $_POST['action'] == 'add') {
    if(
        empty($_POST['user_name']) ||
        empty($_POST['user_email']) ||
        empty($_POST['user_password']) ||
        empty($_POST['user_role']) ||
        empty($_FILES['add_profile']['name'])
    ) {
        echo "All fields are required.";
        exit();
    }

    $user_name = $_POST['user_name'];
    $user_email = $_POST['user_email'];
    $user_password = $_POST['user_password'];
    $user_role = $_POST['user_role'];

    $profile = $_FILES['add_profile']['name'];
    $profile_tmp = $_FILES['add_profile']['tmp_name'];
    move_uploaded_file($profile_tmp, "images/profiles/$profile");

    $created_by = $_SESSION['user_name'] ?? 'Unknown';

    $sql = "INSERT INTO users (user_name, user_email, password, user_role, profile, createdBy) VALUES ('$user_name', '$user_email', '$user_password', '$user_role', '$profile', '$created_by')";

    if(mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

}

// Replace the edit section (around line 28) with this:

if(isset($_POST['action']) && $_POST['action'] == 'edit') {
    $userId = intval($_POST['user_id']);

    $userName = trim($_POST['edit_user_name']);
    $userEmail = trim($_POST['edit_user_email']);
    $userPassword = trim($_POST['edit_user_password']);
    $userRole = trim($_POST['edit_user_role']);

    if(empty($userName) || empty($userEmail) || empty($userPassword) || empty($userRole)) {
        echo "All fields are required.";
        exit();
    }

    $getOldProfile = mysqli_query($conn, "SELECT profile FROM users WHERE user_id=$userId");
    $oldRow = mysqli_fetch_assoc($getOldProfile);
    if($oldRow){
        $oldProfile = $oldRow['profile'];
    } else {
        echo "User not found";
        exit();
    }

    if (!empty($_FILES['edit_profile']['name'])) {
        $profile = $_FILES['edit_profile']['name'];
        $tmp = $_FILES['edit_profile']['tmp_name'];
        move_uploaded_file($tmp, "images/profiles/" . $profile);
    } else {
        $profile = $oldProfile;
    }

    $updated_at = date("Y-m-d H:i:s");
    $updated_by = $_SESSION['user_name'] ?? 'Unknown';

    $sql = "UPDATE users SET 
                user_name='$userName', 
                user_email='$userEmail', 
                password='$userPassword', 
                user_role='$userRole', 
                profile='$profile', 
                updatedAt='$updated_at', 
                updatedBy='$updated_by'
            WHERE user_id=$userId";

    if(mysqli_query($conn, $sql)) {
        echo "User updated successfully.";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }

}

if(isset($_GET['action']) && $_GET['action'] == 'delete') {
    $user_id = $_GET['id'];

    if(isset($user_id)) {
        $sql = "UPDATE users SET dele_te = '1' WHERE user_id = '$user_id'";
        if(mysqli_query($conn, $sql)) {
            header("Location: pages/show_users.php");
            exit();
        } else {
            echo "Error deleting user: " . mysqli_error($conn);
        }
    }

}

?>




<?php
include 'includes/db.php';

if(isset($_POST['dept_id'])) {
    $dept_id = $_POST['dept_id'];

    $sql = "SELECT designation_id, designation_name 
            FROM designations 
            WHERE dept_id = '$dept_id' AND dele_te='0'";
            
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0) {
        echo '<option value="">Select Designation</option>';
        while($row = mysqli_fetch_assoc($result)) {
            echo '<option value="'.$row['designation_id'].'">'.$row['designation_name'].'</option>';
        }
    } else {
        echo '<option value="">No Designation Found</option>';
    }
}
?>



<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$preview = isset($_GET['preview']) && $_GET['preview'] == '1';

if(empty($id)) {
    die("Invalid offer ID.");
}

$sql = "SELECT offers.*, candidates.full_name,
        designations.designation_name, locations.location_name
        FROM offers
        LEFT JOIN candidates   ON offers.candidate_id   = candidates.candidate_id
        LEFT JOIN designations ON offers.designation_id = designations.designation_id
        LEFT JOIN locations    ON offers.location_id    = locations.location_id
        WHERE offer_id = '$id'";

$data = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if(!$data) {
    die("Offer not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['full_name']) ?> — Offer Letter</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 60px;
            color: #222;
            line-height: 1.7;
            background: #fff;
        }
        h2 {
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }
        .details-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .details-table td {
            padding: 6px 12px;
        }
        .details-table td:first-child {
            font-weight: bold;
            width: 200px;
        }
        .signature {
            margin-top: 60px;
        }
        @media print {
            body { padding: 30px; }
        }
    </style>
</head>
<body>

    <h2>Offer Letter</h2>

    <p>Date: <strong><?= htmlspecialchars(!empty($data['created_at']) ? date('d-m-Y', strtotime($data['created_at'])) : date('d-m-Y')) ?></strong></p>

    <p>Dear <strong><?= htmlspecialchars($data['full_name']) ?></strong>,</p>

    <p>
        We are pleased to offer you the position of
        <strong><?= htmlspecialchars($data['designation_name']) ?></strong> at our company.
        We were impressed with your background and believe you will be a valuable addition to our team.
    </p>

    <table class="details-table">
        <tr>
            <td>CTC</td>
            <td>: <?= htmlspecialchars($data['ctc']) ?></td>
        </tr>
        <tr>
            <td>Location</td>
            <td>: <?= htmlspecialchars($data['location_name']) ?></td>
        </tr>
        <tr>
            <td>Date of Joining</td>
            <td>: <?= htmlspecialchars($data['doj']) ?></td>
        </tr>
        <tr>
            <td>Status</td>
            <td>: <?= htmlspecialchars($data['status']) ?></td>
        </tr>
    </table>

    <p>
        Please confirm your acceptance of this offer by signing and returning a copy of this letter.
        We look forward to welcoming you to the team.
    </p>

    <div class="signature">
        <p>Regards,</p>
        <br><br>
        <p><strong>HR Team</strong></p>
    </div>

    <?php if(!$preview): ?>
    <script>
        window.print();
    </script>
    <?php endif; ?>

</body>
</html>



<?php
include '../includes/header.php';

$sql = "SELECT offers.*, candidates.full_name, candidates.candidate_id,
        designations.designation_name, locations.location_name FROM offers
        LEFT JOIN candidates   ON offers.candidate_id   = candidates.candidate_id
        LEFT JOIN designations ON offers.designation_id = designations.designation_id
        LEFT JOIN locations    ON offers.location_id    = locations.location_id
        WHERE offers.isdelete = '0'
        ORDER BY offers.offer_id DESC";
$result = mysqli_query($conn,$sql);

?>

    <div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Offer Management</h2>
                <p class="dash-section-sub">Choose whom to offer a position</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addOfferModal">
                <i class="fa fa-plus me-2"></i>Create Offer
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="offerTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Candidate</th>
                            <th>Designation</th>
                            <th>Location</th>
                            <th>CTC</th>
                            <th>DOJ</th>
                            <th>Status</th>
                            <th>Letter</th>
                            
                            <?php if($role == 'Super_admin') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr>
                                <td><?= $row['offer_id'] ?></td>
                                <td><?= $row['full_name'] ?></td>
                                <td><?= $row['designation_name'] ?></td>
                                <td><?= $row['location_name'] ?></td>
                                <td><?= $row['ctc'] ?></td>
                                <td><?= $row['doj'] ?></td>
                                <td>
                                    <span class="badge-rose"><?= $row['status'] ?></span>
                                </td>

                                <!-- Letter column: visible to all roles -->
                                <td>
                                    <?php if($row['status'] == 'Accepted') { ?>
                                        <a href="javascript:void(0)"
                                            class="btn-action btn-offer btn-view-letter"
                                            data-offer-id="<?= $row['offer_id'] ?>"
                                            data-candidate="<?= htmlspecialchars($row['full_name']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewLetterModal"
                                            title="View Offer Letter">
                                            <i class="fa fa-file"></i>
                                        </a>
                                    <?php } else { ?> - <?php } ?>
                                </td>

                                <?php if($role == 'Super_admin') { ?>
                                    <td class="text-center">
                                        <a href="javascript:void(0)" 
                                            class="btn-action btn-edit me-1"
                                            data-offer_id="<?= $row['offer_id'] ?>"
                                            data-candidate="<?= $row['full_name'] ?>"
                                            data-designation="<?= $row['designation_id'] ?>"
                                            data-location="<?= $row['location_id'] ?>"
                                            data-ctc="<?= $row['ctc'] ?>"
                                            data-doj="<?= $row['doj'] ?>"
                                            data-status="<?= $row['status'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editOfferModal">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                        <a href="../offers_db.php?action=delete&id=<?= $row['offer_id'] ?>" 
                                            class="btn-action btn-delete">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php }
                        }  ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<!-- ===== View Letter Modal ===== -->
<div class="modal fade" id="viewLetterModal" tabindex="-1" aria-labelledby="viewLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header">
                <h3 class="modal-title" id="viewLetterModalLabel">
                    <i class="fa fa-file-alt me-2"></i>
                    Offer Letter &mdash; <span id="letterCandidateName"></span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="flex: 1; overflow: hidden;">
                <iframe id="letterIframe"
                    src=""
                    style="width: 100%; height: 100%; border: none; min-height: 600px;"
                    title="Offer Letter Preview">
                </iframe>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn-rose" id="downloadLetterBtn">
                    <i class="fa fa-download me-2"></i> Download Letter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Add Offer Modal ===== -->
<div class="modal fade" id="addOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Offer</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addOfferForm" method="POST">
                    <input type="hidden" name = "action" value = "add">
                    <div class="mb-3">
                        <label>Candidate</label>
                        <select name="candidate" class="form-select" id="candidate">
                            <option value="">Select Candidate</option>
                            <?php 
                            $sql = "SELECT DISTINCT candidate_id, full_name FROM candidates WHERE status = 'Hired' ORDER BY candidate_id DESC";
                            $candidate_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($candidate_result) > 0){ 
                                while($candidate = mysqli_fetch_assoc($candidate_result)){  ?>
                                    <option value="<?= $candidate['candidate_id'] ?>"><?= $candidate['full_name'] ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No candidate available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Job</label>
                        <select name="job" class="form-select" id="job">
                            <option value="">Select Job</option>
                            <?php 
                            $sql = "SELECT DISTINCT job_id, job_title FROM jobs ORDER BY job_id DESC";
                            $job_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($job_result) > 0){ 
                                while($job = mysqli_fetch_assoc($job_result)){  ?>
                                    <option value="<?= $job['job_id'] ?>"><?= $job['job_title'] ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No job available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Designation</label>
                        <select name="designation_id" class="form-select" id="designation_id">
                            <option value="">Select Designation</option>
                            <?php
                            $sql = "SELECT designation_id, designation_name FROM designations ORDER BY designation_name ASC";
                            $row = mysqli_query($conn, $sql);
                            if($row && mysqli_num_rows($row) > 0) {
                                while($result = mysqli_fetch_assoc($row)) { ?>
                                    <option value="<?= $result['designation_id'] ?>"><?= htmlspecialchars($result['designation_name']) ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No designation available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <select name="location_id" class="form-select" id="location_id">
                            <option value="">Select Location</option>
                            <?php
                            $sql = "SELECT location_id, location_name FROM locations ORDER BY location_name ASC";
                            $loc_result = mysqli_query($conn, $sql);
                            if($loc_result && mysqli_num_rows($loc_result) > 0) {
                                while($loc = mysqli_fetch_assoc($loc_result)) { ?>
                                    <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No location available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>CTC</label>
                        <input type="text" class="form-control" id="ctc" name="ctc" placeholder="e.g. 5,00,000">
                    </div>
                    <div class="mb-3">
                        <label>Date of Joining</label>
                        <input type="date" class="form-control" id="doj" name="doj">
                    </div>
                    <div class="mb-3">
                        <label>Offer Date</label>
                        <input type="date" class="form-control" id="offer_date" name="offer_date">
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Select Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </form>               
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addOffer()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Edit Offer Modal ===== -->
<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Offer</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editOfferForm" method="POST">
                    <input type="hidden" name = "edit_offer_id" id = "edit_offer_id">
                    <div class="mb-3">
                        <label>Candidate</label>
                        <input type="text" class="form-control" id="edit_candidate_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Designation</label>
                        <select name="edit_designation_id" class="form-select" id="edit_designation_id">
                            <option value="">Select Designation</option>
                            <?php
                            $sql = "SELECT designation_id, designation_name FROM designations ORDER BY designation_name ASC";
                            $result = mysqli_query($conn, $sql);
                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { ?>
                                    <option value="<?= $row['designation_id'] ?>"><?= $row['designation_name'] ?></option>
                                <?php } } else { ?>
                                    <option value="">No designations available</option>
                                <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <select name="edit_location_id" class="form-select" id="edit_location_id">
                            <option value="">Select Location</option>
                            <?php
                            $sql = "SELECT location_id, location_name FROM locations ORDER BY location_name ASC";
                            $loc_result = mysqli_query($conn, $sql);
                            if($loc_result && mysqli_num_rows($loc_result) > 0) {
                                while($loc = mysqli_fetch_assoc($loc_result)) { ?>
                                    <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                                <?php }
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>CTC</label>
                        <input type="number" class="form-control" id="edit_ctc" name="edit_ctc">
                    </div>
                    <div class="mb-3">
                        <label>Date of Joining</label>
                        <input type="date" class="form-control" id="edit_doj" name="edit_doj">
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="edit_status" id="edit_status" class="form-select">
                            <option>Select Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Accepted">Accepted</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>   
                </form>               
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="editOffer()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $("#offers").addClass("active");

    $('#offerTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']]
    });

    // ===== View Letter Modal =====
    $(document).on("click", ".btn-view-letter", function () {
        var offerId   = $(this).data("offer-id");
        var candidate = $(this).data("candidate");

        // Load the letter in preview mode (no auto-print)
        var letterUrl = '../create_offer.php?id=' + offerId + '&preview=1';

        $('#letterCandidateName').text(candidate);
        $('#letterIframe').attr('src', letterUrl);
    });

    // Clear iframe when modal is closed
    $('#viewLetterModal').on('hidden.bs.modal', function () {
        $('#letterIframe').attr('src', '');
    });

    // Download button: trigger the browser's print/save-as-PDF on the iframe content
    $('#downloadLetterBtn').on('click', function () {
        var iframe = document.getElementById('letterIframe');
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    });

    // ===== Edit Modal =====
    $(document).on("click", ".btn-edit", function() {
        $("#edit_offer_id").val($(this).data("offer_id"));
        $("#edit_candidate_name").val($(this).data("candidate"));
        $("#edit_designation_id").val($(this).data("designation"));
        $("#edit_location_id").val($(this).data("location"));
        $("#edit_doj").val($(this).data("doj"));
        $("#edit_ctc").val($(this).data("ctc"));
        $("#edit_status").val($(this).data("status"));
    });

    $(document).on("click", ".btn-delete", function(e) {
        e.preventDefault();

        var link = $(this).attr("href");

        Swal.fire({
            title: 'Are you sure?',
            text: "This offer will be deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#c2637a',
            cancelButtonColor: '#f5d5db',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = link;
            }
        });
    });

    function addOffer() {
        var formData = new FormData(document.getElementById('addOfferForm'));

        $.ajax({
            url: '../offers_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.trim().toLowerCase() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Offer added successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response
                    });
                }
            }
        });
    }

    function editOffer() {
        var formData = new FormData(document.getElementById('editOfferForm'));
        formData.append('action', 'edit');

        $.ajax({
            url: '../offers_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.includes("successfully")) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Offer updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response
                    });
                }
            }
        });
    }

</script>