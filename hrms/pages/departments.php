<?php
include '../includes/header.php';

if(!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "Access denied";
    exit();
}

$sql = "SELECT * FROM departments";
$result = mysqli_query($conn, $sql);

?>

<div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Department List</h2>
                <p class="dash-section-sub">Manage departments</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
                <i class="fa fa-plus me-2"></i>Add Department
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="departmentTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Department</th>
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
                                <td><?= $row['dept_id'] ?></td>
                                <td><?= $row['dept_name'] ?></td>
                                <?php if($role == 'Super_admin') { ?>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                        class="btn-action btn-edit me-1"
                                        data-id="<?= $row['dept_id'] ?>"
                                        data-name="<?= $row['dept_name'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDepartmentModal">

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

<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add New Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addDepartmentForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label>Department Name</label>
                        <input type="text" class="form-control" id="dept_name" name="dept_name" placeholder="Enter department name">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addDepartment()">Save</button>
            </div>

        </div>
    </div>
</div>  
                                        
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editDepartmentForm">
                        <input type="hidden" id="edit_dept_id" name="edit_dept_id">
                        <div class="mb-3">
                            <label>Department Name</label>
                            <input type="text" class="form-control" id="edit_dept_name" name="edit_dept_name" placeholder="Enter department name">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-rose" onclick="editDepartment()">Save</button>
                </div>
            </div>
        </div>
    </div>  

    <script>
        $("#department").addClass("active");

        $(document).ready(function() {
            $('#departmentTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']]
            });
        });

        $(document).on("click", ".btn-edit", function () {
            $("#edit_dept_id").val($(this).data("id"));
            $("#edit_dept_name").val($(this).data("name"));
        });

        function editDepartment() {
            var form = document.getElementById('editDepartmentForm');
            var formData = new FormData(form);

            $.ajax({
                url: 'department_db.php?action=edit',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.includes("successfully")) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated',
                            text: 'Department updated successfully',
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

        function addDepartment() {
            var dept_name = document.getElementById('dept_name').value;
            
            $.ajax({
                url: 'department_db.php',
                type: 'POST',
                data: {
                    dept_name: dept_name,
                    action: 'add'
                },
                success: function(response) {
                    if(response.trim().toLowerCase() === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Department added successfully',
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
             