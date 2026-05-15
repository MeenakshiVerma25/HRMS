<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if (!in_array($_SESSION['user_role'] ?? '', ['Super_admin', 'HR_admin'])) {
    echo "Access denied.";
    exit();
}

$user_id = intval($_GET['user_id'] ?? 0);
$month_label = trim($_GET['month'] ?? '');
$gross = floatval($_GET['gross'] ?? 0);
$payable = floatval($_GET['payable'] ?? 0);

if ($user_id <= 0) {
    echo "Invalid user.";
    exit();
}

$user_q = mysqli_query($conn, "SELECT u.user_id, u.user_name, u.user_email, d.designation_name, l.location_name
    FROM users u
    LEFT JOIN designations d ON u.designation_id = d.designation_id
    LEFT JOIN locations l ON u.location_id = l.location_id
    WHERE u.user_id = '$user_id'
    LIMIT 1 ");

$user = mysqli_fetch_assoc($user_q);

if (!$user) {
    echo "Employee not found.";
    exit();
}

$salary_q = mysqli_query($conn, "SELECT sm.*
    FROM salary_master sm
    INNER JOIN (
        SELECT user_id, MAX(salary_id) AS max_id
        FROM salary_master
        WHERE isdelete = '0'
        GROUP BY user_id
    ) latest ON sm.user_id = latest.user_id AND sm.salary_id = latest.max_id
    WHERE sm.user_id = '$user_id'
    LIMIT 1
");

$salary = mysqli_fetch_assoc($salary_q);

$basic = floatval($salary['basic'] ?? 0);
$hra = floatval($salary['hra'] ?? 0);
$special = floatval($salary['special_allowance'] ?? 0);
$ta = floatval($salary['ta'] ?? 0);
$other = floatval($salary['other_allowance'] ?? 0);

$total = $basic + $hra + $special + $ta + $other;

$pf = round($basic * 0.12);
$esic = ($total > 0 && $total <= 21000) ? round($total * 0.0075, 2) : 0;
$telephone = 400;
$canteen = 0;

$gross = $total - $pf;
$net = $gross - $esic - $telephone - $canteen;

if ($net <= 0) {
    $pt = 0;
} elseif ($net <= 7500) {
    $pt = 0;
} elseif ($net <= 10000) {
    $pt = 175;
} else {
    $pt = 200;
}

$total_deduction = max(0, $net - $payable);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f8f9fa;
            margin:0;
            padding:20px;
            color:#222;
        }
        .payslip{
            max-width:900px;
            margin:0 auto;
            background:#fff;
            border:1px solid #ddd;
            padding:24px;
        }
        .top{
            display:flex;
            justify-content:space-between;
            align-items:flex-start;
            border-bottom:2px solid #333;
            padding-bottom:14px;
            margin-bottom:18px;
        }
        .company h2,
        .title h3{
            margin:0 0 6px;
        }
        .meta{
            width:100%;
            border-collapse:collapse;
            margin-bottom:18px;
        }
        .meta td{
            border:1px solid #ddd;
            padding:8px 10px;
            font-size:14px;
        }
        .salary-table{
            width:100%;
            border-collapse:collapse;
            margin-top:8px;
        }
        .salary-table th,
        .salary-table td{
            border:1px solid #ddd;
            padding:10px;
            font-size:14px;
        }
        .salary-table th{
            background:#f1f1f1;
            text-align:left;
        }
        .text-right{
            text-align:right;
        }
        .summary{
            margin-top:20px;
            width:320px;
            margin-left:auto;
            border-collapse:collapse;
        }
        .summary td{
            border:1px solid #ddd;
            padding:10px;
            font-size:14px;
        }
        .summary .final{
            font-weight:700;
            background:#f6f6f6;
        }
        .note{
            margin-top:20px;
            font-size:12px;
            color:#666;
        }
        @media print{
            body{
                background:#fff;
                padding:0;
            }
            .payslip{
                border:none;
                max-width:100%;
            }
        }
    </style>
</head>
<body>
    <div class="payslip">
        <div class="top">
            <div class="company">
                <h2>Suyog Electricals Limited</h2>
                <div>Vadodara</div>
            </div>
            <div class="title">
                <h3>Salary Payslip</h3>
                <div><strong>Month:</strong> <?= htmlspecialchars($month_label) ?></div>
            </div>
        </div>

        <table class="meta">
            <tr>
                <td><strong>Employee ID</strong></td>
                <td><?= htmlspecialchars($user['user_id']) ?></td>
                <td><strong>Employee Name</strong></td>
                <td><?= htmlspecialchars($user['user_name']) ?></td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td><?= htmlspecialchars($user['user_email'] ?? '-') ?></td>
                <td><strong>Designation</strong></td>
                <td><?= htmlspecialchars($user['designation_name'] ?? '-') ?></td>
            </tr>
            <tr>
                <td><strong>Location</strong></td>
                <td><?= htmlspecialchars($user['location_name'] ?? '-') ?></td>
                <td><strong>Payslip Month</strong></td>
                <td><?= htmlspecialchars($month_label) ?></td>
            </tr>
        </table>

        <table class="salary-table">
            <thead>
                <tr>
                    <th>Earnings</th>
                    <th class="text-right">Amount</th>
                    <th>Deductions</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic</td>
                    <td class="text-right"><?= number_format($basic, 2) ?></td>
                    <td>PF</td>
                    <td class="text-right"><?= number_format($pf, 2) ?></td>
                </tr>
                <tr>
                    <td>HRA</td>
                    <td class="text-right"><?= number_format($hra, 2) ?></td>
                    <td>ESIC</td>
                    <td class="text-right"><?= number_format($esic, 2) ?></td>
                </tr>
                <tr>
                    <td>Special Allowance</td>
                    <td class="text-right"><?= number_format($special, 2) ?></td>
                    <td>PT</td>
                    <td class="text-right"><?= number_format($pt, 2) ?></td>
                </tr>
                <tr>
                    <td>Other Allowance</td>
                    <td class="text-right"><?= number_format($other, 2) ?></td>
                    <td>Other Deductions</td>
                    <td class="text-right"><?= number_format(max(0, $total_deduction - $pf - $esic - $pt), 2) ?></td>
                </tr>
                <tr>
                    <th>Gross Salary</th>
                    <th class="text-right"><?= number_format($gross, 2) ?></th>
                    <th>Total Deductions</th>
                    <th class="text-right"><?= number_format($total_deduction, 2) ?></th>
                </tr>
            </tbody>
        </table>

        <table class="summary">
            <tr>
                <td><strong>Net Salary</strong></td>
                <td class="text-right"><?= number_format($net, 2) ?></td>
            </tr>
            <tr>
                <td><strong>Net Payable</strong></td>
                <td class="text-right final"><?= number_format($payable, 2) ?></td>
            </tr>
        </table>

        <div class="note">
            This is a computer-generated payslip.
        </div>
    </div>
</body>
</html>