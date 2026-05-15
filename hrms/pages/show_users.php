<?php
include '../includes/header.php';

$sql = "SELECT * FROM users WHERE dele_te = '0' AND is_left='no' ORDER BY user_id DESC";
$result = mysqli_query($conn, $sql);

?>

    <div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">User Permissions</h2>
                <p class="dash-section-sub">Manage all registered users</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fa fa-plus me-2"></i>Add User
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="usersTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Role</th>
                            <th>Profile</th>
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
                                <td><?= $row['user_id'] ?></td>
                                <td><?= $row['user_name'] ?></td>
                                <td><?= $row['user_email'] ?></td>
                                <td><?= $row['password'] ?></td>
                                <td>
                                    <span class="badge-rose"><?= $row['user_role'] ?></span>
                                </td>
                                <td>

                                <img src="../images/profiles/<?= htmlspecialchars($row['profile'])?>" 
                                    class="profile-img view-profile-img" 
                                    data-img="../images/profiles/<?= htmlspecialchars($row['profile']) ?>" 
                                    data-name="<?= htmlspecialchars($row['user_name']) ?>">
                                </td>
                                <?php if($role == 'Super_admin') { ?>
                                <td class="text-center">
                                    <a href="javascript:void(0)" 
                                        class="btn-action btn-edit me-1"
                                        data-id="<?= $row['user_id'] ?>"
                                        data-name="<?= $row['user_name'] ?>"
                                        data-email="<?= $row['user_email'] ?>"
                                        data-password="<?= $row['password'] ?>"
                                        data-role="<?= $row['user_role'] ?>"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal">

                                        <i class="fa fa-pen"></i>
                                    </a>

                                    <a href="user_db.php?action=delete&id=<?= $row['user_id'] ?>" 
                                        class="btn-action btn-delete">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                                <?php } ?>
                            </tr>
                        <?php }
                        }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>  

      <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="addUserForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label>User Name</label>
                            <input type="text" class="form-control" id="user_name" name="user_name">
                        </div>

                        <div class="mb-3">
                            <label>User Email</label>
                            <input type="email" class="form-control" id="user_email" name="user_email">
                        </div>

                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" id="user_password" name="user_password">
                        </div>

                        <div class="mb-3">
                            <label>Role</label>
                            <select class="form-select" id="user_role" name="user_role">
                                <option value="">Select Role</option>
                                <option value="Super_admin">Super Admin</option>
                                <option value="HR_admin">HR Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Profile</label>
                            <input type="file" class="form-control" id="add_profile" name="add_profile">
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-rose" onclick="addUser()">Save</button>
                </div>

            </div>
        </div>
    </div>  


    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="editUserForm" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label>User Name</label>
                            <input type="text" class="form-control" id="edit_user_name" name="edit_user_name">
                        </div>

                        <div class="mb-3">
                            <label>User Email</label>
                            <input type="email" class="form-control" id="edit_user_email" name="edit_user_email">
                        </div>

                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" class="form-control" id="edit_user_password" name="edit_user_password">
                        </div>

                        <div class="mb-3">
                            <label>Role</label>
                            <select class="form-select" id="edit_user_role" name="edit_user_role">
                                <option value="">Select Role</option>
                                <option value="Super_admin">Super Admin</option>
                                <option value="HR_admin">HR Admin</option>
                                <option value="Manager">Manager</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>Profile (Optional)</label>
                            <input type="file" class="form-control" id="edit_profile" name="edit_profile">
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-rose" onclick="editUser()">Save Changes</button>
                </div>

            </div>
        </div>
    </div>  

    <div class="modal fade" id="ProfileImageModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalTitle">Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <img id="modalProfileImg" src="" 
                        alt="Profile Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

<script>
    $("#permissions").addClass("active");

    $(document).ready(function() {
        if ($.fn.dataTable) {
            $('#usersTable').DataTable({
                order: [[0, 'desc']],
            });
        }
    });

    $(document).on("click", ".btn-edit", function () {
        $("#edit_user_id").val($(this).data("id"));
        $("#edit_user_name").val($(this).data("name"));
        $("#edit_user_email").val($(this).data("email"));
        $("#edit_user_password").val($(this).data("password"));
        $("#edit_user_role").val($(this).data("role"));
    });

    $(document).on("click", ".btn-delete", function(e) {
        e.preventDefault();

        var link = $(this).attr("href");

        Swal.fire({
            title: 'Are you sure?',
            text: "This user will be deleted!",
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

    function addUser() {
        var form = document.getElementById('addUserForm');
        var formData = new FormData(form);

        $.ajax({
            url: 'user_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.trim().toLowerCase() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'User added successfully',
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

    function editUser() {
        var form = document.getElementById('editUserForm');
        var formData = new FormData(form);

        formData.append('action', 'edit');

        $.ajax({
            url: 'user_db.php',
            type: 'POST',
            data: formData,
            contentType : false,
            processData : false,
            success: function(response) {
                if(response.includes("successfully")) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'User updated successfully',
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

    $(document).on('click', '.view-profile-img', function () {
        let imgSrc = $(this).attr('src');
        let name = $(this).data('name');

        $('#modalProfileImg').attr('src', imgSrc);
        $('#profileModalTitle').text(name + ' Profile Picture');

        $('#ProfileImageModal').modal('show');
    });

</script>

