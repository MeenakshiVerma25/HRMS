<?php
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$onboarding_id = intval($_GET['onboarding_id'] ?? 0);
if (!$onboarding_id) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Invalid onboarding ID.</div></div>";
    include '../includes/footer.php'; exit();
}

// Fetch onboarding info
$ob = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT f.*, c.full_name, c.email, o.doj, o.ctc, o.onboarding_status,
            d.designation_name, l.location_name, o.offer_id
     FROM onboarding_forms f
     JOIN candidates c    ON f.candidate_id  = c.candidate_id
     JOIN offers o        ON f.offer_id      = o.offer_id
     LEFT JOIN designations d ON o.designation_id = d.designation_id
     LEFT JOIN locations    l ON o.location_id    = l.location_id
     WHERE f.onboarding_id = $onboarding_id"));

if (!$ob) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Onboarding record not found.</div></div>";
    include '../includes/footer.php'; exit();
}

// Fetch documents
$docs = mysqli_query($conn, "SELECT * FROM onboarding_documents WHERE onboarding_id = $onboarding_id ORDER BY doc_id DESC");

// Fetch checklist
$checklist = mysqli_query($conn, "SELECT * FROM onboarding_checklist WHERE onboarding_id = $onboarding_id ORDER BY checklist_id ASC");

$all_docs       = mysqli_fetch_all($docs, MYSQLI_ASSOC);
$all_checklist  = mysqli_fetch_all($checklist, MYSQLI_ASSOC);
$verified_count = count(array_filter($all_docs, fn($d) => $d['verified']));
$checklist_done = count(array_filter($all_checklist, fn($c) => $c['is_completed']));
?>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="dash-section-title">
                <a href="onboarding.php" style="color:var(--text-secondary);text-decoration:none;font-size:16px">
                    <i class="fa fa-arrow-left me-2"></i>
                </a>
                Documents & Checklist
            </h2>
            <p class="dash-section-sub">For <strong><?= htmlspecialchars($ob['full_name']) ?></strong>
                — <?= htmlspecialchars($ob['designation_name'] ?? '') ?> | DOJ: <?= $ob['doj'] ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="onboarding_form.php?offer_id=<?= $ob['offer_id'] ?>&candidate_id=<?= $ob['candidate_id'] ?>&onboarding_id=<?= $onboarding_id ?>"
               class="btn-rose btn-sm" style="text-decoration:none;">
                <i class="fa fa-edit me-1"></i> Edit Joining Form
            </a>
            <?php if ($ob['onboarding_status'] === 'Verified' && in_array($role, ['Super_admin','HR_admin'])): ?>
            <button class="btn-rose btn-sm"
                onclick="openConvertModal(<?= $onboarding_id ?>, '<?= htmlspecialchars($ob['full_name'], ENT_QUOTES) ?>')">
                <i class="fa fa-user-plus me-1"></i> Convert to Employee
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress Summary -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="ob-stat-card">
                <i class="fa fa-file-alt" style="color:#c2a037"></i>
                <div>
                    <div class="ob-stat-num"><?= count($all_docs) ?></div>
                    <div class="ob-stat-label">Documents Uploaded</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ob-stat-card">
                <i class="fa fa-check-circle" style="color:#37a55a"></i>
                <div>
                    <div class="ob-stat-num"><?= $verified_count ?></div>
                    <div class="ob-stat-label">Docs Verified</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ob-stat-card">
                <i class="fa fa-tasks" style="color:#3788c2"></i>
                <div>
                    <div class="ob-stat-num"><?= $checklist_done ?>/<?= count($all_checklist) ?></div>
                    <div class="ob-stat-label">Checklist Done</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ob-stat-card">
                <i class="fa fa-flag" style="color:var(--accent)"></i>
                <div>
                    <div class="ob-stat-num" style="font-size:13px"><?= $ob['onboarding_status'] ?></div>
                    <div class="ob-stat-label">Current Status</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- ── LEFT: Document Upload ────────────────────────────── -->
        <div class="col-lg-7">
            <div class="content-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="ob-section-title mb-0">
                        <i class="fa fa-folder-open me-2" style="color:var(--accent)"></i>Documents
                    </h5>
                    <button class="btn-rose btn-sm" onclick="$('#uploadDocModal').modal('show')">
                        <i class="fa fa-upload me-1"></i> Upload Document
                    </button>
                </div>

                <?php if (count($all_docs) === 0): ?>
                <div class="text-center py-4" style="color:var(--text-secondary)">
                    <i class="fa fa-cloud-upload-alt" style="font-size:36px;opacity:0.3"></i>
                    <p class="mt-2">No documents uploaded yet</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table" style="font-size:13px">
                        <thead>
                            <tr>
                                <th>Document Type</th>
                                <th>File</th>
                                <th>Uploaded By</th>
                                <th>Verified</th>
                                <?php if (in_array($role, ['Super_admin','HR_admin'])): ?>
                                <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="docTableBody">
                            <?php foreach ($all_docs as $doc): ?>
                            <tr id="doc-row-<?= $doc['doc_id'] ?>">
                                <td><strong><?= htmlspecialchars($doc['doc_type']) ?></strong></td>
                                <td>
                                    <a href="../onboarding_docs/<?= htmlspecialchars($doc['file_name']) ?>"
                                       target="_blank" class="btn btn-sm btn-light">
                                        <i class="fa fa-file me-1"></i> View
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($doc['uploaded_by']) ?></td>
                                <td>
                                    <span id="verified-badge-<?= $doc['doc_id'] ?>">
                                        <?php if ($doc['verified']): ?>
                                        <span style="color:#37a55a"><i class="fa fa-check-circle"></i> Verified</span>
                                        <?php else: ?>
                                        <span style="color:#8b8b8b"><i class="fa fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <?php if (in_array($role, ['Super_admin','HR_admin'])): ?>
                                <td>
                                    <td>
                                        <button class="btn-action <?= $doc['verified'] ? 'btn-delete' : 'btn-offer' ?>"
                                            onclick="openVerifyModal(
                                                <?= $doc['doc_id'] ?>,
                                                <?= $doc['verified'] ? 0 : 1 ?>,
                                                <?= $onboarding_id ?>,
                                                '<?= htmlspecialchars($doc['doc_type'], ENT_QUOTES) ?>',
                                                '<?= htmlspecialchars($doc['file_name'], ENT_QUOTES) ?>',
                                                <?= $doc['verified'] ? 1 : 0 ?>
                                            )"
                                            title="<?= $doc['verified'] ? 'Unverify' : 'Mark Verified' ?>"
                                            id="verify-btn-<?= $doc['doc_id'] ?>">
                                            <i class="fa <?= $doc['verified'] ? 'fa-times' : 'fa-check' ?>"></i>
                                        </button>
                                    </td>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── RIGHT: Checklist ─────────────────────────────────── -->
        <div class="col-lg-5">
            <div class="content-card h-100">
                <h5 class="ob-section-title">
                    <i class="fa fa-tasks me-2" style="color:var(--accent)"></i>New Join Checklist
                </h5>
                <!-- Progress bar -->
                <div class="mb-3">
                    <div style="font-size:12px;color:var(--text-secondary);margin-bottom:4px">
                        <?= $checklist_done ?> of <?= count($all_checklist) ?> completed
                    </div>
                    <div style="height:6px;background:var(--border-color);border-radius:3px;overflow:hidden">
                        <?php $pct = count($all_checklist) > 0 ? round(($checklist_done / count($all_checklist)) * 100) : 0; ?>
                        <div id="checklist-bar" style="width:<?= $pct ?>%;height:100%;background:var(--accent);transition:width .3s"></div>
                    </div>
                </div>

                <div id="checklist-items">
                    <?php foreach ($all_checklist as $item): ?>
                    <div class="ob-checklist-item <?= $item['is_completed'] ? 'done' : '' ?>" id="cl-item-<?= $item['checklist_id'] ?>">
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;width:100%">
                            <input type="checkbox"
                                   class="ob-checkbox"
                                   <?= $item['is_completed'] ? 'checked' : '' ?>
                                   onchange="toggleChecklist(<?= $item['checklist_id'] ?>, this.checked, <?= $onboarding_id ?>)">
                            <span><?= htmlspecialchars($item['item_name']) ?></span>
                        </label>
                        <?php if ($item['is_completed'] && $item['completed_by']): ?>
                        <small style="color:var(--text-secondary);font-size:11px;padding-left:26px">
                            ✓ <?= htmlspecialchars($item['completed_by']) ?>
                            <?= $item['completed_at'] ? '· ' . date('d M', strtotime($item['completed_at'])) : '' ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<div class="modal fade" id="verifyDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-file me-2" style="color:var(--accent)"></i>
                    Verify Document
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="verify_doc_id">
                <input type="hidden" id="verify_status_value">
                <input type="hidden" id="verify_onboarding_id">

                <div class="mb-3">
                    <strong id="verify_doc_type_text"></strong>
                </div>

                <div style="height:70vh; border:1px solid var(--border-color); border-radius:10px; overflow:hidden;">
                    <iframe id="verify_doc_frame"
                        src=""
                        style="width:100%; height:100%; border:none;">
                    </iframe>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn-rose" id="verifyDocActionBtn" onclick="confirmVerifyDoc()">
                    Verify
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa fa-upload me-2" style="color:var(--accent)"></i>Upload Document</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadDocForm">
                    <input type="hidden" name="action"        value="upload_doc">
                    <input type="hidden" name="onboarding_id" value="<?= $onboarding_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <select name="doc_type" id="doc_type" class="form-select">
                            <option value="">Select Type</option>
                            <option>Aadhaar Card</option>
                            <option>PAN Card</option>
                            <option>10th Marksheet</option>
                            <option>12th Marksheet</option>
                            <option>Degree Certificate</option>
                            <option>Experience Letter</option>
                            <option>Bank Passbook</option>
                            <option>Cancelled Cheque</option>
                            <option>Passport Size Photo</option>
                            <option value="__custom__">Other (Custom)</option>
                        </select>
                    </div>
                    <div class="mb-3 d-none" id="custom_doc_wrap">
                        <label class="form-label">Custom Document Name</label>
                        <input type="text" id="custom_doc_name" class="form-control" placeholder="Enter document name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="doc_file" id="doc_file" class="form-control"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <div style="font-size:12px;color:var(--text-secondary);margin-top:4px">
                            Accepted: PDF, JPG, PNG, DOC, DOCX
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="uploadDoc()">
                    <i class="fa fa-upload me-1"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Convert to Employee Modal -->
<div class="modal fade" id="convertModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fa fa-user-plus me-2" style="color:var(--accent)"></i>Convert to Employee</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="color:var(--text-secondary)">Creating employee account for: <strong id="convert_name"></strong></p>
                <input type="hidden" id="convert_onboarding_id">
                <div class="mb-3">
                    <label class="form-label">Assign Role</label>
                    <select id="convert_role" class="form-select">
                        <option value="Employee">Employee</option>
                        <option value="Manager">Manager</option>
                        <option value="HR_admin">HR Admin</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Set Login Password</label>
                    <input type="password" id="convert_password" class="form-control" placeholder="Minimum 6 characters">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" id="convert_password2" class="form-control" placeholder="Re-enter password">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="convertToEmployee()">
                    <i class="fa fa-user-plus me-1"></i> Create Employee
                </button>
            </div>
        </div>
    </div>
</div>


<style>
    
    .table td {
        vertical-align: middle;
    }

    .table td:last-child {
        text-align: center;
        white-space: nowrap;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        padding: 0;
        border-radius: 8px;
    }
    .ob-stat-card {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 20px;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
    }
    .ob-stat-card i { font-size: 28px; opacity: 0.85; }
    .ob-stat-num   { font-size: 22px; font-weight: 700; line-height: 1; }
    .ob-stat-label { font-size: 12px; color: var(--text-secondary); margin-top: 3px; }

    .ob-section-title {
        font-size: 14px;
        font-weight: 700;
        margin-bottom: 16px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .ob-checklist-item {
        padding: 9px 12px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        margin-bottom: 7px;
        transition: background .2s;
    }
    .ob-checklist-item.done {
        background: #37a55a11;
        border-color: #37a55a44;
    }
    .ob-checklist-item.done span { text-decoration: line-through; color: var(--text-secondary); }
    .ob-checkbox {
        width: 16px; height: 16px;
        accent-color: var(--accent);
        flex-shrink: 0;
    }
</style>

<script>
    $('#onboarding').addClass('active');

    // Custom document type toggle
    $('#doc_type').on('change', function() {
        if ($(this).val() === '__custom__') {
            $('#custom_doc_wrap').removeClass('d-none');
        } else {
            $('#custom_doc_wrap').addClass('d-none');
        }
    });

    function uploadDoc() {
        var docType = $('#doc_type').val();
        if (docType === '__custom__') {
            docType = $('#custom_doc_name').val().trim();
        }
        if (!docType) { 
            Swal.fire('Error', 'Please select a document type.', 'error'); 
            return; 
        }
        if (!$('#doc_file')[0].files[0]) { 
            Swal.fire('Error', 'Please select a file.', 'error'); 
            return; 
        }

        var formData = new FormData(document.getElementById('uploadDocForm'));
        if ($('#doc_type').val() === '__custom__') {
            formData.set('doc_type', docType);
        }

        $.ajax({
            url: 'onboarding_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                console.log("Upload response:", response);
                try { 
                    var res = JSON.parse(response); 
                } catch(e) { 
                    var res = {status:'error', message: response}; 
                }
                if (res.status === 'success') {
                    $('#uploadDocModal').modal('hide');
                    Swal.fire({
                        icon: 'success', 
                        title: 'Uploaded!',
                        text: 'Document uploaded successfully.',
                        timer: 1500, 
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Upload failed: ' + error, 'error');
            }
        });
    }

    function openVerifyModal(doc_id, new_verified_value, onboarding_id, doc_type, file_name, current_verified) {
        $('#verify_doc_id').val(doc_id);
        $('#verify_status_value').val(new_verified_value);
        $('#verify_onboarding_id').val(onboarding_id);
        $('#verify_doc_type_text').text(doc_type);

        $('#verify_doc_frame').attr('src', '../onboarding_docs/' + file_name);

        if (current_verified == 1) {
            $('#verifyDocActionBtn').text('Unverify');
            $('#verifyDocActionBtn').removeClass('btn-rose').addClass('btn-danger');
        } else {
            $('#verifyDocActionBtn').text('Verify');
            $('#verifyDocActionBtn').removeClass('btn-danger').addClass('btn-rose');
        }

        $('#verifyDocModal').modal('show');
    }

    function confirmVerifyDoc() {
        var doc_id = $('#verify_doc_id').val();
        var verified = $('#verify_status_value').val();
        var onboarding_id = $('#verify_onboarding_id').val();

        $.ajax({
            url: 'onboarding_db.php',
            type: 'POST',
            data: {
                action: 'verify_doc',
                doc_id: doc_id,
                verified: verified,
                onboarding_id: onboarding_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#verifyDocModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Document verification updated successfully',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', response.message || 'Failed to update verification', 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Request failed: ' + error, 'error');
            }
        });
    }

    var clTotal = <?= count($all_checklist) ?>;
    var clDone  = <?= $checklist_done ?>;

    function toggleChecklist(checklist_id, is_completed, onboarding_id) {
        console.log("Toggling checklist:", checklist_id, is_completed);
        
        $.ajax({
            url: 'onboarding_db.php',
            type: 'POST',
            data: { 
                action: 'toggle_checklist', 
                checklist_id: checklist_id, 
                is_completed: is_completed ? 1 : 0 
            },
            dataType: 'json',
            success: function(response) {
                console.log("Checklist response:", response);
                if (response.status === 'success') {
                    var item = $('#cl-item-' + checklist_id);
                    if (is_completed) {
                        item.addClass('done'); 
                        clDone++;
                    } else {
                        item.removeClass('done'); 
                        clDone--;
                    }
                    var pct = clTotal > 0 ? Math.round((clDone / clTotal) * 100) : 0;
                    $('#checklist-bar').css('width', pct + '%');
                } else {
                    Swal.fire('Error', response.message || 'Failed to update checklist', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                Swal.fire('Error', 'Request failed: ' + error, 'error');
            }
        });
    }

    function openConvertModal(onboarding_id, name) {
        $('#convert_onboarding_id').val(onboarding_id);
        $('#convert_name').text(name);
        $('#convertModal').modal('show');
    }

    function convertToEmployee() {
        var pwd  = $('#convert_password').val().trim();
        var pwd2 = $('#convert_password2').val().trim();
        if (pwd.length < 6) { 
            Swal.fire('Error', 'Password must be at least 6 characters.', 'error'); 
            return; 
        }
        if (pwd !== pwd2) { 
            Swal.fire('Error', 'Passwords do not match.', 'error'); 
            return; 
        }

        $.ajax({
            url: 'onboarding_db.php',
            type: 'POST',
            data: {
                action: 'convert_to_employee',
                onboarding_id: $('#convert_onboarding_id').val(),
                user_role: $('#convert_role').val(),
                emp_password: pwd
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#convertModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Employee Created!',
                        html: '<strong>' + response.name + '</strong> is now an employee!',
                        timer: 2500, 
                        showConfirmButton: false
                    }).then(() => window.location.href = 'show_users.php');
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Conversion failed: ' + error, 'error');
            }
        });
    }
</script>