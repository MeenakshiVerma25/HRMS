<?php
include '../includes/header.php';

$sql = "SELECT 
            i.interview_id,
            i.candidate_id,
            i.job_id,
            i.round_name,
            i.interview_date,
            i.panel_name,
            i.score,
            i.status AS interview_status,
            i.isdeleted,
            c.full_name,
            c.status AS candidate_status,
            j.job_title
        FROM interviews i
        JOIN candidates c ON i.candidate_id = c.candidate_id
        JOIN jobs j ON i.job_id = j.job_id
        WHERE i.isdeleted = '0'
        ORDER BY i.interview_id DESC";
$result = mysqli_query($conn,$sql);

?>

<style>
    .badge-status {
        display:inline-flex; align-items:center; gap:5px;
        padding:4px 10px; border-radius:20px; font-size:0.73rem; font-weight:600;
    }
    .badge-pending  { background:rgba(201,151,90,0.13);color:#b87a2a;border:1px solid rgba(201,151,90,0.3); }
    .badge-selected { background:rgba(46,125,82,0.1);color:#2e7d52;border:1px solid rgba(46,125,82,0.25); }
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
                <h2 class="dash-section-title">Interview Management</h2>
                <p class="dash-section-sub">Manage interview rounds, panels & decisions</p>
            </div>
            <?php if($role == 'Super_admin') {  ?>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addInterviewModal">
                <i class="fa fa-plus me-2"></i>Add Interview
            </button>
            <?php } ?>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="interviewTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Candidate</th>
                            <th>Job</th>
                            <th>Round</th>
                            <th>Date</th>
                            <th>Panel</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Decision</th>
                            <?php if($role == 'Super_admin') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $status = $row['interview_status'] ?? 'Pending';
                                $badgeCls = [
                                    'Selected' => 'badge-selected',
                                    'Rejected' => 'badge-rejected',
                                    'Pending'  => 'badge-pending'
                                ][$status] ?? 'badge-pending';
                                $idate = !empty($row['interview_date']) ? date('Y-m-d', strtotime($row['interview_date'])) : null;
                        ?>  
                            <tr style="font-size: 0.875rem;">
                                <td><?= $row['interview_id'] ?></td>
                                <td><?= $row['full_name'] ?></td>
                                <td><?= $row['job_title'] ?></td>
                                <td><?= $row['round_name'] ?></td>
                                <td><?= !empty($row['interview_date']) ? date('d-m-Y', strtotime($row['interview_date'])) : '-' ?></td>
                                <td><?= $row['panel_name'] ?></td>
                                <td><?= $row['score'] ?></td>
                                <td>
                                    <span class="badge-status <?= $badgeCls ?>">
                                        <?php if ($status === 'Selected') echo '<i class="fa-solid fa-check"></i> '; ?>
                                        <?php if ($status === 'Rejected') echo '<i class="fa-solid fa-xmark"></i> '; ?>
                                        <?php if ($status === 'Pending')  echo '<i class="fa-solid fa-clock"></i> '; ?>
                                        <?= ($status) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($status === 'Selected'): ?>
                                        <button class="act-btn reject-btn"
                                                onclick="updateStatus(<?= $row['interview_id'] ?>,'Rejected')"
                                                title="Reject">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php elseif ($status === 'Pending'): ?>
                                        <button class="act-btn pending-btn"
                                                onclick="updateStatus(<?= $row['interview_id'] ?>,'Selected')"
                                                title="Selected">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button class="act-btn reject-btn ms-1"
                                                onclick="updateStatus(<?= $row['interview_id'] ?>,'Rejected')"
                                                title="Reject">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="act-btn pending-btn"
                                                onclick="updateStatus(<?= $row['interview_id'] ?>,'Selected')"
                                                title="Re-Select">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <?php if($role == 'Super_admin') { ?>
                                    <td class="text-center">
                                        <a href="javascript:void(0)" 
                                            class="btn-action btn-edit me-1"
                                            data-interview_id="<?= $row['interview_id'] ?>"
                                            data-candidate_id="<?= $row['candidate_id'] ?>"
                                            data-job_id="<?= $row['job_id'] ?>"
                                            data-round_name="<?= $row['round_name'] ?>"
                                            data-interview_date="<?= $idate ?>"
                                            data-panel_name="<?= $row['panel_name'] ?>"
                                            data-score="<?= $row['score'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editInterviewModal">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                        <a href="interviews_db.php?action=delete&id=<?= $row['interview_id'] ?>" 
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

<div class="modal fade" id="addInterviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Interview</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addInterviewForm" method="POST">
                    <input type="hidden" name = "action" value = "add">
                    <div class="mb-3">
                        <label>Candidate</label>
                        <select name="candidate" class="form-select" id="candidate">
                            <option value="">Select Candidate</option>
                            <?php 
                            $sql = "SELECT DISTINCT candidate_id, full_name, applied_date FROM candidates WHERE isdeleted = '0' ORDER BY candidate_id DESC";
                            $candidate_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($candidate_result) > 0){ 
                                while($candidate = mysqli_fetch_assoc($candidate_result)){  ?>
                                    <option value="<?= $candidate['candidate_id'] ?>">
                                        <?= $candidate['full_name'] ?></option>
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
                        <label>Round</label>
                        <input type="number" class="form-control" id="round" name="round" placeholder="Enter round number">
                    </div>
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Panel</label>
                        <input type="text" class="form-control" id="panel" name="panel" placeholder="Enter panel members">
                    </div>
                    <div class="mb-3">
                        <label>Score</label>
                        <input type="number" class="form-control" id="score" name="score" min="0" max="100" placeholder="Enter score">
                    </div>
                </form>               
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addInterview()">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editInterviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Interview</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editInterviewForm" method="POST">
                    <input type="hidden" name = "edit_interview_id" id = "edit_interview_id">
                    <div class="mb-3">
                        <label>Candidate</label>
                        <select name="edit_candidate" class="form-select" id="edit_candidate">
                            <option value="">Select Candidate</option>
                            <?php 
                            $sql = "SELECT DISTINCT candidate_id, full_name, applied_date FROM candidates WHERE isdeleted = '0' ORDER BY candidate_id DESC";
                            $candidate_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($candidate_result) > 0){ 
                                while($candidate = mysqli_fetch_assoc($candidate_result)){  ?>
                                    <option value="<?= $candidate['candidate_id'] ?>">
                                    <?= $candidate['full_name'] ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No candidate available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Job</label>
                        <select name="edit_job" class="form-select" id="edit_job">
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
                        <label>Round</label>
                        <input type="number" class="form-control" id="edit_round" name="edit_round" placeholder="Enter round number">
                    </div>
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" class="form-control" id="edit_date" name="edit_date">
                    </div>
                    <div class="mb-3">
                        <label>Panel</label>
                        <input type="text" class="form-control" id="edit_panel" name="edit_panel" placeholder="Enter panel members">
                    </div>
                    <div class="mb-3">
                        <label>Score</label>
                        <input type="number" class="form-control" id="edit_score" name="edit_score" min="0" max="100" placeholder="Enter score">
                    </div>
                </form>               
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="editInterview()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $("#interviews").addClass("active");

    $(document).ready(function() {
        $('#interviewTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });

    });

    function updateStatus(iid, status) {
        const label = status === 'Selected' ? 'Select' : 'Reject';
        Swal.fire({
            title: label + ' Interview?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: label,
            confirmButtonColor: '#c2637a', 
            cancelButtonColor: '#6c757d' 
        }).then(res => {
            if (res.isConfirmed) {
                $.post('interviews_db.php',
                    { action: 'update_status', interview_id: iid, status: status },
                    function (r) {
                    const obj = typeof r === 'string' ? JSON.parse(r) : r;
                    if (obj.success) {
                        Swal.fire({
                            icon: 'success',
                            title: label + 'd!',
                            text: 'Interview status updated successfully.', 
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

    $(document).on("click", ".btn-delete", function(e) {
        e.preventDefault();

        var link = $(this).attr("href");

        Swal.fire({
            title: 'Are you sure?',
            text: "This interview will be deleted!",
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

    $(document).on("click", ".btn-edit", function() {
        $("#edit_interview_id").val($(this).data("interview_id"));
        $("#edit_candidate").val($(this).data("candidate_id"));
        $("#edit_job").val($(this).data("job_id"));
        $("#edit_round").val($(this).data("round_name")); 
        $("#edit_date").val($(this).data("interview_date"));
        $("#edit_panel").val($(this).data("panel_name"));
        $("#edit_score").val($(this).data("score"));                   
    });

    function addInterview() {
        var formData = new FormData(document.getElementById('addInterviewForm'));
        formData.append('action', 'add');

        $.ajax({
            url: 'interviews_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.trim().toLowerCase() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Interview added successfully',
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

    function editInterview() {
        var formData = new FormData(document.getElementById('editInterviewForm'));
        formData.append('action', 'edit');

        $.ajax({
            url: 'interviews_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.includes("successfully")) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Interview updated successfully',
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

    function setInterviewDateFromCandidate(candidateSelectId, dateInputId) {
        const candidateSelect = document.getElementById(candidateSelectId);
        const interviewDate = document.getElementById(dateInputId);

        if (!candidateSelect || !interviewDate) return;

        function updateDate() {
            const selectedOption = candidateSelect.options[candidateSelect.selectedIndex];
            const applyDate = selectedOption ? selectedOption.getAttribute("data-apply") : "";

            if (applyDate) {
                interviewDate.min = applyDate;
                interviewDate.value = applyDate;   // auto fill according to apply date
            } else {
                interviewDate.min = "";
            }
        }

        candidateSelect.addEventListener("change", updateDate);
    }

    document.addEventListener("DOMContentLoaded", function () {
        setInterviewDateFromCandidate("candidate", "date");
    });

</script>