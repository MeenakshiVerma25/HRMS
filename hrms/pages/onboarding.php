<?php 
include '../includes/header.php';

if(!in_array($role, ['Super_admin', 'HR_admin'])){
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$sql = "SELECT
            o.offer_id,
            o.onboarding_status,
            o.doj,
            o.ctc,
            c.candidate_id,
            c.full_name,
            c.email,
            c.phone,
            d.designation_name,
            dept.dept_name,
            l.location_name,
            f.onboarding_id,
            f.submitted_at,
            u.user_id,
            (SELECT COUNT(*) FROM onboarding_documents od WHERE od.onboarding_id = f.onboarding_id) AS doc_count,
            (SELECT COUNT(*) FROM onboarding_documents od WHERE od.onboarding_id = f.onboarding_id AND od.verified = 1) AS verified_count,
            (SELECT COUNT(*) FROM onboarding_checklist oc WHERE oc.onboarding_id = f.onboarding_id AND oc.is_completed = 1) AS checklist_done,
            (SELECT COUNT(*) FROM onboarding_checklist oc WHERE oc.onboarding_id = f.onboarding_id) AS checklist_total
        FROM offers o
        JOIN candidates c   ON o.candidate_id   = c.candidate_id
        LEFT JOIN designations d ON o.designation_id = d.designation_id
        LEFT JOIN departments  dept ON o.dept_id      = dept.dept_id
        LEFT JOIN locations l    ON o.location_id    = l.location_id
        LEFT JOIN onboarding_forms f ON f.offer_id = o.offer_id AND f.candidate_id = c.candidate_id
        LEFT JOIN users u ON u.candidate_id = c.candidate_id AND u.dele_te = '0' AND u.is_left='no' 
        WHERE o.status = 'Accepted'
          AND o.isdeleted = '0'
          AND c.isdeleted = '0'
        ORDER BY o.offer_id DESC";

$result = mysqli_query($conn, $sql);

$status_colors = [
    'Not Started'        => '#8b8b8b',
    'Form Submitted'     => '#c2a037',
    'Documents Uploaded' => '#3788c2',
    'Verified'           => '#37a55a',
    'Converted'          => '#c2637a',
];

?>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h2 class="dash-section-title">Onboarding</h2>
            <p class="dash-section-sub">Manage new joinee onboarding from form to employee conversion</p>
        </div>
    </div>

    <!-- Status Legend -->
    <div class="d-flex flex-wrap gap-2 mb-3">
        <?php foreach ($status_colors as $s => $col): ?>
        <span style="background:<?= $col ?>22; color:<?= $col ?>; border:1px solid <?= $col ?>44;
                     padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">
            <?= $s ?>
        </span>
        <?php endforeach; ?>
    </div>

    <div class="content-card dataTables_wrapper">
        <div class="table-responsive">
            <table id="onboardingTable" class="table dataTable">
                <thead>
                    <tr>
                        <th>Offer ID</th>
                        <th>Employee ID</th>
                        <th>Candidate</th>
                        <th>Designation</th>
                        <th>Department</th>
                        <th>Location</th>
                        <th>DOJ</th>
                        <th>Form</th>
                        <th>Documents</th>
                        <th>Checklist</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>View Profile</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0):
                        while ($row = mysqli_fetch_assoc($result)):
                            $color  = $status_colors[$row['onboarding_status']] ?? '#8b8b8b';
                            $ob_id  = $row['onboarding_id'];
                    ?>
                    <tr style="font-size: 0.875rem;">
                        <td><?= $row['offer_id'] ?></td>
                        <td><?php 
                            if ($row['onboarding_status'] === 'Converted') {
                                // Fetch employee ID for converted candidates
                                $emp_sql = "SELECT user_id FROM users WHERE candidate_id = {$row['candidate_id']} LIMIT 1";
                                $emp_res = mysqli_query($conn, $emp_sql);
                                if ($emp_res && mysqli_num_rows($emp_res) > 0) {
                                    $emp_row = mysqli_fetch_assoc($emp_res);
                                    echo $emp_row['user_id'];
                                } else {
                                    echo '—';
                                }
                            } else {
                                echo '—';
                            }
                        ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                            <small style="color:var(--text-secondary)"><?= htmlspecialchars($row['email']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['designation_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($row['dept_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($row['location_name']    ?? '—') ?></td>
                        <td><?= !empty($row['doj']) ? date('d-m-Y', strtotime($row['doj'])) : '-' ?></td>
                        <td>
                            <?php if ($row['submitted_at']): ?>
                                <span style="color:#37a55a"><i class="fa fa-check-circle"></i> Done</span><br>
                                <small style="color:var(--text-secondary)"><?= date('d M Y', strtotime($row['submitted_at'])) ?></small>
                            <?php else: ?>
                                <span style="color:#8b8b8b">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ob_id): ?>
                                <?= $row['verified_count'] ?>/<?= $row['doc_count'] ?> verified
                            <?php else: ?>—
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ob_id): ?>
                                <?= $row['checklist_done'] ?>/<?= $row['checklist_total'] ?> done
                            <?php else: ?>—
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge-status" style="background:<?= $color ?>22; color:<?= $color ?>; border:1px solid <?= $color ?>44;">
                                <?= $row['onboarding_status'] ?>
                            </span>
                        </td>
                        <td class="text-center">

                            <?php if ($row['onboarding_status'] !== 'Converted'): ?>
                                <!-- Start / Continue Onboarding -->
                                <a href="onboarding_form.php?offer_id=<?= $row['offer_id'] ?>&candidate_id=<?= $row['candidate_id'] ?><?= $ob_id ? '&onboarding_id='.$ob_id : '' ?>"
                                   class="btn-action btn-offer" title="Joining Form"
                                   style="margin-right:4px">
                                    <i class="fa fa-wpforms"></i>
                                </a>
                                <?php if ($ob_id): ?>
                                <!-- Documents & Checklist -->
                                <a href="onboarding_docs.php?onboarding_id=<?= $ob_id ?>"
                                   class="btn-action btn-edit" title="Documents & Checklist"
                                   style="margin-right:4px">
                                    <i class="fa fa-folder-open"></i>
                                </a>
                                <?php if ($row['onboarding_status'] === 'Verified'): ?>
                                <!-- Convert to Employee -->
                                <button class="btn-action btn-rose"
                                    onclick="openConvertModal(<?= $ob_id ?>, '<?= htmlspecialchars($row['full_name'], ENT_QUOTES) ?>')"
                                    title="Convert to Employee">
                                    <i class="fa fa-user-plus"></i>
                                </button>
                                <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:#37a55a"><i class="fa fa-check-circle"></i> Employee</span>
                            <?php endif; ?>
                        </td>
                        <td><?php
                            if($row['onboarding_status'] === 'Converted') { ?>
                                <a href="javascript:void(0)"
                                    class="btn-action btn-offer me-1"
                                    onclick="viewProfile(<?= $row['user_id'] ?>)"
                                    title="View Profile">
                                    <i class="fa fa-eye"></i>
                                </a>
                        <?php } else { echo '—'; } ?>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- View Profile Modal -->
<div class="modal fade" id="viewProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa fa-user me-2" style="color:var(--accent)"></i>Employee Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="profileModalBody">
                <div class="text-center py-4">
                    <i class="fa fa-spinner fa-spin fa-2x" style="color:var(--accent)"></i>
                </div>
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
                <div class="alert alert-info" style="font-size:13px">
                    <i class="fa fa-info-circle me-1"></i>
                    The candidate's name and email will be used as their login credentials.
                    Checklist and all onboarding data will be saved to their employee record.
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
.badge-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
</style>

<script>
    $('#onboarding').addClass('active');

    $(document).ready(function() {
        $('#onboardingTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    function openConvertModal(onboarding_id, name) {
        $('#convert_onboarding_id').val(onboarding_id);
        $('#convert_name').text(name);
        $('#convert_password').val('');
        $('#convert_password2').val('');
        $('#convertModal').modal('show');
    }

    function convertToEmployee() {
        var pwd  = $('#convert_password').val().trim();
        var pwd2 = $('#convert_password2').val().trim();
        if (pwd.length < 6) {
            Swal.fire('Error', 'Password must be at least 6 characters.', 'error'); return;
        }
        if (pwd !== pwd2) {
            Swal.fire('Error', 'Passwords do not match.', 'error'); return;
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
                try { var res = JSON.parse(response); } catch(e) { var res = {status:'error', message: response}; }
                if (res.status === 'success') {
                    $('#convertModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Employee Created!',
                        html: '<strong>' + res.name + '</strong> has been successfully converted to an employee.',
                        timer: 2500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    }

    function viewProfile(user_Id) {
        $('#profileModalBody').html('<div class="text-center py-4"><i class="fa fa-spinner fa-spin fa-2x" style="color:var(--accent)"></i></div>');
        $('#viewProfileModal').modal('show');

        $.ajax({
            url: 'onboarding_db.php',
            type: 'POST',
            data: { action: 'get_profile', user_id: user_Id },
            success: function(response) {
                $('#profileModalBody').html(response);
            },
            error: function() {
                $('#profileModalBody').html('<div class="alert alert-danger">Failed to load profile. Please try again.</div>');
            }
        });
    }
</script>
