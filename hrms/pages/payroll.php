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

$selDate = new DateTime("{$sel_year}-{$sel_month}-01");
$curFirst = new DateTime(date('Y-m-01'));
if ($selDate > $curFirst) {
    $sel_month = intval($lastMo->format('n'));
    $sel_year  = intval($lastMo->format('Y'));
}

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $sel_month, $sel_year);
$holiday_dates = [];
$hol_q = mysqli_query($conn, "SELECT holiday_date 
        FROM holidays WHERE MONTH(holiday_date) = '$sel_month'
        AND YEAR(holiday_date) = '$sel_year'
");

while ($h = mysqli_fetch_assoc($hol_q)) {
    $holiday_dates[] = $h['holiday_date'];
}

$total_working_days = 0;

for ($d = 1; $d <= $days_in_month; $d++) {

    $date_str = sprintf('%04d-%02d-%02d', $sel_year, $sel_month, $d);
    $day_name = date('D', strtotime($date_str));

    // Saturday number in month
    $week_of_month = ceil($d / 7);

    $is_sunday = ($day_name === 'Sun');

    $is_2nd_or_4th_saturday = (
        $day_name === 'Sat' &&
        in_array($week_of_month, [2, 4])
    );

    $is_company_holiday = in_array($date_str, $holiday_dates);

    if (!$is_sunday && !$is_2nd_or_4th_saturday && !$is_company_holiday) {
        $total_working_days++;
    }
}

$users_q = mysqli_query($conn,
    "SELECT u.user_id, u.user_name,
            d.designation_name,
            dept.dept_name
     FROM users u
     LEFT JOIN designations d ON u.designation_id = d.designation_id
     LEFT JOIN departments dept ON u.dept_id = dept.dept_id
     WHERE u.dele_te='0' AND u.is_left='no' 
     ORDER BY u.user_id ASC");

$users = [];
while ($u = mysqli_fetch_assoc($users_q)) $users[] = $u;

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

$salary_data = [];
$sal_q = mysqli_query($conn, "SELECT 
        sm.user_id,
        sm.basic,
        sm.hra,
        sm.special_allowance,
        sm.ta,
        sm.other_allowance
    FROM salary_master sm
    INNER JOIN (
        SELECT user_id, MAX(salary_id) AS max_id
        FROM salary_master
        WHERE isdelete = '0'
        GROUP BY user_id
    ) latest ON sm.user_id = latest.user_id AND sm.salary_id = latest.max_id
");

while ($s = mysqli_fetch_assoc($sal_q)) {

    $basic = floatval($s['basic']);
    $hra = floatval($s['hra']);
    $special = floatval($s['special_allowance']);
    $ta = floatval($s['ta']);
    $other = floatval($s['other_allowance']);

    $total = $basic + $hra + $special + $ta + $other;

    // same as salary master
    $pf = round($basic * 0.12);
    $esic = ($total > 0 && $total <= 21000) ? round($total * 0.0075, 2) : 0;
    $telephone = 400;
    $canteen = 0;
    $gross = $total;
    $net = $gross - $pf - $esic - $telephone - $canteen;

    $salary_data[intval($s['user_id'])] = [
        'basic' => $basic,
        'hra' => $hra,
        'special' => $special,
        'ta' => $ta,
        'other' => $other,
        'total' => $total,
        'pf' => $pf,
        'esic' => $esic,
        'telephone' => $telephone,
        'canteen' => $canteen,
        'gross' => $gross,
        'net' => $net
    ];
}

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

$loan_deduction_data = [];

$loan_month = sprintf('%04d-%02d', $sel_year, $sel_month);

$loan_q = mysqli_query($conn, "SELECT empid, SUM(final_deduction) AS loan_deduction
    FROM loan_deduction
    WHERE Month = '$loan_month'
    GROUP BY empid
");

while ($ld = mysqli_fetch_assoc($loan_q)) {
    $loan_deduction_data[intval($ld['empid'])] = floatval($ld['loan_deduction']);
}

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

    .payroll-cal-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        min-width: 100%;
        font-size: 0.72rem;
        color: var(--text-primary);
    }

    .payroll-cal-table th,
    .payroll-cal-table td {
        background-clip: padding-box;
    }

    /* ── thead base ── */
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

    /* ── sticky fixed columns — PART 1 FIX: theme-aware background + opaque text ── */
    .col-fixed {
        position: sticky;
        background: var(--bg-card) !important;
        color: var(--text-primary) !important;
        white-space: nowrap;
        border-right: 1px solid var(--border-hi);
        z-index: 20;
        box-shadow: 1px 0 0 var(--border-hi);
    }

    /* 1st sticky column */
    .col-empid {
        left: 0;
        min-width: 62px;
        text-align: center;
        font-weight: 600;
        z-index: 15;
    }

    /* 2nd sticky column */
    .col-empname {
        left: 62px;
        min-width: 140px;
        text-align: left;
        font-weight: 500;
        z-index: 15;
    }

    /* 3rd sticky column — Type; left updated: summary no longer in sticky group */
    .col-rowlabel {
        left: 202px;   /* 62 + 140 */
        min-width: 90px;
        text-align: left;
        font-weight: 600;
        z-index: 15;
    }

    /* sticky header cells stay topmost */
    thead .col-empid,
    thead .col-empname,
    thead .col-rowlabel {
        background: var(--sidebar-bg, #1a1a2e) !important;
        z-index: 30 !important;
    }

    /* ── body cells ── */
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

    .weekend-cell { background: rgba(194,99,122,0.04); }

    /* ── status badges ── */
    .s-present { display:inline-block; padding:1px 6px; border-radius:3px; background:rgba(46,125,82,0.14);   color:#2e7d52;                 font-weight:700; }
    .s-absent  { display:inline-block; padding:1px 6px; border-radius:3px; background:rgba(194,99,122,0.14); color:var(--rose-mid,#c2637a); font-weight:700; }
    .s-half    { display:inline-block; padding:1px 6px; border-radius:3px; background:rgba(201,151,90,0.14);  color:#b87a2a;                 font-weight:700; }
    .s-weekend { display:inline-block; padding:1px 5px; border-radius:3px; background:rgba(100,100,120,0.08); color:var(--text-secondary);   font-size:0.62rem; }
    .s-none    { color: var(--text-secondary); }

    /* ── salary / payroll columns at end of table ── */
    .col-sal-hd {
        min-width: 84px;
        text-align: center;
        white-space: nowrap;
        line-height: 1.35;
    }

    .col-sal {
        min-width: 84px;
        padding: 3px 6px !important;
        text-align: right;
        vertical-align: middle;
        white-space: nowrap;
        font-size: 0.70rem;
    }

    .col-sal-payable {
        font-weight: 700;
        color: #2e7d52;
    }
    body.theme-dark .col-sal-payable {
        color: #5dba88;
    }

    .col-sal-deduct {
        color: var(--rose-mid, #c2637a);
    }

    /* summary — moved to last, no longer sticky */
    .col-summary-end {
        min-width: 72px;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
        padding: 4px 6px !important;
    }
    .col-summary-end div {
        font-size: 0.68rem;
        color: var(--text-secondary);
        line-height: 1.65;
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
            <div style="width:100%;font-size:0.85rem;color:var(--text-secondary);margin-top:4px;">
                Total Working Days in <?= htmlspecialchars($sel_label) ?>:
                <strong style="color:var(--rose-mid);"><?= $total_working_days ?></strong>
            </div>
        </form>

    </div>

    <div class="content-card" style="padding:0;overflow:hidden;">
        <div style="overflow-x:auto;overflow-y:auto;max-height:72vh;">
            <table class="payroll-cal-table" id="payrollCalTable">
                <thead>
                    <tr style="font-size: 0.875rem;">
                        <th class="col-fixed col-empid" rowspan="2">Emp ID</th>
                        <th class="col-fixed col-empname" rowspan="2">Emp Name</th>
                        <th class="col-fixed col-rowlabel" rowspan="2">Type</th>
                        <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                        <th class="col-day"><?= $d ?></th>
                        <?php endfor; ?>
                        
                        <th class="col-sal-hd" rowspan="2">Summary</th>
                        <th class="col-sal-hd" rowspan="2">Net<br>Gross</th>
                        <th class="col-sal-hd" rowspan="2">Per Day</th>
                        <th class="col-sal-hd" rowspan="2">Deductions</th>
                        <th class="col-sal-hd" rowspan="2">PT</th>
                        <th class="col-sal-hd" rowspan="2">Loan / Advance</th>
                        <th class="col-sal-hd" rowspan="2">OT Pay</th>
                        <th class="col-sal-hd" rowspan="2">Payable</th>
                        <th class="col-sal-hd" rowspan="2">Action</th>
                    </tr>
                    <tr class="subhead-row" style="font-size: 0.875rem;">
                        <?php for ($d = 1; $d <= $days_in_month; $d++):
                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));
                        ?>
                        <th class="col-dow <?= in_array($dow,['Sat','Sun']) ? 'weekend' : '' ?>"><?= $dow ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr style="font-size: 0.875rem;">
                        <td colspan="<?= 12 + $days_in_month ?>" class="text-center py-4"
                            style="color:var(--text-secondary);">No employees found.</td>
                    </tr>
                    <?php $salary_report_rows = []; ?>
                    <?php else: foreach ($users as $u):
                        $uid  = $u['user_id'];
                        $present_days = 0;
                        $absent_days  = 0;
                        $half_days  = 0;
                        $total_ot_hours = 0;

                        for ($d = 1; $d <= $days_in_month; $d++) {
                            $a = $att_data[$uid][$d] ?? null;
                            if ($a && $a['total_hours'] !== null && $a['total_hours'] !== '') {
                                $day_total_hours = (float)$a['total_hours'];
                                $base_hours = 9.5;

                                if ($day_total_hours > $base_hours) {
                                    $total_ot_hours += ($day_total_hours - $base_hours);
                                }
                            }

                            $dow = date('D', mktime(0,0,0,$sel_month,$d,$sel_year));

                            $date_str = sprintf('%04d-%02d-%02d', $sel_year, $sel_month, $d);
                            $week_of_month = ceil($d / 7);

                            $is_sunday = ($dow === 'Sun');

                            $is_2nd_or_4th_saturday = (
                                $dow === 'Sat' &&
                                in_array($week_of_month, [2, 4])
                            );

                            $is_company_holiday = in_array($date_str, $holiday_dates);

                            if ($is_sunday || $is_2nd_or_4th_saturday || $is_company_holiday) {
                                continue;
                            }

                            if ($a) {
                                if ($a['status'] === 'present') {
                                    $present_days++;
                                } elseif ($a['status'] === 'absent') {
                                    $absent_days++;
                                } elseif ($a['status'] === 'half_day') {
                                    $half_days++;
                                }
                            } else {
                                $absent_days++;
                            }
                        }

                        $sal = $salary_data[$uid] ?? null;
                        $basic = $sal['basic'] ?? 0;
                        $total = $sal['total'] ?? 0;
                        $pf = $sal['pf'] ?? 0;
                        $esic = $sal['esic'] ?? 0;
                        $telephone = $sal['telephone'] ?? 0;
                        $canteen = $sal['canteen'] ?? 0;
                        $gross = $sal['gross'] ?? 0;
                        $net = $sal['net'] ?? 0;

                        // per day from NET SALARY
                        $per_day = ($total_working_days > 0 && $net > 0) ? ($net / $total_working_days) : 0;

                        // leave/attendance deduction from NET salary
                        $deduction = $per_day * ($absent_days + 0.5 * $half_days);

                        // PT from NET salary
                        if ($net <= 0) {
                            $pt = 0;
                        } elseif ($net <= 7500) {
                            $pt = 0;
                        } elseif ($net <= 10000) {
                            $pt = 175;
                        } else {
                            $pt = 200;
                        }

                        $loan_deduction = $loan_deduction_data[$uid] ?? 0;

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
                        
                        $final_absent = $deduct_absent + ($half_days * 0.5);

                        $leave_deduction = $final_absent * $per_day;

                        $total_deduction = $leave_deduction + $pt + $loan_deduction;

                        $ot_h = floor($total_ot_hours);
                        $ot_m = round(($total_ot_hours - $ot_h) * 60);

                        if ($ot_m == 60) {
                            $ot_h++;
                            $ot_m = 0;
                        }

                        $total_ot_display = sprintf('%02d:%02d', $ot_h, $ot_m) . 'h';

                        $working_days = $total_working_days;
                        $ot_pay = 0;

                        if ($basic > 0 && $working_days > 0 && $total_ot_hours > 0) {
                            $ot_pay = ($basic / $working_days / 8) * 2 * $total_ot_hours;
                        }

                        $payable = ($net > 0) ?
                                      max(0, $net - $total_deduction + $ot_pay)
                                      : 0; 

                        $salary_report_rows[] = [
                            'user_id'         => $uid,
                            'user_name'       => $u['user_name'],
                            'dept'            => $u['dept_name'] ?? '',
                            'desig'           => $u['designation_name'] ?? '',
                            'report_month'    => $sel_month,
                            'report_year'     => $sel_year,
                            'total_present'   => $present_days,
                            'total_absent'    => $final_absent,
                            'net_salary'      => $net,
                            'deduction'       => $deduction,
                            'ot_hours'        => $total_ot_display,
                            'ot_pay'          => $ot_pay,
                            'payable_salary'  => $payable
                        ];

                        $rows = ['In Time', 'Out Time', 'Total Time', 'Over Time', 'Status'];
                    ?>

                    <?php foreach ($rows as $ri => $rlabel): ?>
                    <tr class="emp-row <?= $ri===0 ? 'emp-first-row' : '' ?> <?= $ri===4 ? 'emp-last-row' : '' ?>" style="font-size: 0.875rem;">

                        <?php if ($ri === 0): ?>
                        <td class="col-fixed col-empid" rowspan="5"><?= htmlspecialchars($u['user_id']) ?></td>
                        <td class="col-fixed col-empname" rowspan="5"><?= htmlspecialchars($u['user_name']) ?></td>
                        <?php endif; ?>

                        <td class="col-fixed col-rowlabel"><?= htmlspecialchars($rlabel) ?></td>

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
                                        $val = $a['in_time']    ? date('h:i A', strtotime($a['in_time'])) : '—';
                                        break;
                                    case 1:
                                        $val = $a['out_time']   ? date('h:i A', strtotime($a['out_time'])) : '—';
                                        break;
                                    case 2:
                                        $val = ($a['total_hours'] !== null && $a['total_hours'] !== '')
                                               ? $a['total_hours'].'h' : '—';
                                        break;
                                    case 3:
                                        if ($a['total_hours'] !== null && $a['total_hours'] !== '') {
                                            $totalHours = (float)$a['total_hours'];
                                            $baseHours  = 9.5; // 9 hr 30 min

                                            if ($totalHours > $baseHours) {
                                                $otHours = $totalHours - $baseHours;

                                                $otH = floor($otHours);
                                                $otM = round(($otHours - $otH) * 60);

                                                if ($otM == 60) {
                                                    $otH++;
                                                    $otM = 0;
                                                }

                                                $val = sprintf('%02d:%02d', $otH, $otM) . 'h';
                                            } else {
                                                $val = '—';
                                            }
                                        } else {
                                            $val = '—';
                                        }
                                        break;
                                    case 4:
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
                                    if ($ri === 4) {
                                        $val = '<span class="s-absent">A</span>';
                                    } else {
                                        $val = '<span class="s-none">—</span>';
                                    }
                                }
                                // $val = $isWE
                                //      ? '<span class="s-weekend">WO</span>'
                                //      : '<span class="s-none">—</span>';
                            }
                        ?>
                        <td class="cell-day <?= $isWE ? 'weekend-cell' : '' ?>"><?= $val ?></td>
                        <?php endfor; ?>

                        <?php if ($ri === 0): ?>

                        <td class="col-summary-end" rowspan="5">
                            <div>P:&nbsp;<?= $present_days ?></div>
                            <div>A:&nbsp;<?= $absent_days ?></div>
                            <div>H:&nbsp;<?= $half_days ?></div>
                            <div>OT:&nbsp;<?= $total_ot_display ?></div>
                        </td>

                        <td class="col-sal" rowspan="5">
                            <?= $net > 0
                                ? '&#8377;'.number_format($net, 0)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal" rowspan="5">
                            <?= $per_day > 0
                                ? '&#8377;'.number_format($per_day, 2)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal-hd" rowspan="5">
                            <?= $deduction > 0
                                ? '&#8377;'.number_format($deduction, 2)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal col-sal-deduct" rowspan="5">
                            <?= $pt > 0
                                ? '&#8377;'.number_format($pt, 0)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal col-sal-deduct" rowspan="5">
                            <?= $loan_deduction > 0
                                ? '&#8377;'.number_format($loan_deduction, 2)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal col-sal-ot" rowspan="5">
                            <?= $ot_pay > 0
                                ? '&#8377;'.number_format($ot_pay, 2)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td class="col-sal col-sal-payable" rowspan="5">
                            <?= $net > 0
                                ? '&#8377;'.number_format($payable, 0)
                                : '<span style="color:var(--text-secondary)">&mdash;</span>' ?>
                        </td>

                        <td rowspan="5">
                            <a href="javascript:void(0)" 
                            class="btn-action btn-offer btn-view-payslip"
                            data-user="<?= $uid ?>"
                            data-name="<?= htmlspecialchars($u['user_name']) ?>"
                            data-month="<?= $sel_label ?>"
                            data-gross="<?= $gross ?>"
                            data-net="<?= $net ?>"
                            data-payable="<?= $payable ?>"
                            data-bs-toggle="modal" 
                            data-bs-target="#viewPayslipModal">
                                <i class="fa fa-file"></i>
                            </a>
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

<?php
if (!empty($salary_report_rows)) {
    foreach ($salary_report_rows as $sr) {

        $user_id        = intval($sr['user_id']);
        $user_name      = mysqli_real_escape_string($conn, $sr['user_name']);
        $dept           = mysqli_real_escape_string($conn, $sr['dept']);
        $desig          = mysqli_real_escape_string($conn, $sr['desig']);
        $report_month   = intval($sr['report_month']);
        $report_year    = intval($sr['report_year']);
        $total_present  = floatval($sr['total_present']);
        $total_absent   = floatval($sr['total_absent']);
        $net_salary   = floatval($sr['net_salary']);
        $deduction      = floatval($sr['deduction']);
        $payable_salary = floatval($sr['payable_salary']);
        $ot_hours       = floatval($sr['ot_hours']);
        $ot_pay         = floatval($sr['ot_pay']);

        mysqli_query($conn, "INSERT INTO salary_reports (
                user_id, user_name, dept, desig,
                report_month, report_year,
                total_present, total_absent, ot_hours, ot_pay,
                net_salary, deduction, payable_salary
            ) VALUES (
                '$user_id', '$user_name', '$dept', '$desig',
                '$report_month', '$report_year',
                '$total_present', '$total_absent', '$ot_hours', '$ot_pay',
                '$net_salary', '$deduction', '$payable_salary'
            )
            ON DUPLICATE KEY UPDATE
                user_name = VALUES(user_name),
                dept = VALUES(dept),
                desig = VALUES(desig),
                total_present = VALUES(total_present),
                total_absent = VALUES(total_absent),
                net_salary = VALUES(net_salary),
                deduction = VALUES(deduction),
                ot_hours = VALUES(ot_hours),
                ot_pay = VALUES(ot_pay),
                payable_salary = VALUES(payable_salary)
        ");
    }
}
?>

<?php include '../includes/footer.php'; ?>

<!-- View Payslip Modal  -->
<div class="modal fade" id="viewPayslipModal" tabindex="-1" aria-labelledby="viewPayslipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header">
                <h3 class="modal-title" id="viewPayslipModalLabel">
                    <i class="fa fa-file-alt me-2"></i>
                    Payslip &mdash; <span id="payslipEmployeeName"></span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="flex: 1; overflow: hidden;">
                <iframe id="payslipIframe"
                    src=""
                    style="width: 100%; height: 100%; border: none; min-height: 600px;"
                    title="Payslip Preview">
                </iframe>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn-rose" id="downloadPayslipBtn">
                    <i class="fa fa-download me-2"></i> Download Payslip    
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $('#payroll').addClass('active');

    function applyMonth(sel) {
        var parts = sel.value.split('-');
        document.getElementById('hYear').value  = parts[0];
        document.getElementById('hMonth').value = parseInt(parts[1], 10);
        document.getElementById('monthForm').submit();
    }

    $(document).on('click', '.btn-view-payslip', function() {
        var userId = $(this).data('user');
        var userName = $(this).data('name');
        var month = $(this).data('month');
        var gross = $(this).data('gross');
        var payable = $(this).data('payable');

        var letterUrl = 'payslip.php?user_id=' + userId + 
                        '&month=' + encodeURIComponent(month) + 
                        '&gross=' + gross + 
                        '&payable=' + payable;

        $('#payslipEmployeeName').text(userName + ' — ' + month);
        $('#payslipIframe').attr('src', letterUrl);
    });
    $('#viewPayslipModal').on('hidden.bs.modal', function () {
        $('#payslipIframe').attr('src', '');
    });
    $('#downloadPayslipBtn').on('click', function() {
        var iframe = document.getElementById('payslipIframe');
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    });

</script>
