<?php
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$sql    = "SELECT r.* FROM resignation r ORDER BY resignation_id DESC";
$result = mysqli_query($conn, $sql);
?>

<style>
    .badge-status {
        display:inline-flex; align-items:center; gap:5px;
        padding:4px 10px; border-radius:20px; font-size:0.73rem; font-weight:600;
    }
    .badge-pending  { background:rgba(201,151,90,0.13);color:#b87a2a;border:1px solid rgba(201,151,90,0.3); }
    .badge-approved { background:rgba(46,125,82,0.1);color:#2e7d52;border:1px solid rgba(46,125,82,0.25); }
    .badge-rejected { background:rgba(194,99,122,0.1);color:var(--rose-deep);border:1px solid rgba(194,99,122,0.25); }

    .act-btn {
        width:32px; height:32px; border:none; border-radius:8px; cursor:pointer;
        display:inline-flex; align-items:center; justify-content:center;
        font-size:0.8rem; transition:0.2s;
    }
    .approve-btn { background:rgba(46,125,82,0.12); color:#2e7d52; }
    .approve-btn:hover { background:rgba(46,125,82,0.22); }
    .reject-btn  { background:rgba(194,99,122,0.12); color:var(--rose-deep); }
    .reject-btn:hover  { background:rgba(194,99,122,0.22); }
    .pending-btn { background:rgba(201,151,90,0.12); color:#b87a2a; }
    .pending-btn:hover { background:rgba(201,151,90,0.22); }


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
</style>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h2 class="dash-section-title">Resignation</h2>
            <p class="dash-section-sub">Manage employee resignations</p>
        </div>
        <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#addResignationModal">
            <i class="fa fa-plus me-2"></i>Add Resignation
        </button>
    </div>

    <div class="content-card datatables_wrapper">
        <div class="table-responsive">
            <table id="resignationTable" class="table dataTable">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Designation</th>
                        <th>Resignation Date</th>
                        <th>Last Working Day</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Letters</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                        $status = strtolower($row['status']);
                        $badgeCls = ['approved' => 'badge-approved', 'rejected' => 'badge-rejected', 'pending' => 'badge-pending'][$status] ?? ''; ?>
                    <tr style="font-size:0.875rem;">
                        <td><?= $row['resignation_id'] ?></td>
                        <td>
                            <div style="font-weight:500;color:var(--text-primary)"><?= htmlspecialchars($row['user_name'] ?: '—') ?></div>
                            <div style="font-size:0.74rem;color:var(--text-secondary)">ID: <?= (int)$row['user_id'] ?></div>
                        </td>
                        <td><?= htmlspecialchars($row['dept'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($row['desig'] ?? '—') ?></td>
                        <td><?= !empty($row['resignation_date']) ? date('d M Y', strtotime($row['resignation_date'])) : '—' ?></td>
                        <td><?= !empty($row['last_working_date']) ? date('d M Y', strtotime($row['last_working_date'])) : '—' ?></td>
                        <td>
                            <span class="badge-status <?= $badgeCls ?>">
                                <?php if ($status === 'approved') echo '<i class="fa-solid fa-check"></i> '; ?>
                                <?php if ($status === 'rejected') echo '<i class="fa-solid fa-xmark"></i> '; ?>
                                <?php if ($status === 'pending')  echo '<i class="fa-solid fa-clock"></i> '; ?>
                                <?= ucfirst($status) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($status === 'approved'): ?>
                                <button class="act-btn reject-btn"
                                        onclick="updateStatus(<?= $row['resignation_id'] ?>,'rejected')"
                                        title="Reject">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            <?php elseif ($status === 'pending'): ?>
                                <button class="act-btn pending-btn"
                                        onclick="updateStatus(<?= $row['resignation_id'] ?>,'approved')"
                                        title="Approve">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                                <button class="act-btn reject-btn ms-1"
                                        onclick="updateStatus(<?= $row['resignation_id'] ?>,'rejected')"
                                        title="Reject">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            <?php else: ?>
                                <button class="act-btn pending-btn"
                                        onclick="updateStatus(<?= $row['resignation_id'] ?>,'approved')"
                                        title="Re-Approve">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($status === 'approved'): ?>
                                <div class="d-flex flex-wrap gap-1">
                                    <a href="letters.php?action=resignation&id=<?= $row['resignation_id'] ?>" class="act-btn" target="_blank" title="Resignation Acceptance Letter">
                                        <i class="fa fa-file-alt"></i>
                                    </a>
                                    <a href="letters.php?action=relieving&id=<?= $row['resignation_id'] ?>" class="act-btn" target="_blank" title="Relieving Letter">
                                        <i class="fa fa-file-alt"></i>
                                    </a>
                                    <button class="act-btn" onclick="openFFF(<?= $row['resignation_id'] ?>)" title="Final Settlement">
                                        <i class="fa fa-file-invoice-dollar"></i>
                                    </button>
                                    <button class="act-btn" onclick="openExperience(<?= $row['resignation_id'] ?>)" title="Experience Letter">
                                        <i class="fa fa-id-badge"></i>
                                    </button>
                                </div>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<div class="modal fade" id="addResignationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user-minus me-2"></i>Add Resignation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addResignationForm">
                    <input type="hidden" name="action" value="add">

                    <div class="row">
                        <!-- Employee ID – aligned in the same grid as all other fields -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Employee ID <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="al_user_id" name="user_id"
                                   placeholder="e.g. 1006" min="1000">
                            <div id="al_emp_feedback" style="font-size:0.74rem;min-height:18px;margin-top:4px;"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="al_user_name" name="user_name"
                                   readonly placeholder="Auto-filled">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Joining</label>
                            <!-- raw YYYY-MM-DD sent to server -->
                            <input type="hidden" id="al_doj_raw" name="doj">
                            <!-- human-readable shown in the UI -->
                            <input type="text" class="form-control" id="al_doj_display"
                                   readonly placeholder="Auto-filled">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="al_dept" name="dept"
                                   readonly placeholder="Auto-filled">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="al_desig" name="desig"
                                   readonly placeholder="Auto-filled">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Resignation Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="resignation_date"
                                   id="add_resignation_date" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Working Day <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="last_working_date"
                                   id="add_last_working_date" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveResignation()">
                    <i class="fa fa-save me-2"></i>Save
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="fffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-file-invoice-dollar me-2"></i>Full &amp; Final Settlement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <!-- POST directly to letters.php in a new tab — no DB save -->
                <form id="fffForm" method="POST" action="letters.php" target="_blank">
                    <input type="hidden" name="action" value="fff">
                    <input type="hidden" name="id"    id="fff_resignation_id_hidden">

                    <!-- Employee Details (read-only display, no name attr — not submitted) -->
                    <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">
                        <i class="fa fa-user me-1"></i> Employee Details
                    </h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="fff_user_name" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="fff_user_id" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Department</label>
                            <input type="text" class="form-control" id="fff_dept" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="fff_desig" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date of Joining</label>
                            <input type="text" class="form-control" id="fff_doj" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Last Working Day</label>
                            <input type="text" class="form-control" id="fff_lwd" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tenure</label>
                            <input type="text" class="form-control" id="fff_tenure" readonly>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Exit Type</label>
                            <select class="form-select" name="exit_type" id="fff_exit_type">
                                <option value="Resigned">Resigned</option>
                                <option value="Retired">Retired</option>
                                <option value="Terminated">Terminated</option>
                                <option value="Contract End">Contract End</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reason for Leaving</label>
                            <input type="text" class="form-control" name="reason_for_leaving"
                                   id="fff_reason" placeholder="e.g. Personal">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Statement Date</label>
                            <input type="date" class="form-control" name="fff_statement_date"
                                   id="fff_statement_date">
                        </div>
                    </div>

                    <!-- Earnings & Deductions -->
                    <div class="row mt-2">
                        <!-- EARNINGS -->
                        <div class="col-md-6">
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">
                                <i class="fa fa-plus-circle me-1 text-success"></i> Earnings (₹)
                            </h6>
                            <div class="mb-2">
                                <label class="form-label small">Salary Payable</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="salary_payable" id="fff_salary_payable" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Bonus</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="bonus" id="fff_bonus" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Leave Encashment</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="leave_encashment" id="fff_leave_encashment" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Gratuity <span class="text-muted small">(auto-calc for ≥5 yrs)</span></label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="gratuity" id="fff_gratuity" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Ex-Gratia / Goodwill</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="ex_gratia" id="fff_ex_gratia" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Performance Payout</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="performance_payout" id="fff_performance_payout" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Other Earnings</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-earn"
                                       name="other_earnings" id="fff_other_earnings" value="0" oninput="calcFFF()">
                            </div>
                            <div class="p-2 rounded" style="background:var(--bg-subtle,#f8f9fa);">
                                <strong>Total Earnings: <span id="total_earnings" class="text-success">₹0.00</span></strong>
                            </div>
                        </div>

                        <!-- DEDUCTIONS -->
                        <div class="col-md-6">
                            <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">
                                <i class="fa fa-minus-circle me-1 text-danger"></i> Deductions (₹)
                            </h6>
                            <div class="mb-2">
                                <label class="form-label small">Notice Pay Recovery</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="notice_pay_recovery" id="fff_notice_pay_recovery" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">HIP (Health Insurance)</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="hip_deduction" id="fff_hip" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Loan / Advance Recovery</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="loan_recovery" id="fff_loan" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Asset Recovery</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="asset_recovery" id="fff_asset" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Mobile / Data Recovery</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="mobile_recovery" id="fff_mobile" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">TDS</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="tds" id="fff_tds" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Other Documents</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="other_documents" id="fff_other_docs" value="0" oninput="calcFFF()">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Other Deductions</label>
                                <input type="number" step="0.01" min="0" class="form-control fff-ded"
                                       name="other_deductions" id="fff_other_ded" value="0" oninput="calcFFF()">
                            </div>
                            <div class="p-2 rounded" style="background:var(--bg-subtle,#f8f9fa);">
                                <strong>Total Deductions: <span id="total_deductions" class="text-danger">₹0.00</span></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Net Payable -->
                    <div class="mt-3 p-3 rounded text-center"
                         style="background:var(--bg-subtle,#f0fdf4);border:1px solid #bbf7d0;">
                        <h5 class="mb-0">Net Payable: <span id="net_payable" class="text-success fw-bold">₹0.00</span></h5>
                    </div>

                    <!-- Payment & Clearance -->
                    <div class="row mt-3">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select class="form-select" name="payment_mode" id="fff_payment_mode">
                                <option>Bank Transfer</option>
                                <option>Cheque</option>
                                <option>Cash</option>
                                <option>NEFT</option>
                                <option>RTGS</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Transaction Reference</label>
                            <input type="text" class="form-control" name="payment_ref"
                                   id="fff_payment_ref" placeholder="NA">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pending Clearances</label>
                            <input type="text" class="form-control" name="pending_clearances"
                                   id="fff_pending_clearances" placeholder="NA">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Remarks</label>
                            <input type="text" class="form-control" name="fff_remarks"
                                   id="fff_remarks_field" placeholder="NA">
                        </div>
                    </div>

                </form><!-- /fffForm -->

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="fffForm" class="btn-rose">
                    <i class="fa fa-file-invoice-dollar me-2"></i>Generate Letter
                </button>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="experienceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-id-badge me-2"></i>Experience Letter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form id="experienceForm" method="POST" action="letters.php" target="_blank">
                    <input type="hidden" name="action" value="experience">
                    <input type="hidden" name="id"    id="exp_resignation_id">

                    <!-- Employee Details (auto-filled, display-only — no name attr, not submitted) -->
                    <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">
                        <i class="fa fa-user me-1"></i> Employee Details
                        <span class="badge bg-secondary ms-2" style="font-size:0.7rem;font-weight:400;">Auto-filled from records</span>
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small">Employee Name</label>
                            <input type="text" class="form-control" id="exp_user_name" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small">Employee ID</label>
                            <input type="text" class="form-control" id="exp_user_id" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small">Department</label>
                            <input type="text" class="form-control" id="exp_dept" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small">Designation</label>
                            <input type="text" class="form-control" id="exp_desig" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Date of Joining</label>
                            <input type="text" class="form-control" id="exp_doj" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Last Working Day</label>
                            <input type="text" class="form-control" id="exp_lwd" readonly>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Tenure</label>
                            <input type="text" class="form-control" id="exp_tenure" readonly>
                        </div>
                    </div>

                    <!-- Auto-calculated settlement figures -->
                    <h6 class="text-muted fw-bold mb-3 border-bottom pb-2 mt-1">
                        <i class="fa fa-calculator me-1"></i> Auto-Calculated Settlement
                    </h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Basic Salary</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="exp_basic" readonly>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Salary Payable</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="exp_salary_display" readonly>
                                <!-- submitted hidden input -->
                                <input type="hidden" name="salary_payable" id="exp_salary_payable">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Gratuity <span class="text-muted" style="font-size:0.7rem;">(≥5 yrs service)</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="text" class="form-control" id="exp_gratuity_display" readonly>
                                <!-- submitted hidden input -->
                                <input type="hidden" name="gratuity" id="exp_gratuity">
                            </div>
                        </div>
                    </div>

                    <!-- Manual entry: only these two -->
                    <h6 class="text-muted fw-bold mb-3 border-bottom pb-2 mt-1">
                        <i class="text-primary"></i> Manual Entry
                        <span style="font-size:0.74rem;color:var(--text-secondary)">Enter values below</span>
                    </h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Ex-Gratia / Goodwill <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" class="form-control exp-val"
                                       name="ex_gratia" id="exp_ex_gratia" value="0"
                                       oninput="calcExp()" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Performance Payout <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" class="form-control exp-val"
                                       name="performance_payout" id="exp_performance_payout" value="0"
                                       oninput="calcExp()" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Net Settlement Summary -->
                    <div class="rounded p-3 mb-3" style="background:var(--bg-subtle,#f0fdf4);border:1px solid #bbf7d0;">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold" style="font-size:0.95rem;">Net Settlement Amount</span>
                            <span id="exp_net_display" class="fw-bold text-success" style="font-size:1.1rem;">₹0.00</span>
                        </div>
                        <div class="text-muted mt-1" style="font-size:0.75rem;">
                            Salary Payable + Gratuity + Ex-Gratia + Performance Payout
                        </div>
                    </div>

                    <!-- Payment Details -->
                    <h6 class="text-muted fw-bold mb-3 border-bottom pb-2">
                        <i class="fa fa-credit-card me-1"></i> Payment Details
                    </h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Payment Mode</label>
                            <select class="form-select" name="payment_mode" id="exp_payment_mode">
                                <option>Bank Transfer</option>
                                <option>Cheque</option>
                                <option>Cash</option>
                                <option>NEFT</option>
                                <option>RTGS</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Transaction Reference</label>
                            <input type="text" class="form-control" name="payment_ref"
                                   id="exp_payment_ref" placeholder="NA">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small">Statement Date</label>
                            <input type="date" class="form-control" name="statement_date"
                                   id="exp_statement_date">
                        </div>
                    </div>

                </form><!-- /experienceForm -->

            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="experienceForm" class="btn-rose">
                    <i class="fa fa-id-badge me-2"></i>Generate Experience Letter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $('#resignation').addClass('active');

    document.addEventListener('DOMContentLoaded', function () {
        
        const resignationDateInput = document.getElementById('add_resignation_date');
        const lastWorkingDateInput = document.getElementById('add_last_working_date');

        if (resignationDateInput.value) {
            lastWorkingDateInput.min = resignationDateInput.value;
        }

        resignationDateInput.addEventListener("change", function (){
            lastWorkingDateInput.min = this.value;

            if (lastWorkingDateInput.value < this.value) {
                lastWorkingDateInput.value = this.value; // auto-adjust LWD if it's before the new Resignation Date
            }
        })
            
    });

    $(document).ready(function () {
        $('#resignationTable').DataTable({
            pageLength: 15,
            order: [[0, 'desc']],
            language: { emptyTable: 'No resignation records found.' }
        });

        /* Employee ID input → autofill (debounced) */
        let timer;
        $('#al_user_id').on('input', function () {
            clearTimeout(timer);
            const uid = $(this).val().trim();
            if (!uid) { clearEmpFields(); return; }
            timer = setTimeout(function () { fetchEmployeeById(uid); }, 450);
        });
    });

    /* Fetch employee by ID and autofill fields */
    function fetchEmployeeById(uid) {
        const fb = $('#al_emp_feedback');
        fb.html('<span style="color:#888;"><i class="fa fa-spinner fa-spin"></i> Looking up…</span>');

        $.ajax({
            url: 'resignation_db.php',
            type: 'GET',
            data: { action: 'get_employee', user_id: uid },
            dataType: 'json',
            success: function (res) {
                if (res && res.success) {
                    applyEmpFields(res);
                } else {
                    clearEmpFields();
                    fb.html('<span style="color:#c0392b;"><i class="fa fa-exclamation-circle"></i> ' +
                            (res ? res.message : 'Employee not found') + '</span>');
                }
            },
            error: function (xhr) {
                fb.html('<span style="color:#c0392b;">Request failed — check console</span>');
                console.error('get_employee error:', xhr.responseText);
            }
        });
    }

    /* Fill modal fields from employee object */
    function applyEmpFields(emp) {
        $('#al_user_id').val(emp.user_id   || '');
        $('#al_user_name').val(emp.user_name || '');
        $('#al_dept').val(emp.dept         || '');
        $('#al_desig').val(emp.desig       || '');
        $('#al_doj_raw').val(emp.doj       || '');
        $('#al_doj_display').val(emp.doj ? fmtDateDisplay(emp.doj) : '');
        $('#al_emp_feedback').html(
            '<span style="color:#2e7d52;font-size:0.8rem;">' +
            '<i class="fa fa-check-circle"></i> ' + emp.user_name + '</span>'
        );
    }

    function clearEmpFields() {
        $('#al_user_id,#al_user_name,#al_dept,#al_desig,#al_doj_raw,#al_doj_display').val('');
        $('#al_emp_feedback').html('');
    }

    function fmtDateDisplay(raw) {
        if (!raw) return '';
        try {
            const [y, m, d] = raw.split('-');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return d + ' ' + months[parseInt(m, 10) - 1] + ' ' + y;
        } catch(e) { return raw; }
    }

    /* Save new resignation */
    function saveResignation() {
        const uid = $('#al_user_id').val().trim();
        if (!uid) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Please enter an Employee ID.' });
            return;
        }
        if (!$('[name="resignation_date"]').val()) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Please enter Resignation Date.' });
            return;
        }
        if (!$('[name="last_working_date"]').val()) {
            Swal.fire({ icon: 'warning', title: 'Required', text: 'Please enter Last Working Day.' });
            return;
        }

        const data = new FormData(document.getElementById('addResignationForm'));
        $.ajax({
            url: 'resignation_db.php',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.trim().toLowerCase() === 'success') {
                    Swal.fire({ icon: 'success', title: 'Success',
                                text: 'Resignation added successfully.',
                                timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response });
                }
            }
        });
    }


    function updateStatus(rid, status) {
        const label = status === 'approved' ? 'Approve' : 'Reject';
        Swal.fire({
            title: label + ' Resignation?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: label,
            confirmButtonColor: '#c2637a', 
            cancelButtonColor: '#6c757d' 
        }).then(res => {
            if (res.isConfirmed) {
                $.post('resignation_db.php',
                    { action: 'update_status', resignation_id: rid, status: status },
                    function (r) {
                    const obj = typeof r === 'string' ? JSON.parse(r) : r;
                    if (obj.success) {
                        Swal.fire({
                            icon: 'success',
                            title: label + 'd!',
                            text: 'Resignation status updated successfully.', 
                            confirmButtonColor: '#c2637a',  
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: obj.message, confirmButtonColor: '#c2637a' });
                    }
                });
            }
        });
    }


    function openFFF(rid) {
        $.getJSON('resignation_db.php',
                { action: 'get_fff_preview', resignation_id: rid },
                function (res) {
            if (!res.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load F&F data.' });
                return;
            }

            /* Bind resignation ID to the form hidden field */
            $('#fff_resignation_id_hidden').val(res.resignation_id);

            /* Display-only fields (no name attr, not submitted) */
            $('#fff_user_name').val(res.user_name);
            $('#fff_user_id').val(res.user_id);
            $('#fff_dept').val(res.dept);
            $('#fff_desig').val(res.desig);
            $('#fff_doj').val(res.doj ? formatDate(res.doj) : '—');
            $('#fff_lwd').val(res.last_working_date ? formatDate(res.last_working_date) : '—');
            $('#fff_tenure').val(res.tenure || '');

            /* Form fields that will be submitted */
            $('#fff_exit_type').val('Resigned');
            $('#fff_reason').val('');
            $('#fff_statement_date').val(res.fff_statement_date || new Date().toISOString().split('T')[0]);

            /* Earnings – seed from live salary data */
            $('#fff_salary_payable').val(res.basic || 0);
            $('#fff_bonus').val(0);
            $('#fff_leave_encashment').val(0);
            $('#fff_gratuity').val(res.gratuity_auto || 0);
            $('#fff_ex_gratia').val(0);
            $('#fff_performance_payout').val(0);
            $('#fff_other_earnings').val(0);

            /* Deductions */
            $('#fff_notice_pay_recovery').val(0);
            $('#fff_hip').val(0);
            $('#fff_loan').val(0);
            $('#fff_asset').val(0);
            $('#fff_mobile').val(0);
            $('#fff_tds').val(0);
            $('#fff_other_docs').val(0);
            $('#fff_other_ded').val(0);

            /* Payment */
            $('#fff_payment_mode').val('Bank Transfer');
            $('#fff_payment_ref').val('');
            $('#fff_pending_clearances').val('NA');
            $('#fff_remarks_field').val('NA');

            calcFFF();
            new bootstrap.Modal(document.getElementById('fffModal')).show();
        });
    }

    /* Live totals */
    function calcFFF() {
        let earn = 0, ded = 0;
        $('.fff-earn').each(function () { earn += parseFloat($(this).val()) || 0; });
        $('.fff-ded').each(function ()  { ded  += parseFloat($(this).val()) || 0; });
        $('#total_earnings').text('₹' + earn.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
        $('#total_deductions').text('₹' + ded.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
        const net = earn - ded;
        $('#net_payable').text('₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
    }

    /* Shared date formatter (YYYY-MM-DD → dd Mon YYYY) */
    function formatDate(raw) {
        if (!raw) return '—';
        try {
            const [y, m, d] = raw.split('-');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            return d + ' ' + months[parseInt(m, 10) - 1] + ' ' + y;
        } catch(e) { return raw; }
    }


    function openExperience(rid) {
        $.getJSON('resignation_db.php',
                { action: 'get_fff_preview', resignation_id: rid },
                function (res) {
            if (!res.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not load employee data.' });
                return;
            }

            /* Hidden ID for the form */
            $('#exp_resignation_id').val(res.resignation_id);

            /* Display-only fields */
            $('#exp_user_name').val(res.user_name);
            $('#exp_user_id').val(res.user_id);
            $('#exp_dept').val(res.dept);
            $('#exp_desig').val(res.desig);
            $('#exp_doj').val(res.doj ? formatDate(res.doj) : '—');
            $('#exp_lwd').val(res.last_working_date ? formatDate(res.last_working_date) : '—');
            $('#exp_tenure').val(res.tenure || '—');
            $('#exp_basic').val(res.basic ? parseFloat(res.basic).toLocaleString('en-IN', { minimumFractionDigits: 2 }) : '0.00');

            /* Auto-calculated settlement — seeded from live salary data */
            const salaryPayable = parseFloat(res.basic) || 0;
            const gratuity      = parseFloat(res.gratuity_auto) || 0;

            $('#exp_salary_display').val(salaryPayable.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#exp_salary_payable').val(salaryPayable.toFixed(2));   // hidden submitted
            $('#exp_gratuity_display').val(gratuity.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
            $('#exp_gratuity').val(gratuity.toFixed(2));              // hidden submitted

            /* Manual fields — reset to zero for fresh entry */
            $('#exp_ex_gratia').val(0);
            $('#exp_performance_payout').val(0);

            /* Payment defaults */
            $('#exp_payment_mode').val('Bank Transfer');
            $('#exp_payment_ref').val('');
            $('#exp_statement_date').val(res.fff_statement_date || new Date().toISOString().split('T')[0]);

            calcExp();
            new bootstrap.Modal(document.getElementById('experienceModal')).show();
        });
    }

    /* Live net settlement total */
    function calcExp() {
        const salary = parseFloat($('#exp_salary_payable').val())        || 0;
        const grat   = parseFloat($('#exp_gratuity').val())              || 0;
        const exg    = parseFloat($('#exp_ex_gratia').val())             || 0;
        const perf   = parseFloat($('#exp_performance_payout').val())    || 0;
        const net    = salary + grat + exg + perf;
        $('#exp_net_display').text('₹' + net.toLocaleString('en-IN', { minimumFractionDigits: 2 }));
    }
</script>