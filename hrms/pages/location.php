<?php
include '../includes/header.php';

if(!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "Access denied";
    exit();
}

$sql = "SELECT * FROM locations order by location_id desc";
$result = mysqli_query($conn, $sql);

?>

<div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Location Availability</h2>
                <p class="dash-section-sub">Find where jobs are available</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addlocationModal">
                <i class="fa fa-plus me-2"></i>Add Location
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="locationTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
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
                                <td><?= $row['location_id'] ?></td>
                                <td><?= $row['location_name'] ?></td>
                                <?php if($role == 'Super_admin') { ?>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                        class="btn-action btn-edit me-1"
                                        data-id="<?= $row['location_id'] ?>"
                                        data-name="<?= $row['location_name'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editLocationModal">

                                        <i class="fa fa-pen"></i>
                                    </a>
                                <?php } ?>
                            </tr>
                        <?php }
                        } else {
                            echo "<tr><td colspan='7' class='text-center' style='color:var(--text-secondary);'>No locations found</td></tr>";
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<div class="modal fade" id="addlocationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="addLocationForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label>Location Name</label>
                        <input type="text" class="form-control" id="location_name" name="location_name">
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addLocation()">Save</button>
            </div>

        </div>
    </div>
</div>  
                                        
<div class="modal fade" id="editLocationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editLocationForm">
                        <input type="hidden" id="edit_location_id" name="edit_location_id">
                        <div class="mb-3">
                            <label>Location Name</label>
                            <input type="text" class="form-control" id="edit_location_name" name="edit_location_name">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-rose" onclick="editLocation()">Save</button>
                </div>
            </div>
        </div>
    </div>  

<script>
    $("#location").addClass("active");
    
    $(document).on("click", ".btn-edit", function () {
        $("#edit_location_id").val($(this).data("id"));
        $("#edit_location_name").val($(this).data("name"));
    });

    function addLocation() {
        var form = document.getElementById('addLocationForm');
        var formData = new FormData(form);

        var location_name = formData.get('location_name');

        if(location_name == '') {
            alert('Please fill all fields');
            return;
        }

         $.ajax({
             url: 'location_db.php',
             type: 'POST',
             data: formData,
             processData: false,
             contentType: false,
             success: function(response) {
                if(response.trim().toLowerCase() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Location added successfully',
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

    function editLocation() {
        var form = document.getElementById('editLocationForm');
        var formData = new FormData(form);

        formData.append('action', 'edit');

         $.ajax({
            url: 'location_db.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.includes("successfully")) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Location updated successfully',
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