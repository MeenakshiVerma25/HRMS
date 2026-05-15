<?php
include '../includes/header.php';

$sql = "SELECT c.*, j.job_title 
        FROM candidates c 
        LEFT JOIN jobs j ON c.job_id = j.job_id 
        WHERE c.isdeleted='0' ORDER BY c.candidate_id DESC";
$result = mysqli_query($conn,$sql);

?>

<style>
    .status-dropdown{
        min-width: 135px;
        padding: 6px 32px 6px 12px;
        border-radius: 20px;
        border: 1px solid var(--border);
        font-size: 0.78rem;
        font-weight: 600;
        outline: none;
        cursor: pointer;
        background-color: #fff;
        transition: 0.2s ease;
    }

    .status-dropdown:focus{
        border-color: var(--rose-mid);
        box-shadow: 0 0 0 3px rgba(212,132,154,0.12);
    }

    .status-dropdown.applied{
        background: rgba(201,151,90,0.13);
        color: #b87a2a;
        border: 1px solid rgba(201,151,90,0.30);
    }

    .status-dropdown.shortlisted{
        background: rgba(70,130,180,0.12);
        color: #4682b4;
        border: 1px solid rgba(70,130,180,0.28);
    }

    .status-dropdown.rejected{
        background: rgba(194,99,122,0.10);
        color: var(--rose-deep);
        border: 1px solid rgba(194,99,122,0.25);
    }

    .status-dropdown.hired{
        background: rgba(46,125,82,0.10);
        color: #2e7d52;
        border: 1px solid rgba(46,125,82,0.25);
    }

    .status-dropdown.joined{
        background: rgba(123,104,238,0.12);
        color: #6a5acd;
        border: 1px solid rgba(123,104,238,0.25);
    }
</style>

    <div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Candidates List</h2>
                <p class="dash-section-sub">Choose the best candidates for your team</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                <i class="fa fa-plus me-2"></i>Add Candidate
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="candidateTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Job</th>
                            <th>Resume</th>
                            <th>Education</th>
                            <th>Experience</th>
                            <th>Status</th>
                            <th>Apply Date</th>
                            <th>Interview Date</th>
                            <?php if($role == 'Super_admin') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $status = $row['status'] ?? 'Applied';
                                $badgeCls = [
                                    'Applied'     => 'badge-applied',
                                    'Shortlisted' => 'badge-shortlisted',
                                    'Rejected'    => 'badge-rejected',
                                    'Hired'       => 'badge-hired',
                                    'Joined'      => 'badge-joined'
                                ][$status] ?? 'badge-applied';
                        ?>
                            <tr style="font-size: 0.875rem;">
                                <td><?= $row['candidate_id'] ?></td>
                                <td><?= $row['full_name'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td><?= $row['phone'] ?></td>
                                <td><?= $row['job_title'] ?></td>
                                <td class="text-center">
                                    <?php if(!empty($row['resume'])) { ?>
                                        <a href="../resume/<?= $row['resume'] ?>" target="_blank" class="btn btn-sm btn-light">
                                            <i class="fa fa-file-pdf text-danger"></i>
                                        </a>
                                    <?php } else { ?>
                                        - 
                                    <?php } ?>
                                </td>
                                <td><?= $row['education'] ?></td>
                                <td><?= $row['experience'] ?></td>
                                <td>
                                    <select class="status-dropdown <?= strtolower($status) ?>"
                                            onchange="changeCandidateStatus(this, <?= $row['candidate_id'] ?>)">
                                        <option value="Applied" <?= $status == 'Applied' ? 'selected' : '' ?>>Applied</option>
                                        <option value="Shortlisted" <?= $status == 'Shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                                        <option value="Rejected" <?= $status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                        <option value="Hired" <?= $status == 'Hired' ? 'selected' : '' ?>>Hired</option>
                                        <option value="Joined" <?= $status == 'Joined' ? 'selected' : '' ?>>Joined</option>
                                    </select>
                                </td>
                                <td><?= !empty($row['applied_date']) ? date('d-m-Y', strtotime($row['applied_date'])) : '-' ?></td>
                                <td><?= !empty($row['interview_date']) ? date('d-m-Y', strtotime($row['interview_date'])) : '-' ?></td>
                                <?php if($role == 'Super_admin') { ?>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                        class="btn-action btn-edit me-1"
                                        data-candidate_id="<?= $row['candidate_id'] ?>"
                                        data-name="<?= $row['full_name'] ?>"
                                        data-email="<?= $row['email'] ?>"
                                        data-phone="<?= $row['phone'] ?>"
                                        data-job_id="<?= $row['job_id'] ?>"
                                        data-resume="<?= $row['resume'] ?>"
                                        data-education="<?= $row['education'] ?>"
                                        data-experience="<?= $row['experience'] ?>"
                                        data-applied_date="<?= $row['applied_date'] ?>"
                                        data-interview_date="<?= $row['interview_date'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editCandidateModal">

                                        <i class="fa fa-pen"></i>
                                    </a>

                                    <a href="candidates_db.php?action=delete&id=<?= $row['candidate_id'] ?>" 
                                        class="btn-action btn-delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                                <?php } ?>
                            </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<div class="modal fade" id="addCandidateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Candidate</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addCandidateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name = "action" value = "Save">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Enter email">
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label>Select Job</label>
                        <select name="Job" class="form-select" id="Job">
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
                        <label>Resume (PDF)</label>
                        <input type="file" accept="application/pdf" class="form-control" id="Resume" name="Resume" placeholder="Upload resume in PDF format">
                    </div>
                    <div class="mb-3">
                        <label>Education</label>
                        <input type="text" class="form-control" id="education" name="education" placeholder="Enter your education details">
                    </div>
                    <div class="mb-3">
                        <label>Experience</label>
                        <input type="text" class="form-control" id="experience" name="experience" placeholder="Enter your experience details">
                    </div>
                    <div class="mb-3">
                        <label>Apply date</label>
                        <input type="date" class="form-control" id="apply_date" name="apply_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Interview date</label>
                        <input type="date" class="form-control" id="interview_date" name="interview_date" value="<?= date('Y-m-d') ?>">
                    </div>   
                </form>               
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addCandidate()">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editCandidateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Candidate</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCandidateForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_candidate_id" name="edit_candidate_id">
                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="edit_full_name" placeholder="Enter full name">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" id="edit_email" name="edit_email" placeholder="Enter email">
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" class="form-control" id="edit_phone" name="edit_phone" placeholder="Enter phone number">
                    </div>
                    <div class="mb-3">
                        <label>Select Job</label>
                        <select name="edit_Job" class="form-select" id="edit_Job">
                            <option value="">Select Job</option>
                            <?php 
                            $sql = "SELECT DISTINCT job_id, job_title FROM jobs WHERE status = 'Open' ORDER BY job_id DESC";
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
                        <label>Resume (PDF)</label>
                        <input type="file" accept="application/pdf" class="form-control" id="edit_Resume" name="edit_Resume" placeholder="Upload resume in PDF format">
                    </div>
                    <div class="mb-3">
                        <label>Education</label>
                        <input type="text" class="form-control" id="edit_education" name="edit_education" placeholder="Enter your education details">
                    </div>
                    <div class="mb-3">
                        <label>Experience</label>
                        <input type="text" class="form-control" id="edit_experience" name="edit_experience" placeholder="Enter your experience details">
                    </div>
                    <div class="mb-3">
                        <label>Apply date</label>
                        <input type="date" class="form-control" id="edit_apply_date" name="edit_apply_date">
                    </div>
                    <div class="mb-3">
                        <label>Interview date</label>
                        <input type="date" class="form-control" id="edit_interview_date" name="edit_interview_date">
                    </div>   
                </form>               
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="editCandidate()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $("#candidates").addClass("active");

    $(document).ready(function() {
        $('#candidateTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    $(document).on("click", ".btn-delete", function(e) {
        e.preventDefault();

        var link = $(this).attr("href");

        Swal.fire({
            title: 'Are you sure?',
            text: "This candidate will be deleted!",
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

    document.addEventListener("DOMContentLoaded", function () {

        const applyDate = document.getElementById("apply_date");
        const interviewDate = document.getElementById("interview_date");

        // set initial min
        interviewDate.min = applyDate.value;

        // update min when apply date changes
        applyDate.addEventListener("change", function () {
            interviewDate.min = this.value;

            // if interview date is before apply date → reset it
            if (interviewDate.value < this.value) {
                interviewDate.value = this.value;
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
        const editApplyDate = document.getElementById("edit_apply_date");
        const editInterviewDate = document.getElementById("edit_interview_date");

        if (editApplyDate && editInterviewDate) {
            editApplyDate.addEventListener("change", function () {
                editInterviewDate.min = this.value;

                if (editInterviewDate.value < this.value) {
                    editInterviewDate.value = this.value;
                }
            });
        }
    });

    $(document).on("click", ".btn-edit", function() {
        $("#edit_candidate_id").val($(this).data("candidate_id"));
        $("#edit_full_name").val($(this).data("name"));
        $("#edit_email").val($(this).data("email"));
        $("#edit_phone").val($(this).data("phone"));
        $("#edit_Job").val($(this).data("job_id"));
        $("#edit_education").val($(this).data("education"));
        $("#edit_experience").val($(this).data("experience"));

        let applyDate = $(this).data("applied_date");
        let interviewDate = $(this).data("interview_date");

        $("#edit_apply_date").val(applyDate);
        $("#edit_interview_date").val(interviewDate);

        if(applyDate) {
            $("#edit_interview_date").attr("min", applyDate);

            if(!interviewDate || interviewDate < applyDate) {
                $("#edit_interview_date").val(applyDate);
            }
        }
    });

    function addCandidate() {
        var formData = new FormData(document.getElementById('addCandidateForm'));

        $.ajax({
            url: 'candidates_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.trim() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Candidate added successfully',
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

    function editCandidate() {
        var formData = new FormData(document.getElementById('editCandidateForm'));

        $.ajax({
            url: 'candidates_db.php?action=edit',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
            if(response.includes("successfully")) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated',
                    text: 'Candidate updated successfully',
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

    function changeCandidateStatus(selectEl, candidateId) {
        const status = selectEl.value;

        $.ajax({
            url: 'candidates_db.php',
            type: 'POST',
            data: {
                action: 'update_status',
                candidate_id: candidateId,
                status: status
            },
            success: function(response) {
                let res = typeof response === 'string' ? JSON.parse(response) : response;

                if (res.success) {
                    selectEl.className = 'status-dropdown ' + status.toLowerCase();

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Candidate status updated successfully',
                        timer: 1200,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Something went wrong'
                    });
                }
            }
        });
    }    

</script>