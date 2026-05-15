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
        
        // company rules: 9am-6:30pm shift
        $shift_start = strtotime("09:00:00");
        $shift_end   = strtotime("18:30:00");

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
            <tr style="font-size: 0.875rem;">
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
                    <tr style="font-size: 0.875rem;">
                        <td><strong>Employee ID</strong></td>
                        <td><?= $user_id ?></td>
                        <td><strong>Month</strong></td>
                        <td><?= date('F') ?></td>
                        <td><strong>Year</strong></td>
                        <td><?= date('Y') ?></td>
                    </tr>

                    <tr style="font-size: 0.875rem;">
                        <td><strong>Present Days</strong></td>
                        <td><?= $present ?></td>
                        <td><strong>Absent Days</strong></td>
                        <td><?= $absent ?></td>
                        <td><strong>Half Days</strong></td>
                        <td><?= $halfday ?></td>
                    </tr>

                    <tr style="font-size: 0.875rem;">
                        <td><strong>Total Work Hours</strong></td>
                        <td><?= sprintf("%d hr %d min", floor($total_hours), ($total_hours - floor($total_hours)) * 60) ?></td>
                        <td><strong>Total OT Hours</strong></td>
                        <td><?= sprintf("%d hr %d min", floor($total_ot), ($total_ot - floor($total_ot)) * 60) ?></td>
                        <td><strong>Late Days</strong></td>
                        <td><?= $late_days ?></td>
                    </tr>

                    <tr style="font-size: 0.875rem;">
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