<?php
include '../includes/header.php';

$sql = "SELECT jobs.*, departments.dept_name, designations.designation_name, locations.location_name FROM jobs 
        LEFT JOIN departments ON jobs.dept_id = departments.dept_id 
        LEFT JOIN designations ON jobs.designation_id = designations.designation_id 
        LEFT JOIN locations ON jobs.location_id = locations.location_id ORDER BY jobs.job_id DESC";
$result = mysqli_query($conn, $sql);

?>

    <div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Job List</h2>
                <p class="dash-section-sub">Here is a list of all available jobs</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addJobModal">
                <i class="fa fa-plus me-2"></i>Add Job
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="jobTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>JD</th>
                            <th>Job Type</th>
                            <th>Vacancies</th>
                            <th>Experience</th>
                            <th>Location</th>
                            <th>Salary</th>
                            <th>Work Mode</th>
                            <th>Apply Before</th>
                            <th>Status</th>
                            <?php if($role == 'Super_admin') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                        ?>
                            <tr style="font-size: 0.875rem;">
                                <td><?= $row['job_id'] ?></td>
                                <td><?= $row['job_title'] ?></td>
                                <td><?= $row['dept_name'] ?></td>
                                <td><?= $row['designation_name'] ?></td>
                                <td class="text-center">
                                    <?php if(!empty($row['jd_file'])) { ?>
                                        <a href="../JD/<?= $row['jd_file'] ?>" target="_blank" class="btn btn-sm btn-light">
                                            <i class="fa fa-file-pdf text-danger"></i>
                                        </a>
                                    <?php } else { ?>
                                        - 
                                    <?php } ?>
                                </td>
                                <td><?= $row['job_type'] ?></td>
                                <td><?= $row['vacancies'] ?></td>
                                <td><?= $row['experience_required'] ?></td>
                                <td><?= $row['location_name']  ?></td>
                                <td><?= $row['salary'] ?></td>
                                <td><?= $row['work_mode'] ?></td>
                                <td><?= !empty($row['last_date_to_apply']) ? date('d-m-Y', strtotime($row['last_date_to_apply'])) : '-' ?></td>
                                <td><?= $row['status'] ?></td>
                                <?php if($role == 'Super_admin') { ?>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                        class="btn-action btn-edit me-1"
                                        data-id="<?= $row['job_id'] ?>"
                                        data-title="<?= $row['job_title'] ?>"
                                        data-department="<?= $row['dept_id'] ?>"
                                        data-designation="<?= $row['designation_id'] ?>"
                                        data-job-type="<?= $row['job_type'] ?>"
                                        data-vacancies="<?= $row['vacancies'] ?>"
                                        data-experience="<?= $row['experience_required'] ?>"
                                        data-location="<?= $row['location_id'] ?>"
                                        data-salary="<?= $row['salary'] ?>"
                                        data-work-mode="<?= $row['work_mode'] ?>"
                                        data-deadline="<?= $row['last_date_to_apply'] ?>"
                                        data-jd-file="<?= $row['jd_file'] ?>"
                                        data-status="<?= $row['status'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editJobModal">

                                        <i class="fa fa-pen"></i>
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

<!-- Add Job Modal -->
<div class="modal fade" id="addJobModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Job</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addJobForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label>Job Title</label>
                        <input type="text" class="form-control" id="job_title" name="job_title" placeholder="Enter job title">
                    </div>
                    <div class="mb-3">
                        <label>Department</label>
                        <select name="Department" id="Department" class="form-select">
                            <option value="">Select Department</option>
                            <?php 
                            $sql = "SELECT DISTINCT dept_id, dept_name FROM departments ORDER BY dept_id DESC";
                            $result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($result) > 0) { while($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?= $row['dept_id'] ?>"><?= $row['dept_name'] ?></option>
                            <?php } } else { ?>
                                <option value="">No departments available</option>
                            <?php } ?>
                        </select>                    
                    </div>
                    <div class="mb-3">
                        <label>Designation</label>
                        <select name="Designation" id="Designation" class="form-select">
                            <option value="">Select Designation</option>
                            <?php 
                            $sql = "SELECT DISTINCT designation_id, designation_name FROM designations ORDER BY designation_id DESC";
                            $result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($result) > 0) { while($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?= $row['designation_id'] ?>"><?= $row['designation_name'] ?></option>
                            <?php } } else { ?>
                                <option value="">No designations available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Description (PDF)</label>
                        <input type="file" accept="application/pdf" class="form-control" id="Description" name="Description" placeholder="Upload job description in PDF format">
                    </div>
                    <div class="mb-3">
                        <label>Job Type</label>
                        <select name="Type" id="Type" class="form-select">
                            <option value="">Select Job Type</option>
                            <option value="Full Time">Full Time</option>
                            <option value="Part Time">Part Time</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Total Vacancies</label>
                        <input type="number" class="form-control" id="Vacancy" name="Vacancy" min="1" placeholder="Enter number of vacancies">
                    </div>
                    <div class="mb-3">
                        <label>Experience</label>
                        <input type="text" class="form-control" id="experience_required" name="experience_required" placeholder="Enter experience required">
                    </div>
                    <div class="mb-3">
                        <label>Select Location</label>
                        <select name="Location" class="form-select" id="Location">
                            <option value="">Select Location</option>
                            <?php 
                            $sql = "SELECT DISTINCT location_id, location_name FROM locations ORDER BY location_id DESC";
                            $location_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($location_result) > 0){ 
                                while($location = mysqli_fetch_assoc($location_result)){  ?>
                                    <option value="<?= $location['location_id'] ?>"><?= $location['location_name'] ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No location available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Salary</label>
                        <input type="number" class="form-control" id="Salary" name="Salary" placeholder="Enter salary offered">
                    </div>
                    <div class="mb-3">
                        <label>Work Mode</label>
                        <select name="WorkMode" id="WorkMode" class="form-select">
                            <option value="">Select Work Mode</option>
                            <option value="On-site">On-site</option>
                            <option value="Remote">Remote</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Last Date to Apply</label>
                        <input type="date" class="form-control" id="ApplyBefore" name="ApplyBefore" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                </form>               
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addJob()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Job Modal -->
<div class="modal fade" id="editJobModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Job</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editJobForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="edit_job_id" name="edit_job_id">
                    <div class="mb-3">
                        <label>Job Title</label>
                        <input type="text" class="form-control" id="edit_job_title" name="edit_job_title" placeholder="Enter job title">
                    </div>
                    <div class="mb-3">
                        <label>Department</label>
                        <select name="edit_Department" id="edit_Department" class="form-select">
                            <option value="">Select Department</option>
                            <?php 
                            $sql = "SELECT DISTINCT dept_id, dept_name FROM departments ORDER BY dept_id DESC";
                            $result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($result) > 0) { while($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?= $row['dept_id'] ?>"><?= $row['dept_name'] ?></option>
                            <?php } } else { ?>
                                <option value="">No departments available</option>
                            <?php } ?>
                        </select>                    
                    </div>
                    <div class="mb-3">
                        <label>Designation</label>
                        <select name="edit_Designation" id="edit_Designation" class="form-select">
                            <option value="">Select Designation</option>
                            <?php 
                            $sql = "SELECT DISTINCT designation_id, designation_name FROM designations ORDER BY designation_id DESC";
                            $result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($result) > 0) { while($row = mysqli_fetch_assoc($result)) { ?>
                                <option value="<?= $row['designation_id'] ?>"><?= $row['designation_name'] ?></option>
                            <?php } } else { ?>
                                <option value="">No designations available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Description (PDF)</label>
                        <input type="file" accept="application/pdf" class="form-control" id="edit_Description" name="edit_Description">
                    </div>
                    <div class="mb-3">
                        <label>Job Type</label>
                        <select name="edit_Type" id="edit_Type" class="form-select">
                            <option value="">Select Job Type</option>
                            <option value="Full Time">Full Time</option>
                            <option value="Part Time">Part Time</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Total Vacancies</label>
                        <input type="number" class="form-control" id="edit_Vacancy" name="edit_Vacancy">
                    </div>
                    <div class="mb-3">
                        <label>Experience</label>
                        <input type="text" class="form-control" id="edit_Experience" name="edit_Experience">
                    </div>
                    <div class="mb-3">
                        <label>Select Location</label>
                        <select name="edit_Location" id="edit_Location" class="form-select">
                            <option value="">Select Location</option>
                            <?php 
                            $sql = "SELECT DISTINCT location_id, location_name FROM locations ORDER BY location_id DESC";
                            $location_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($location_result) > 0){ 
                                while($location = mysqli_fetch_assoc($location_result)){  ?>
                                    <option value="<?= $location['location_id'] ?>"><?= $location['location_name'] ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No location available</option> 
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Salary</label>
                        <input type="number" class="form-control" id="edit_Salary" name="edit_Salary">
                    </div>
                    <div class="mb-3">
                        <label>Work Mode</label>
                        <select name="edit_WorkMode" id="edit_WorkMode" class="form-select">
                            <option value="">Select Work Mode</option>
                            <option value="On-site">On-site</option>
                            <option value="Remote">Remote</option>
                            <option value="Hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Last Date to Apply</label>
                        <input type="date" class="form-control" id="edit_ApplyBefore" name="edit_ApplyBefore">
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="edit_Status" id="edit_Status" class="form-select">
                            <option value="">Select Status</option>
                            <option value="Open">Open</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </form>               
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="editJob()">Save</button>
            </div>
        </div>
    </div>
</div>


<script>
    $("#jobs").addClass("active");
    $(document).ready(function() {
        $('#jobTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    // Load designations based on selected department in Edit Modal
    // $("#edit_Department").on("change", function() {
    //     var dept_id = $(this).val();

    //     $.ajax({
    //         url: "../get_designations.php",
    //         type: "POST",
    //         data: {dept_id: dept_id},
    //         success: function(data) {
    //             $("#edit_Designation").html(data);
    //         }
    //     });
    // });

    // $("#Department").on("change", function() {
    //     var dept_id = $(this).val();

    //     if(dept_id != "") {
    //         $.ajax({
    //             url: "../get_designations.php",
    //             type: "POST",
    //             data: {dept_id: dept_id},
    //             success: function(data) {
    //                 $("#Designation").html(data);
    //             }
    //         });
    //     } else {
    //         $("#Designation").html('<option value="">Select Designation</option>');
    //     }
    // });

    $(document).on("click", ".btn-edit", function () {

        const today = new Date().toISOString().split("T")[0];

        $("#edit_job_id").val($(this).data("id"));
        $("#edit_job_title").val($(this).data("title"));
        $("#edit_Department").val($(this).data("department"));
        $("#edit_Designation").val($(this).data("designation"));
        $("#edit_Type").val($(this).data("job-type"));
        $("#edit_Vacancy").val($(this).data("vacancies"));
        $("#edit_Experience").val($(this).data("experience"));
        $("#edit_Location").val($(this).data("location"));
        $("#edit_Salary").val($(this).data("salary"));
        $("#edit_WorkMode").val($(this).data("work-mode"));

        let deadline = $(this).data("deadline");

        $("#edit_ApplyBefore").attr("min", today);

        if (deadline && deadline >= today) {
            $("#edit_ApplyBefore").val(deadline);
        } else {
            $("#edit_ApplyBefore").val(today);
        }
            
            $("#edit_Status").val($(this).data("status"));
            $("#edit_Description").val($(this).data("jd-file"));
        });

    // Add Job Function
    function addJob() {
        var form = document.getElementById('addJobForm');
        var formData = new FormData(form);
        
        $.ajax({
            url: 'jobs_db.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            beforeSend: function() {
                Swal.fire({
                    title: 'Adding Job...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
            },

            success: function(response) {
                if(response.trim().toLowerCase() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Job added successfully',
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

    // Edit Job Function
    function editJob() {
        var form = document.getElementById('editJobForm');
        var formData = new FormData(form);

        formData.append('action', 'edit');
        
        $.ajax({
            url: 'jobs_db.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.includes("successfully")) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Job updated successfully',
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

    document.addEventListener("DOMContentLoaded", function () {
        const today = new Date().toISOString().split("T")[0];
        // Edit form
        const editDate = document.getElementById("edit_ApplyBefore");
        if (editDate) {
            editDate.min = today;
        }

    });

</script>