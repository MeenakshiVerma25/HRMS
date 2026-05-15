<?php
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "Access denied"; exit();
}

$sql = "SELECT u.*, d.designation_name, l.location_name
        FROM users u
        LEFT JOIN designations d ON u.designation_id = d.designation_id
        LEFT JOIN locations l ON u.location_id = l.location_id
        WHERE u.dele_te = '0' AND u.is_left='no'
        ORDER BY u.user_id DESC";
$result = mysqli_query($conn, $sql);
?>

    <div class="dashboard-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h2 class="dash-section-title">Attendance</h2>
                <p class="dash-section-sub">Manage and view employee attendance records</p>
            </div>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="employeeTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Designation</th>
                            <th>Location</th>
                            <th>DOJ</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0):
                            while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr style="font-size: 0.875rem;">
                                <td><?= $row['user_id'] ?></td>
                                <td>
                                    <img src="../images/profiles/<?= htmlspecialchars($row['profile']) ?>"
                                         class="profile-img view-profile-img" alt="Profile"
                                         data-img="../images/profiles/<?= htmlspecialchars($row['profile']) ?>"
                                         data-name="<?= htmlspecialchars($row['user_name']) ?>"
                                         onerror="this.src='../images/profiles/img.jpeg'">
                                </td>
                                <td><?= htmlspecialchars($row['user_name']) ?></td>
                                <td><?= htmlspecialchars($row['user_email']) ?></td>
                                <td><span class="badge-rose"><?= htmlspecialchars($row['user_role']) ?></span></td>
                                <td><?= htmlspecialchars($row['designation_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['location_name'] ?? '—') ?></td>
                                <td><?= $row['doj'] ? date('d M Y', strtotime($row['doj'])) : '—' ?></td>
                                <td class="text-center">
                                    <a href="javascript:void(0)"
                                        class="btn-action btn-offer me-1 view-report"
                                        data-id="<?= $row['user_id'] ?>"
                                        title="View Report">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="javascript:void(0)"
                                        class="btn-action"
                                        data-id="<?= $row['user_id'] ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#EmployeeAttendanceModal"
                                        title="Attendance">
                                        <i class="fa fa-pen"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile;
                        endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<!-- mark attendance modal -->
<div class="modal fade" id="EmployeeAttendanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Mark Attendance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form id="markAttendanceForm">
                    <input type="hidden" name="action" value="Mark Attendance">
                    <input type="hidden" name="attendance_user_id" id="attendance_user_id">
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" class="form-control" id="attendance_date" name="attendance_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label>In-time</label>
                        <input type="time" class="form-control attendanceinput" id="in_time" name="in_time">
                    </div>
                    <div class="mb-3">
                        <label>Out-time</label>
                        <input type="time" class="form-control attendanceinput" id="out_time" name="out_time">
                    </div>
                    <div class="mb-3">
                        <label for="tatal_hours">Total Hours</label>
                        <input type="decimal" class="form-control" id="total_hours" name="total_hours" placeholder="Total Hours" readonly>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="MarkAttendance()">Save</button>
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
                <img id="modalProfileImg"
                     src=""
                     style="max-width:100%; max-height:400px; border-radius:16px;">
            </div>

        </div>
    </div>
</div>

<script>
    $('#attendance').addClass("active");

    $(document).ready(function() {
        $('#employeeTable').DataTable({
            pageLength: 10,
            order: [[0, 'desc']]
        });
    });

    $(document).on('click', '.btn-action', function() {
        let userId = $(this).data('id');
        $('#attendance_user_id').val(userId);
    });

    $(document).on('input', '.attendanceinput', function() {
        let inTime = $('#in_time').val();
        let outTime = $('#out_time').val();

        if (inTime && outTime) {
            let start = new Date(`1970-01-01T${inTime}:00`);
            let end = new Date(`1970-01-01T${outTime}:00`);

            if (end < start) {
                end.setDate(end.getDate() + 1);
            }

            let diff = (end - start) / (1000 * 60 * 60);

            $('#total_hours').val(diff > 0 ? diff.toFixed(2) : '0');

        } else {
            $('#total_hours').val('0');
        }
    });

    function MarkAttendance() {
        $.ajax({
            url: 'attendance_db.php',
            type: 'POST',
            data: $('#markAttendanceForm').serialize(),
            success: function(res) {
                if (res === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Attendance marked successfully',
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res,
                    });
                }
            }
        });
    }

    $(document).on('click', '.view-report', function () {
        let user_id = $(this).data('id');

        $.ajax({
            url: 'attendance_db.php',
            type: 'POST',
            data: {
                action: 'check_report',
                user_id: user_id
            },
            success: function (res) {

            res = res.trim();

                if (res === 'no_data') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Records',
                        text: 'No attendance found for this employee',
                    });
                    return;

                } 
                if (res === 'has_data') {
                    window.location.href = "attendance_report.php?user_id=" + user_id;
                }
            }
        });
    });

    $(document).on('click', '.view-profile-img', function () {
        let imgSrc = $(this).attr('src');
        let name = $(this).data('name');

        $('#modalProfileImg').attr('src', imgSrc);
        $('#profileModalTitle').text(name + ' Profile Picture');

        $('#ProfileImageModal').modal('show');
    });

</script>
