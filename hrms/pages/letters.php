<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Accept GET (resignation / relieving) and POST (fff form submission) */
$action = $_REQUEST['action'] ?? '';
$id     = intval($_REQUEST['id'] ?? 0);

if (!$id || !in_array($action, ['resignation', 'relieving', 'fff', 'experience'])) {
    echo "<div style='padding:2rem;text-align:center;color:red;font-family:sans-serif;'>Invalid request.</div>";
    exit();
}


$sql = "SELECT r.resignation_id,
               r.user_id,
               r.resignation_date,
               r.last_working_date,
               r.status,
               r.doj                                       AS r_doj,
               COALESCE(u.user_name,  r.user_name)         AS user_name,
               COALESCE(u.doj,        r.doj)               AS doj,
               COALESCE(d.designation_name, r.desig)       AS desig,
               COALESCE(dept.dept_name,     r.dept)        AS dept,
               u.ctc,
               sm.basic,
               sm.hra,
               sm.special_allowance,
               sm.ta,
               sm.other_allowance
        FROM resignation r
        LEFT JOIN users        u    ON r.user_id          = u.user_id
        LEFT JOIN designations d    ON u.designation_id   = d.designation_id
        LEFT JOIN departments  dept ON d.dept_id          = dept.dept_id
        LEFT JOIN salary_master sm  ON r.user_id          = sm.user_id
                                   AND sm.isdelete        = '0'
        WHERE r.resignation_id = $id
        ORDER BY sm.salary_id DESC
        LIMIT 1";

$row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
if (!$row) {
    echo "<div style='padding:2rem;font-family:sans-serif;'>Record not found.</div>";
    exit();
}

/* ── Helper functions ───────────────────────────────────────────────── */
function fmt($d, $format = 'd/m/Y') { return $d ? date($format, strtotime($d)) : '—'; }
function fmtDot($d)  { return $d ? date('d.m.Y', strtotime($d)) : '—'; }
function fmtLong($d) { return $d ? date('d F Y', strtotime($d)) : '—'; }
function money($v)   { return number_format(floatval($v), 2); }

/* ── Tenure ─────────────────────────────────────────────────────────── */
function calcTenure($doj, $lwd) {
    if (!$doj || !$lwd) return ['text' => '—', 'years' => 0];
    $d1   = new DateTime($doj);
    $d2   = new DateTime($lwd);
    $diff = $d1->diff($d2);
    return [
        'text'  => $diff->y . ' Years, ' . $diff->m . ' Months, ' . $diff->d . ' Days',
        'years' => $diff->y
    ];
}

$doj_raw = $row['doj'];
$lwd_raw = $row['last_working_date'];
$res_raw = $row['resignation_date'];
$tenure  = calcTenure($doj_raw, $lwd_raw);

/* ── Notice period (days) ───────────────────────────────────────────── */
$notice_days = 30;
if ($res_raw && $lwd_raw) {
    $d1          = new DateTime($res_raw);
    $d2          = new DateTime($lwd_raw);
    $notice_days = $d1->diff($d2)->days;
}


$earn_keys = ['salary_payable','bonus','leave_encashment','gratuity',
              'ex_gratia','performance_payout','other_earnings'];
$ded_keys  = ['notice_pay_recovery','hip_deduction','loan_recovery',
              'asset_recovery','mobile_recovery','tds',
              'other_documents','other_deductions'];

$fff = [];

/* Financial fields */
foreach (array_merge($earn_keys, $ded_keys) as $f) {
    $fff[$f] = floatval($_POST[$f] ?? 0);
}

/* Text / select fields */
$fff['exit_type']          = htmlspecialchars($_POST['exit_type']          ?? 'Resigned');
$fff['reason_for_leaving'] = htmlspecialchars($_POST['reason_for_leaving'] ?? '—');
$fff['payment_mode']       = htmlspecialchars($_POST['payment_mode']       ?? 'Bank Transfer');
$fff['payment_ref']        = htmlspecialchars($_POST['payment_ref']        ?? 'NA');
$fff['pending_clearances'] = htmlspecialchars($_POST['pending_clearances'] ?? 'NA');
$fff['fff_remarks']        = htmlspecialchars($_POST['fff_remarks']        ?? 'NA');
$fff['fff_statement_date'] = htmlspecialchars($_POST['fff_statement_date'] ?? date('Y-m-d'));

/* Totals */
$total_earn = array_sum(array_map(fn($f) => $fff[$f], $earn_keys));
$total_ded  = array_sum(array_map(fn($f) => $fff[$f], $ded_keys));
$net_pay    = $total_earn - $total_ded;

/* ── Experience Letter values (from POST) ─────────────────────────── */
$exp = [];
if ($action === 'experience') {
    $exp['salary_payable']      = floatval($_POST['salary_payable']      ?? 0);
    $exp['gratuity']            = floatval($_POST['gratuity']            ?? 0);
    $exp['ex_gratia']           = floatval($_POST['ex_gratia']           ?? 0);
    $exp['performance_payout']  = floatval($_POST['performance_payout']  ?? 0);
    $exp['payment_mode']        = htmlspecialchars($_POST['payment_mode']   ?? 'Bank Transfer');
    $exp['payment_ref']         = htmlspecialchars($_POST['payment_ref']    ?? 'NA');
    $exp['statement_date']      = htmlspecialchars($_POST['statement_date'] ?? date('Y-m-d'));
    $exp['net_settlement'] = $exp['salary_payable'] + $exp['gratuity']
                           + $exp['ex_gratia']      + $exp['performance_payout'];
}

/* ── Company info ───────────────────────────────────────────────────── */
$company       = 'Suyog Electricals Limited';
$company_short = 'Suyog Electricals LTD';
$hr_dept       = 'HR Department';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>
<?php echo match($action) {
    'resignation' => 'Resignation Acceptance Letter',
    'relieving'   => 'Relieving Letter',
    'fff'         => 'Full & Final Settlement',
    'experience'  => 'Experience Letter',
    default       => 'Letter'
}; ?> — <?= htmlspecialchars($row['user_name']) ?>
</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Times New Roman', Times, serif;
    background: #e8e8e8;
    color: #1a1a1a;
    font-size: 13pt;
    line-height: 1.6;
  }

  /* Toolbar */
  .toolbar {
    position: fixed; top: 0; left: 0; right: 0;
    background: #1a1a2e;
    padding: 10px 20px;
    display: flex; gap: 10px; align-items: center;
    z-index: 9999;
    box-shadow: 0 2px 8px rgba(0,0,0,.4);
  }
  .toolbar h3 { color: #fff; flex: 1; font-size: 15px; font-family: Arial, sans-serif; font-weight: 600; }
  .btn-tool {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 16px; border-radius: 6px; border: none; cursor: pointer;
    font-family: Arial, sans-serif; font-size: 13px; font-weight: 600;
    transition: filter .2s;
  }
  .btn-tool:hover { filter: brightness(1.12); }
  .btn-print  { background: #e8344a; color: #fff; }
  .btn-pdf    { background: #2563eb; color: #fff; }
  .btn-back   { background: #4b5563; color: #fff; }

  /* Page */
  .page-wrap  { margin: 72px auto 40px; max-width: 800px; }
  .letter-page {
    background: #fff;
    padding: 60px 65px 70px;
    box-shadow: 0 4px 30px rgba(0,0,0,.15);
    min-height: 297mm;
  }

  /* Letterhead */
  .letterhead { text-align: center; border-bottom: 3px double #1a1a2e; padding-bottom: 14px; margin-bottom: 30px; }
  .letterhead .company-name { font-size: 22pt; font-weight: 700; letter-spacing: 1px; color: #1a1a2e; font-family: Georgia, serif; }
  .letterhead .company-sub  { font-size: 10.5pt; color: #555; margin-top: 2px; font-family: Arial, sans-serif; }

  /* Letter body */
  .date-line   { text-align: right; margin-bottom: 20px; font-size: 12pt; }
  .to-block    { margin-bottom: 18px; }
  .to-block p  { margin: 0; }
  .subject-line { font-weight: 700; margin-bottom: 18px; text-decoration: underline; font-size: 12.5pt; }
  .letter-body p { margin-bottom: 14px; text-align: justify; }
  .sign-block  { margin-top: 48px; }
  .sign-block .sign-row { display: flex; justify-content: space-between; margin-top: 50px; }
  .sign-col    { text-align: center; }
  .sign-col .sign-line  { border-top: 1px solid #555; width: 160px; margin: 0 auto 4px; }
  .sign-col p  { font-size: 11pt; color: #333; margin: 0; }
  .sign-col .title { font-weight: 700; font-size: 11.5pt; }

  /* F&F */
  .fff-title    { text-align: center; font-size: 17pt; font-weight: 700; letter-spacing: 2px; margin-bottom: 4px; font-family: Arial, sans-serif; }
  .fff-subtitle { text-align: center; font-size: 11pt; color: #444; font-family: Arial, sans-serif; margin-bottom: 20px; }
  .info-table   { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11pt; font-family: Arial, sans-serif; }
  .info-table th, .info-table td { border: 1px solid #bbb; padding: 5px 9px; }
  .info-table th { background: #f0f0f0; font-weight: 600; width: 22%; }
  .section-header { background: #1a1a2e; color: #fff; font-weight: 700; font-family: Arial, sans-serif; font-size: 11pt; padding: 4px 9px; text-transform: uppercase; letter-spacing: 1px; }
  .earn-ded-table { width: 100%; border-collapse: collapse; font-size: 11pt; font-family: Arial, sans-serif; margin-bottom: 16px; }
  .earn-ded-table th, .earn-ded-table td { border: 1px solid #ccc; padding: 5px 9px; }
  .earn-ded-table th { background: #f5f5f5; text-align: center; font-weight: 700; }
  .earn-ded-table td:last-child { text-align: right; }
  .earn-ded-table .total-row td { font-weight: 700; background: #f0f0f0; }
  .summary-table { width: 60%; margin: 0 auto 16px; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11.5pt; }
  .summary-table td { border: 1px solid #ccc; padding: 7px 12px; }
  .summary-table .net-row td { font-weight: 700; background: #e8f4e8; font-size: 12.5pt; }
  .declaration-box { border: 1px solid #ccc; padding: 10px 14px; font-size: 10.5pt; font-family: Arial, sans-serif; margin-bottom: 16px; color: #333; }
  .approvers-table { width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11pt; }
  .approvers-table th, .approvers-table td { border: 1px solid #ccc; padding: 7px 10px; text-align: center; }
  .approvers-table th { background: #f0f0f0; font-weight: 700; }

  @media print {
    body { background: #fff; }
    .toolbar { display: none !important; }
    .page-wrap { margin: 0; max-width: 100%; }
    .letter-page { box-shadow: none; padding: 20mm 22mm; min-height: auto; }
    @page { margin: 15mm; size: A4; }
  }
</style>
</head>
<body>

<div class="toolbar">
    <h3>
        <?= match($action) {
            'resignation' => '📄 Resignation Acceptance Letter',
            'relieving'   => '📋 Relieving Letter',
            'fff'         => '💰 Full & Final Settlement',
            'experience'  => '🎓 Experience Letter',
            default       => 'Letter'
        } ?> — <?= htmlspecialchars($row['user_name']) ?> (ID: <?= $row['user_id'] ?>)
    </h3>
    <button class="btn-tool btn-back"  onclick="window.history.back()">← Back</button>
    <button class="btn-tool btn-print" onclick="window.print()">🖨️ Print</button>
    <button class="btn-tool btn-pdf"   onclick="downloadPDF()">⬇️ Download PDF</button>
</div>

<div class="page-wrap">
<div class="letter-page" id="letter-content">

<?php 


if ($action === 'resignation'): ?>

    <div class="letterhead">
        <div class="company-name"><?= $company ?></div>
        <div class="company-sub">HR Department</div>
    </div>

    <div class="date-line"><?= fmtDot($lwd_raw) ?></div>

    <div class="to-block">
        <p>To,</p>
        <p><?= htmlspecialchars($row['user_name']) ?></p>
    </div>

    <div class="subject-line">Subject: Resignation acceptance letter</div>

    <div class="letter-body">
        <p>This letter acknowledges and accepts your resignation letter dated
        <strong><?= fmt($res_raw) ?></strong>, in which you expressed your intent to resign from your
        position as <strong><?= htmlspecialchars($row['desig']) ?></strong> at
        <strong><?= $company ?></strong>, effective <strong><?= fmt($lwd_raw) ?></strong>.
        In accordance with your notice period of <strong><?= $notice_days ?> days</strong>, during which
        you will hand over your work to the concerned person.</p>

        <p>We express our appreciation for your contributions and dedication during your time at
        <strong><?= $company ?></strong>. Your hard work and commitment have been valuable to our team,
        and your absence will be felt. We respect your decision and wish you the best in your future
        endeavours.</p>

        <p>In accordance with the company's policies, we will process your resignation and take care of
        the necessary administrative and HR procedures. This will include the return of any company
        property, the settlement of any outstanding dues, and the completion of any exit interviews or
        formalities required.</p>

        <p>Wishing you all the best in your future endeavours.</p>
    </div>

    <div class="sign-block">
        <div class="sign-row">
            <div class="sign-col">
                <div class="sign-line"></div>
                <p class="title">Authorized Signatory,</p>
                <p><?= $hr_dept ?></p>
                <p><?= $company ?></p>
            </div>
        </div>
    </div>

<?php 


elseif ($action === 'relieving'): ?>

    <div class="letterhead">
        <div class="company-name"><?= $company ?></div>
        <div class="company-sub">HR Department</div>
    </div>

    <div style="text-align:center;font-size:16pt;font-weight:700;text-decoration:underline;margin-bottom:20px;letter-spacing:1px;">
        RELIEVING LETTER
    </div>

    <div class="date-line"><?= fmtDot($lwd_raw) ?></div>

    <div class="to-block">
        <p>To,</p>
        <p><?= htmlspecialchars($row['user_name']) ?>,</p>
    </div>

    <div class="subject-line">Subject: Relieving Letter</div>

    <div class="letter-body">
        <p>This is in furtherance to your resignation letter on <strong><?= fmtDot($res_raw) ?></strong>
        wherein you had requested to be relieved from your services on <strong><?= fmtDot($lwd_raw) ?></strong>.
        We wish to inform you that your resignation has been accepted and you are being relieved from
        the position as <strong>"<?= htmlspecialchars($row['desig']) ?>"</strong> with
        <strong><?= $company_short ?></strong> with effect from <strong><?= fmtDot($lwd_raw) ?></strong>.</p>

        <p>We appreciate your contributions to <strong><?= $company ?></strong> and wish you all the
        best for your future endeavours.</p>
    </div>

    <div class="sign-block">
        <div class="sign-row">
            <div class="sign-col">
                <div class="sign-line"></div>
                <p class="title">Authorized Signatory,</p>
                <p><?= $hr_dept ?>.</p>
            </div>
        </div>
    </div>

<?php 


elseif ($action === 'fff'): ?>

    <div class="letterhead">
        <div class="company-name"><?= $company ?></div>
    </div>

    <div class="fff-title">Full &amp; Final Settlement Statement</div>
    <div class="fff-subtitle">(All amounts in INR)</div>

    <!-- Employee Details (fetched live from DB) -->
    <div class="section-header">Employee Details</div>
    <table class="info-table">
        <tr>
            <th>Employee Name</th>
            <td><?= htmlspecialchars($row['user_name']) ?></td>
            <th>Statement Date</th>
            <td><?= fmt($fff['fff_statement_date']) ?></td>
        </tr>
        <tr>
            <th>Employee ID</th>
            <td><?= $row['user_id'] ?></td>
            <th>Department</th>
            <td><?= htmlspecialchars($row['dept']) ?></td>
        </tr>
        <tr>
            <th>Date of Joining</th>
            <td><?= fmt($doj_raw) ?></td>
            <th>Last Working Day</th>
            <td><?= fmt($lwd_raw) ?></td>
        </tr>
        <tr>
            <th>Designation</th>
            <td><?= htmlspecialchars($row['desig']) ?></td>
            <th>Tenure</th>
            <td><?= $tenure['text'] ?></td>
        </tr>
        <tr>
            <th>Exit Type</th>
            <td><?= $fff['exit_type'] ?></td>
            <th>Reason for Leaving</th>
            <td><?= $fff['reason_for_leaving'] ?></td>
        </tr>
        <tr>
            <th>Pending Clearance</th>
            <td colspan="3"><?= $fff['pending_clearances'] ?></td>
        </tr>
    </table>

    <!-- Earnings & Deductions -->
    <div class="section-header">Earnings &amp; Deductions</div>
    <table class="earn-ded-table">
        <thead>
            <tr>
                <th style="width:30%">Earnings</th>
                <th style="width:20%">Amount (Rs.)</th>
                <th style="width:30%">Deductions</th>
                <th style="width:20%">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $earn_labels = [
                'salary_payable'     => 'Salary Payable',
                'bonus'              => 'Bonus',
                'leave_encashment'   => 'Leave Encashment',
                'gratuity'           => 'Gratuity',
                'ex_gratia'          => 'Ex-Gratia / Goodwill',
                'performance_payout' => 'Performance Payout',
                'other_earnings'     => 'Other Earnings',
            ];
            $ded_labels = [
                'notice_pay_recovery' => 'Notice Pay Recovery',
                'hip_deduction'       => 'HIP (Health Insurance)',
                'loan_recovery'       => 'Loan / Advance Recovery',
                'asset_recovery'      => 'Asset Recovery',
                'mobile_recovery'     => 'Mobile / Data Recovery',
                'tds'                 => 'TDS',
                'other_documents'     => 'Other Documents',
                'other_deductions'    => 'Other Deductions',
            ];
            $ek_list  = array_keys($earn_labels);
            $dk_list  = array_keys($ded_labels);
            $max_rows = max(count($ek_list), count($dk_list));
            for ($i = 0; $i < $max_rows; $i++):
                $ek = $ek_list[$i] ?? null;
                $dk = $dk_list[$i] ?? null;
            ?>
            <tr>
                <td><?= $ek ? htmlspecialchars($earn_labels[$ek]) : '' ?></td>
                <td><?= $ek ? money($fff[$ek]) : '' ?></td>
                <td><?= $dk ? htmlspecialchars($ded_labels[$dk])  : '' ?></td>
                <td><?= $dk ? money($fff[$dk]) : '' ?></td>
            </tr>
            <?php endfor; ?>
            <tr class="total-row">
                <td>Total Earnings</td>
                <td><?= money($total_earn) ?></td>
                <td>Total Deductions</td>
                <td><?= money($total_ded) ?></td>
            </tr>
        </tbody>
    </table>

    <!-- Clearance & Payment -->
    <div class="section-header">Clearance &amp; Payment</div>
    <table class="info-table" style="margin-bottom:16px;">
        <tr>
            <th>Payment Mode</th>
            <td><?= $fff['payment_mode'] ?></td>
            <th>Transaction Reference</th>
            <td><?= $fff['payment_ref'] ?></td>
        </tr>
        <tr>
            <th>Pending Clearances</th>
            <td><?= $fff['pending_clearances'] ?></td>
            <th>Remarks</th>
            <td><?= $fff['fff_remarks'] ?></td>
        </tr>
    </table>

    <!-- Department Status -->
    <div class="section-header">Department Status</div>
    <table class="approvers-table" style="margin-bottom:16px;">
        <tr>
            <th>Department</th><th>Status</th>
            <th>Department</th><th>Status</th>
        </tr>
        <tr>
            <td>Reporting Supervisor</td><td>Done</td>
            <td>IT / Systems</td><td>Done</td>
        </tr>
        <tr>
            <td>Reporting HR</td><td>Done</td>
            <td>Admin / Assets</td><td>Done</td>
        </tr>
    </table>

    <!-- Final Summary -->
    <div class="section-header">Final Settlement Summary</div>
    <table class="summary-table">
        <tr>
            <td>Total Earnings (Rs.)</td>
            <td style="text-align:right;font-weight:600;"><?= money($total_earn) ?></td>
        </tr>
        <tr>
            <td>Less: Total Deductions (Rs.)</td>
            <td style="text-align:right;font-weight:600;"><?= money($total_ded) ?></td>
        </tr>
        <tr class="net-row">
            <td>Net Payable to Employee (Rs.)</td>
            <td style="text-align:right;"><?= money($net_pay) ?></td>
        </tr>
    </table>

    <!-- Declaration -->
    <div class="declaration-box">
        <strong>Employee Declaration:</strong> I acknowledge the above full &amp; final settlement amount
        and confirm that, subject to actual bank credits, I have no further dues claims except as
        recorded in this settlement.
    </div>

    <!-- Signatories -->
    <table class="approvers-table">
        <tr>
            <th>Prepared by HR</th>
            <th>Verified by</th>
            <th>Approved by – Authorized Signatory</th>
            <th>Employee</th>
        </tr>
        <tr>
            <td style="height:40px;"></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td><?= htmlspecialchars($row['user_name']) ?></td>
        </tr>
    </table>

<?php


elseif ($action === 'experience'): ?>

    <div class="letterhead">
        <div class="company-name"><?= $company ?></div>
        <div class="company-sub">HR Department</div>
    </div>

    <div style="text-align:center;font-size:18pt;font-weight:700;
                margin:20px 0;letter-spacing:1px;font-family:Arial;">
        EXPERIENCE LETTER
    </div>

    <div style="text-align:right;margin-bottom:20px;">
        Date: <?= fmtLong($lwd_raw) ?>
    </div>

    <div style="margin-bottom:20px;">
        <strong>To Whom It May Concern,</strong>
    </div>

    <div class="letter-body" style="line-height:1.7;font-size:12pt;">

        <p>
            This is to certify that <strong><?= htmlspecialchars($row['user_name']) ?></strong> 
            (Employee ID: <strong><?= $row['user_id'] ?></strong>) was employed with 
            <strong><?= $company ?></strong> as a 
            <strong><?= htmlspecialchars($row['desig']) ?></strong> in the 
            <strong><?= htmlspecialchars($row['dept']) ?></strong> Department.
        </p>

        <p>
            The employee worked with us from 
            <strong><?= fmtLong($doj_raw) ?></strong> to 
            <strong><?= fmtLong($lwd_raw) ?></strong>, completing a tenure of 
            <strong><?= $tenure['text'] ?></strong>.
        </p>

        <p>
            During this period, <?= htmlspecialchars($row['user_name']) ?> was found to be 
            sincere, hardworking, and professional in their duties. They consistently 
            demonstrated a positive attitude and contributed effectively to the team.
        </p>

        <p>
            Their conduct and performance were found to be satisfactory during their 
            employment with us.
        </p>

        <p>
            We wish them all the best in their future endeavors.
        </p>

    </div>

    <div class="sign-block" style="margin-top:40px;">
        <div class="sign-row" style="justify-content:flex-start;">
            <div class="sign-col">
                <div class="sign-line"></div>
                <p><strong>Authorized Signatory</strong></p>
                <p><?= $hr_dept ?></p>
                <p><?= $company ?></p>
            </div>
        </div>
    </div>

<?php endif; ?>

</div><!-- /#letter-content -->
</div><!-- /.page-wrap -->

<script>
async function downloadPDF() {
    const btn = document.querySelectorAll('.btn-tool');
    btn.forEach(b => b.style.display = 'none');

    if (typeof html2pdf === 'undefined') {
        await loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js');
    }

    const element  = document.getElementById('letter-content');
    const filename = '<?= $row['user_name'] ?>_<?= $action ?>_letter.pdf';

    html2pdf().set({
        margin: [12, 12, 12, 12],
        filename: filename,
        image:    { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF:    { unit: 'mm', format: 'a4', orientation: 'portrait' }
    }).from(element).save().then(function () {
        btn.forEach(b => b.style.display = '');
    });
}

function loadScript(src) {
    return new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
}
</script>
</body>
</html>