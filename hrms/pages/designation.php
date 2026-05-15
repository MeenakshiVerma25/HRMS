<?php
include '../includes/header.php';

if(!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "Access denied";
    exit();
}

$sql = "SELECT designations.* FROM designations ORDER BY designation_id DESC";
$result = mysqli_query($conn, $sql);

?>

    <div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Designation List</h2>
                <p class="dash-section-sub">Manage designations</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addDesignationModal">
                <i class="fa fa-plus me-2"></i>Add Designation
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="designationTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>Designation ID</th>
                            <th>Designation</th>
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
                                <td><?= $row['designation_id'] ?></td>
                                <td><?= $row['designation_name'] ?></td>
                                <?php if($role == 'Super_admin') { ?>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                        class="btn-action btn-edit me-1"
                                        data-id="<?= $row['designation_id'] ?>"
                                        data-name="<?= $row['designation_name'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDesignationModal">

                                        <i class="fa fa-pen"></i>
                                    </a>
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

<div class="modal fade" id="addDesignationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add New Designation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addDesignationForm">
                    <div class="mb-3">
                        <label for="designation_name">Designation Name</label>
                        <input type="text" class="form-control" id="designation_name" name="designation_name" placeholder="Enter designation name">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addDesignation()">Save</button>
            </div>

        </div>
    </div>
</div>  
                                        
<div class="modal fade" id="editDesignationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Designation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDesignationForm" method="POST">
                        <input type="hidden" id="edit_designation_id" name="edit_designation_id">
                        <div class="mb-3">
                            <label for="edit_designation_name">Designation Name</label>
                            <input type="text" class="form-control" id="edit_designation_name" name="edit_designation_name" placeholder="Enter designation name">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-rose" onclick="editDesignation()">Save</button>
                </div>
            </div>
        </div>
    </div>  


    <script>
        $("#designation").addClass("active");

        $(document).ready(function() {
            $('#designationTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']]
            });
        });
        
        $(document).on("click", ".btn-edit", function () {
            $("#edit_designation_id").val($(this).data("id"));
            $("#edit_designation_name").val($(this).data("name"));
        });

        function addDesignation() {
            var form = document.getElementById('addDesignationForm');
            var formData = new FormData(form);

             $.ajax({
                url: 'designation_db.php?action=save',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.trim().toLowerCase() === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Designation added successfully',
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

        function editDesignation() {
            var form = document.getElementById('editDesignationForm');
            var formData = new FormData(form);

            formData.append('action', 'edit');

             $.ajax({
                url: 'designation_db.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.includes("successfully")) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: 'Designation updated successfully',
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

    </script>