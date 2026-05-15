<?php 
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; 
    exit();
}

$curFirst = new DateTime(date('Y-m-01'));
$sel_month = isset($_GET['month']) ? intval($_GET['month']) : intval($curFirst->format('n'));
$sel_year  = isset($_GET['year'])  ? intval($_GET['year'])  : intval($curFirst->format('Y'));
$filter    = $_GET['filter'] ?? 'all';

/* future months list */
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

/* ── Type filter ── */
$where_filter = "";
if ($filter === 'Loan') {
    $where_filter = " AND la.loan_type = 'Loan' ";
} elseif ($filter === 'Advance') {
    $where_filter = " AND la.loan_type = 'Advance' ";
}

/* ── Fetch ALL loans (no date-range restriction) ── */
$sql = "SELECT la.loan_id, la.user_id, la.user_name, la.dept, la.desig,
               la.doj, la.loan_type, la.amount, la.emi_amount,
               la.interest_rate, la.start_month, la.end_month, la.status,
               (SELECT ld.balance_amt FROM loan_deduction ld WHERE ld.loan_id = la.loan_id ORDER BY ld.Month DESC LIMIT 1) AS balance_amount
        FROM loan_advances la
        WHERE 1=1
          $where_filter
        ORDER BY la.loan_id DESC";

$res = mysqli_query($conn, $sql);
?>

<style>
    .filter-btn{
        border:1px solid var(--border-hi);
        background: var(--card-bg);
        color: var(--text-primary);
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 0.82rem;
        font-weight: 500;
        cursor: pointer;
        transition: 0.25s ease;
    }
    .filter-btn:hover,
    .filter-btn.active{
        background: var(--rose-deep);
        color: #fff;
        border-color: var(--rose-deep);
    }
    .badge-loan{
        display:inline-block;
        padding:4px 10px;
        border-radius:20px;
        font-size:0.72rem;
        font-weight:600;
        background:rgba(194,99,122,0.12);
        color:var(--rose-mid,#c2637a);
        border:1px solid rgba(194,99,122,0.20);
    }
    .badge-advance{
        display:inline-block;
        padding:4px 10px;
        border-radius:20px;
        font-size:0.72rem;
        font-weight:600;
        background:rgba(46,125,82,0.12);
        color:#2e7d52;
        border:1px solid rgba(46,125,82,0.20);
    }
    .badge-open{
        display:inline-block;
        padding:4px 10px;
        border-radius:20px;
        font-size:0.72rem;
        font-weight:600;
        background:rgba(46,125,82,0.12);
        color:#2e7d52;
    }
    .badge-closed{
        display:inline-block;
        padding:4px 10px;
        border-radius:20px;
        font-size:0.72rem;
        font-weight:600;
        background:rgba(120,120,120,0.12);
        color:var(--text-secondary);
    }
    .act-btn {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--icon-border);
        background: var(--icon-bg);
        color: var(--icon-color);
        text-decoration: none;
        transition: all .25s ease;
    }
    .act-btn:hover{
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
    .proc-summary-bar {
        padding: 10px 18px;
        background: rgba(194,99,122,0.06);
        border-bottom: 1px solid var(--border);
        font-size: 0.8rem;
        color: var(--text-secondary);
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        align-items: center;
    }
    .proc-summary-bar strong { color: var(--text-primary); }
    .badge-pending {
        font-size:0.72rem;font-weight:600;color:#2e7d52;
        background:rgba(46,125,82,0.12);padding:3px 9px;border-radius:20px;
    }
    .badge-saved {
        font-size:0.72rem;font-weight:600;color:#888;
        background:rgba(120,120,120,0.12);padding:3px 9px;border-radius:20px;
    }
</style>

<div class="dashboard-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap:12px;">
        <div>
            <h2 class="dash-section-title">Loans &amp; Advances</h2>
            <p class="dash-section-sub">Manage employee loans and advances</p>
        </div>
        <div>
            <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#addLoanAdvanceModal">
                <i class="fa fa-plus me-2"></i>Add Loan/Advance
            </button>
            <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#loanDeductionModal">
                <i class="fa fa-list me-2"></i>Loan Deduction
            </button>
        </div>
    </div>

    <div class="content-card dataTables_wrapper">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;width:100%;">
            <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px;" id="filterBtns">
                <button type="button" class="filter-btn <?= ($filter === 'all')     ? 'active' : '' ?>" onclick="setLoanFilter('all')">All</button>
                <button type="button" class="filter-btn <?= ($filter === 'Loan')    ? 'active' : '' ?>" onclick="setLoanFilter('Loan')">Loan</button>
                <button type="button" class="filter-btn <?= ($filter === 'Advance') ? 'active' : '' ?>" onclick="setLoanFilter('Advance')">Advance</button>
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
                        <th>Balance Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && mysqli_num_rows($res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($res)): ?>
                            <?php
                                $typeBadge   = ($row['loan_type'] === 'Advance') ? 'badge-advance' : 'badge-loan';
                                $statusLower = strtolower($row['status'] ?? '');
                                $statusBadge = ($statusLower === 'paid' || $statusLower === 'completed') ? 'badge-closed' : 'badge-open';
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
                            <tr style="font-size: 0.875rem;">
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
                                <td>
                                    <?php
                                        $bal = isset($row['balance_amount']) && $row['balance_amount'] !== null
                                            ? (float)$row['balance_amount']
                                            : (float)$row['amount'];
                                        $balStyle = $bal <= 0 ? 'color:var(--rose-mid);font-weight:700;' : '';
                                    ?>
                                    <span style="<?= $balStyle ?>">&#8377;<?= number_format($bal, 2) ?></span>
                                    <?php if ($bal <= 0): ?><small style="color:var(--rose-mid);font-size:0.72rem;"> (Fully Paid)</small><?php endif; ?>
                                </td>
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


<div class="modal fade" id="addLoanAdvanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Loan / Advance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addLoanAdvanceForm" autocomplete="off">
                    <input type="hidden" name="action" value="add_loan_advance">
                    <input type="hidden" name="user_id" id="al_user_id_hidden">

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
                            <input type="hidden" id="al_doj_val" name="doj">
                        </div>
                    </div>

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
                                Monthly: <strong id="al_monthly_interest">&#8377;0.00</strong>
                                &nbsp;|&nbsp; Annual: <strong id="al_annual_interest">&#8377;0.00</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Month <span class="text-danger">*</span></label>
                            <input type="month" class="form-control" id="al_start_month" name="start_month" value="<?= date('Y-m') ?>" required>
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
                <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveLoanAdvance()">Save</button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="loanDeductionModal" tabindex="-1" aria-labelledby="loanDeductionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="loanDeductionModalLabel">
                    <i class="fa fa-list me-2" style="color:var(--rose-mid);"></i>Loan Deduction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" style="padding:0;">

                <!-- Month Selector -->
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);
                            display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label style="font-size:0.85rem;font-weight:600;color:var(--text-primary);margin:0;">
                        Select Month to Process:
                    </label>
                    <input type="month" id="processDedMonth" class="form-control form-control-sm"
                           style="width:185px;" value="<?= date('Y-m') ?>">
                    <button type="button" class="btn-rose" style="padding:6px 16px;font-size:0.82rem;"
                            onclick="loadLoansForDeduction()">
                        <i class="fa fa-search me-1"></i>Get Data
                    </button>
                    <div id="processDedStatus" style="font-size:0.8rem;color:var(--text-secondary);margin-left:auto;"></div>
                </div>

                <!-- Loader -->
                <div id="processDedLoader" class="modal-loader" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i>&nbsp;Fetching active loans&hellip;
                </div>

                <!-- Summary bar (shows after data loads) -->
                <div id="processDedSummary" class="proc-summary-bar" style="display:none;"></div>

                <!-- Preview Table -->
                <div id="processDedContent" style="display:none;overflow-x:auto;max-height:55vh;">
                    <table class="table ded-table" style="width:100%;min-width:1150px;">
                        <thead>
                            <tr>
                                <th>Loan ID</th>
                                <th>Emp ID</th>
                                <th>Employee Name</th>
                                <th>Type</th>
                                <th>Loan Amt (&#8377;)</th>
                                <th>Last Balance (&#8377;)</th>
                                <th>EMI (&#8377;)</th>
                                <th>Interest (&#8377;)</th>
                                <th>Final Deduction (&#8377;)</th>
                                <th>Balance After (&#8377;)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="processDedTbody"></tbody>
                    </table>
                </div>

                <!-- Empty / initial state -->
                <div id="processDedEmpty" style="padding:45px 0;text-align:center;
                     color:var(--text-secondary);font-size:0.85rem;">
                    Select a month above and click <strong>Get Data</strong> to see active loans.
                </div>

            </div><!-- /.modal-body -->

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn-rose" id="btnSaveDeductions"
                        style="display:none;" onclick="saveDeductions()">
                    <i class="fa fa-save me-1"></i>Save
                </button>
            </div>

        </div>
    </div>
</div>


<script>
    $('#loan').addClass('active');

    /* ── Filter helpers ── */
    function setLoanFilter(type) {
        const p     = new URLSearchParams(window.location.search);
        const month = p.get('month') || <?= $sel_month ?>;
        const year  = p.get('year')  || <?= $sel_year ?>;
        window.location.href = `?month=${month}&year=${year}&filter=${encodeURIComponent(type)}`;
    }

    /* ── DataTable ── */
    $(document).ready(function () {
        $('#loanTable').DataTable({
            pageLength: 15,
            order: [[0, 'desc']],
            language: {
                emptyTable: 'No loan / advance records found.'
            }
        });
    });

    /* ── Employee auto-fill ── */
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

    /* ── Loan amount calculator ── */
    $('#al_amount, #al_emi, #al_start_month').on('input change', recalcLoan);

    function recalcLoan() {
        var amount     = parseFloat($('#al_amount').val()) || 0;
        var emi        = parseFloat($('#al_emi').val()) || 0;
        var startMonth = $('#al_start_month').val();
        var rate       = 12;

        var payable = (amount * rate / 100) + amount;
        var monthly = ((amount * rate / 100) / 365) * 26;
        var annual  = amount * rate / 100;
        $('#al_monthly_interest').html('&#8377;' + monthly.toFixed(2));
        $('#al_annual_interest').html('&#8377;' + annual.toFixed(2));

        if (amount > 0 && emi > 0) {
            var numMonths  = Math.ceil(payable / emi);
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

    /* Reset add loan modal on close */
    $('#addLoanAdvanceModal').on('hidden.bs.modal', function () {
        $('#addLoanAdvanceForm')[0].reset();
        clearEmpFields();
        $('#al_interest').val('12');
        $('#al_monthly_interest').text('&#8377;0.00');
        $('#al_annual_interest').text('&#8377;0.00');
        $('#al_months_info').hide();
        $('#al_end_month').val('');
        $('#al_total_repay').text('0.00');
    });

    /* ── Save loan ── */
    function saveLoanAdvance() {
        var form     = document.getElementById('addLoanAdvanceForm');
        var formData = new FormData(form);

        $.ajax({
            url: 'loan_db.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.trim().toLowerCase() === 'success') {
                    Swal.fire({
                        icon: 'success', title: 'Success', text: 'Added Successfully',
                        timer: 1500, showConfirmButton: false
                    }).then(() => { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response });
                }
            }
        });
    }


    /** Holds the preview data returned from server for the selected month */
    var _dedPreviewData = [];

    /* Reset preview when Loan Deduction modal opens */
    $('#loanDeductionModal').on('show.bs.modal', function () {
        resetProcessDed();
    });

    function resetProcessDed() {
        _dedPreviewData = [];
        $('#processDedLoader').hide();
        $('#processDedContent').hide();
        $('#processDedSummary').hide().html('');
        $('#processDedEmpty').show().html(
            'Select a month above and click <strong>Get Data</strong> to see active loans.'
        );
        $('#processDedStatus').html('');
        $('#processDedTbody').html('');
        $('#btnSaveDeductions').hide();
    }

    function updateSaveButton() {
        var count = _dedPreviewData.filter(function (r) { return !r.already_processed; }).length;
        if (count > 0) {
            $('#btnSaveDeductions').show().html(
                '<i class="fa fa-save me-1"></i>Save'
            );
        } else {
            $('#btnSaveDeductions').hide();
        }
    }

    /* Fetch active loans for the selected month from loan_db.php */
    function loadLoansForDeduction() {
        var month = $('#processDedMonth').val();
        if (!month) {
            Swal.fire({
                icon: 'warning', title: 'Select Month',
                text: 'Please select a month to process.',
                background: 'var(--modal-bg)', color: 'var(--text-primary)', confirmButtonColor: '#c2637a'
            });
            return;
        }

        _dedPreviewData = [];
        $('#processDedLoader').show();
        $('#processDedContent').hide();
        $('#processDedSummary').hide().html('');
        $('#processDedEmpty').hide();
        $('#processDedStatus').html('');
        $('#btnSaveDeductions').hide();
        $('#processDedTbody').html('');

        $.ajax({
            url: 'loan_db.php',
            type: 'GET',
            data: { action: 'get_loans_for_deduction', month: month },
            dataType: 'json',
            success: function (res) {
                $('#processDedLoader').hide();

                if (!res.success) {
                    $('#processDedEmpty').show().html(
                        '<i class="fa fa-exclamation-circle" style="color:var(--rose-deep);"></i> ' + escHtml(res.message)
                    );
                    return;
                }

                _dedPreviewData = res.data || [];

                if (_dedPreviewData.length === 0) {
                    $('#processDedEmpty').show().html(
                        '<i class="fa fa-info-circle"></i> No active (Ongoing) loans found for <strong>' + formatMonth(month) + '</strong>.'
                    );
                    return;
                }

                renderDedPreview(_dedPreviewData, month);
            },
            error: function (xhr) {
                $('#processDedLoader').hide();
                $('#processDedEmpty').show().html(
                    '<i class="fa fa-exclamation-circle" style="color:var(--rose-deep);"></i> Request failed. Check console.'
                );
                console.error(xhr.responseText);
            }
        });
    }

    /* Build the deduction table */
    function renderDedPreview(data, month) {
        var tbody     = $('#processDedTbody');
        var newCount  = 0;
        var doneCount = 0;
        var totalEmi  = 0;
        var totalInt  = 0;

        tbody.empty();

        $.each(data, function (i, r) {
            var done     = r.already_processed;
            var rowStyle = done ? 'opacity:0.55;' : '';
            var badge    = r.loan_type === 'Advance'
                            ? '<span class="badge-advance">Advance</span>'
                            : '<span class="badge-loan">Loan</span>';
            var statusCell = done
                ? '<span class="badge-saved">Already Saved</span>'
                : '<span class="badge-pending">Pending</span>';
            var balStyle = r.balance_amt <= 0 ? 'color:var(--rose-mid);font-weight:700;' : 'font-weight:600;';


            tbody.append(
                '<tr style="' + rowStyle + '">' +
                '<td><strong>' + r.loan_id + '</strong></td>' +
                '<td>' + r.user_id + '</td>' +
                '<td>' + escHtml(r.user_name) + '</td>' +
                '<td>' + badge + '</td>' +
                '<td>&#8377;' + fmtIN(r.loan_amt)            + '</td>' +
                '<td>&#8377;' + fmtIN(r.last_month_balance)  + '</td>' +
                '<td>&#8377;' + fmtIN(r.emi)                 + '</td>' +
                '<td>&#8377;' + fmtIN(r.interest)            + '</td>' +
                '<td style="font-weight:600;color:var(--rose-mid);">&#8377;' + fmtIN(r.final_deduction) + '</td>' +
                '<td style="' + balStyle + '">&#8377;' + fmtIN(r.balance_amt) + (r.balance_amt <= 0 ? ' <small>(Fully Paid)</small>' : '') + '</td>' +
                '<td>' + statusCell + '</td>' +
                '</tr>'
            );

            if (done) {
                doneCount++;
            } else {
                newCount++;
                totalEmi += parseFloat(r.emi)      || 0;
                totalInt += parseFloat(r.interest)  || 0;
            }
        });

        /* Summary bar */
        $('#processDedSummary').show().html(
            '<span><strong>' + data.length + '</strong> loan(s) active in <strong>' + formatMonth(month) + '</strong></span>' +
            '<span style="color:#2e7d52;"><i class="fa fa-clock me-1"></i><strong>' + newCount + '</strong> pending</span>' +
            '<span style="color:#888;"><i class="fa fa-check me-1"></i><strong>' + doneCount + '</strong> already saved</span>' +
            '<span style="margin-left:auto;">Total EMI this month: <strong>&#8377;' + fmtIN(totalEmi) +
            '</strong> &nbsp;|&nbsp; Total Interest: <strong>&#8377;' + fmtIN(totalInt) + '</strong></span>'
        );

        $('#processDedContent').show();
        $('#processDedEmpty').hide();

        updateSaveButton();
    }

    /* Save all pending deductions for the month */
    function saveDeductions() {
        var month = $('#processDedMonth').val();
        if (!month || _dedPreviewData.length === 0) { return; }

        var toSave = _dedPreviewData.filter(function (r) { return !r.already_processed; });

        if (toSave.length === 0) {
            Swal.fire({
                icon: 'info', title: 'Nothing to Save',
                text: 'All loans are already processed for this month.',
                background: 'var(--modal-bg)', color: 'var(--text-primary)', confirmButtonColor: '#c2637a'
            });
            return;
        }

        Swal.fire({
            icon: 'question', title: 'Confirm Save',
            html: 'Save EMI deductions for <strong>' + toSave.length + '</strong> loan(s) for <strong>' + formatMonth(month) + '</strong>?',
            showCancelButton: true,
            confirmButtonText: 'Yes, Save',
            cancelButtonText:  'Cancel',
            confirmButtonColor: '#c2637a',
            background: 'var(--modal-bg)',
            color: 'var(--text-primary)'
        }).then(function (result) {
            if (!result.isConfirmed) { return; }

            $('#btnSaveDeductions').prop('disabled', true).html(
                '<i class="fa fa-spinner fa-spin me-1"></i>Saving&hellip;'
            );

            $.ajax({
                url: 'loan_db.php',
                type: 'POST',
                data: {
                    action:     'process_monthly_deduction',
                    month:      month,
                    deductions: JSON.stringify(toSave)
                },
                dataType: 'json',
                success: function (res) {
                    $('#btnSaveDeductions').prop('disabled', false);

                    var msg = res.saved + ' deduction(s) saved successfully.';
                    if (res.skipped > 0) { msg += ' ' + res.skipped + ' skipped (already processed).'; }
                    if (res.errors && res.errors.length) { msg += '\nErrors: ' + res.errors.join(', '); }

                    Swal.fire({
                        icon:  res.saved > 0 ? 'success' : 'info',
                        title: res.saved > 0 ? 'Saved!'  : 'Nothing New Saved',
                        text:  msg,
                        timer: 2200,
                        showConfirmButton: false,
                        background: 'var(--modal-bg)',
                        color: 'var(--text-primary)'
                    }).then(function () {
                        /* Refresh data to show updated "Already Saved" state */
                        loadLoansForDeduction();
                    });
                },
                error: function (xhr) {
                    $('#btnSaveDeductions').prop('disabled', false).html(
                        '<i class="fa fa-save me-1"></i>Save'
                    );
                    Swal.fire({
                        icon: 'error', title: 'Error',
                        text: 'Request failed. Please check console.',
                        background: 'var(--modal-bg)', color: 'var(--text-primary)', confirmButtonColor: '#c2637a'
                    });
                    console.error(xhr.responseText);
                }
            });
        });
    }

    /* ── Shared helpers ── */
    function escHtml(str) {
        if (!str) return '&mdash;';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function fmtIN(v) {
        return Number(v || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    function formatMonth(ym) {
        if (!ym) return '—';
        var p = ym.split('-');
        return new Date(parseInt(p[0]), parseInt(p[1]) - 1, 1)
                   .toLocaleDateString('en-IN', {month:'long', year:'numeric'});
    }

    /* ── PDF Download ── */
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
        var monthlyInterest = (((data.amount * data.interest_rate) / 100) / 365) * 26;
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
                            [{ text:'Type',              style:'lbl' }, { text: data.loan_type || '—',                           style:'val' }],
                            [{ text:'Loan Amount',       style:'lbl' }, { text: fmtCurr(data.amount),                             style:'valHi' }],
                            [{ text:'EMI Amount',        style:'lbl' }, { text: fmtCurr(data.emi_amount),                         style:'val' }],
                            [{ text:'Interest Rate',     style:'lbl' }, { text: Number(data.interest_rate).toFixed(2) + '% p.a.', style:'val' }],
                            [{ text:'Monthly Interest',  style:'lbl' }, { text: fmtCurr(monthlyInterest),                         style:'val' }],
                            [{ text:'No. of EMI Months', style:'lbl' }, { text: numMonths + ' months',                            style:'val' }],
                            [{ text:'Start Month',       style:'lbl' }, { text: fmtMonth(data.start_month),                       style:'val' }],
                            [{ text:'End Month',         style:'lbl' }, { text: fmtMonth(data.end_month),                         style:'val' }],
                            [{ text:'Status',            style:'lbl' }, { text: data.status || 'Ongoing',                         style:'val' }]
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
                companyName:  { fontSize:22, bold:true, color:'#c2637a' },
                companyTag:   { fontSize:9,  color:'#888', margin:[0,2,0,0] },
                docTitle:     { fontSize:13, bold:true, color:'#333' },
                loanIdText:   { fontSize:10, color:'#c2637a', margin:[0,4,0,0] },
                sectionTitle: { fontSize:11, bold:true, color:'#c2637a', margin:[0,0,0,4], decoration:'underline' },
                lbl:          { fontSize:9,  bold:true, color:'#666' },
                val:          { fontSize:10, color:'#222' },
                valHi:        { fontSize:11, bold:true, color:'#c2637a' },
                footNote:     { fontSize:8,  color:'#aaa', margin:[0,2,0,0] }
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