<?php 
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$shifts_result   = mysqli_query($conn, "SELECT * FROM shifts ORDER BY shift_id DESC");
$holidays_result = mysqli_query($conn, "SELECT * FROM holidays ORDER BY holiday_date ASC");

?>

<div class="dashboard-wrapper">
        <h2 class="dash-section-title">Shifts & Holidays</h2>
        <p class="dash-section-sub mb-4">Manage work shifts and holiday calendar</p>

        <div class="row g-4">

            <!-- shifts -->
            <div class="col-lg-5">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 style="color:var(--text-primary);margin:0">Shifts Master</h5>
                        <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                            <i class="fa fa-plus me-1"></i>Add Shift
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="shiftTable" class="table">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Shift Name</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($shifts_result) > 0):
                                    while ($row = mysqli_fetch_assoc($shifts_result)): ?>
                                <tr style="font-size: 0.875rem;">
                                    <td><?= $row['shift_id'] ?></td>
                                    <td><?= htmlspecialchars($row['shift_name']) ?></td>
                                    <td><?= date('h:i A', strtotime($row['start_time'])) ?></td>
                                    <td><?= date('h:i A', strtotime($row['end_time'])) ?></td>
                                    <td class="text-center">
                                        <a href="javascript:void(0)"
                                            class="btn-action btn-edit me-1"
                                            data-id="<?= $row['shift_id'] ?>"
                                            data-name="<?= htmlspecialchars($row['shift_name'], ENT_QUOTES) ?>"
                                            data-start="<?= $row['start_time'] ?>"
                                            data-end="<?= $row['end_time'] ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editShiftModal">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- holidays -->
            <div class="col-lg-7">
                <div class="content-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 style="color:var(--text-primary);margin:0">Holiday Calendar</h5>
                        <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                            <i class="fa fa-plus me-1"></i>Add Holiday
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table id="holidayTable" class="table">
                            <thead>
                                <tr>
                                    <th>Id</th>
                                    <th>Holiday Name</th>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Type</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($holidays_result) > 0):
                                    while ($row = mysqli_fetch_assoc($holidays_result)): ?>
                                <tr>
                                    <td><?= $row['holiday_id'] ?></td>
                                    <td><?= htmlspecialchars($row['holiday_name']) ?></td>
                                    <td><?= date('d M Y', strtotime($row['holiday_date'])) ?></td>
                                    <td><?= date('l', strtotime($row['holiday_date'])) ?></td>
                                    <td>
                                        <span class="badge-rose" style="<?= $row['holiday_type'] == 'Optional' ? 'background:#c2a03722;color:#c2a037;border:1px solid #c2a03744' : '' ?>">
                                            <?= $row['holiday_type'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="javascript:void(0)"
                                            class="btn-action btn-edit me-1"
                                            data-id="<?= $row['holiday_id'] ?>"
                                            data-name="<?= htmlspecialchars($row['holiday_name'], ENT_QUOTES) ?>"
                                            data-date="<?= $row['holiday_date'] ?>"
                                            data-type="<?= $row['holiday_type'] ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editHolidayModal">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<!-- Add shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addShiftForm">
                    <input type="hidden" name="action" value="add_shift">
                    <div class="mb-3">
                        <label>Shift Name</label>
                        <input type="text" class="form-control" name="shift_name" placeholder="e.g. Morning Shift">
                    </div>
                    <div class="mb-3">
                        <label>Start Time</label>
                        <input type="time" class="form-control" name="start_time">
                    </div>
                    <div class="mb-3">
                        <label>End Time</label>
                        <input type="time" class="form-control" name="end_time">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveShift('add')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Shift Modal -->
<div class="modal fade" id="editShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editShiftForm">
                    <input type="hidden" name="action" value="edit_shift">
                    <input type="hidden" name="shift_id" id="edit_shift_id">
                    <div class="mb-3">
                        <label>Shift Name</label>
                        <input type="text" class="form-control" name="shift_name" id="edit_shift_name">
                    </div>
                    <div class="mb-3">
                        <label>Start Time</label>
                        <input type="time" class="form-control" name="start_time" id="edit_shift_start">
                    </div>
                    <div class="mb-3">
                        <label>End Time</label>
                        <input type="time" class="form-control" name="end_time" id="edit_shift_end">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveShift('edit')">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addHolidayForm">
                    <input type="hidden" name="action" value="add_holiday">
                    <div class="mb-3">
                        <label>Holiday Name</label>
                        <input type="text" class="form-control" name="holiday_name" placeholder="e.g. Diwali">
                    </div>
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" class="form-control" name="holiday_date" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select class="form-select" name="holiday_type">
                            <option value="National">National</option>
                            <option value="Regional">Regional</option>
                            <option value="Optional">Optional</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveHoliday('add')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Holiday Modal -->
<div class="modal fade" id="editHolidayModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editHolidayForm">
                    <input type="hidden" name="action" value="edit_holiday">
                    <input type="hidden" name="holiday_id" id="edit_holiday_id">
                    <div class="mb-3">
                        <label>Holiday Name</label>
                        <input type="text" class="form-control" name="holiday_name" id="edit_holiday_name">
                    </div>
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" class="form-control" name="holiday_date" id="edit_holiday_date">
                    </div>
                    <div class="mb-3">
                        <label>Type</label>
                        <select class="form-select" name="holiday_type" id="edit_holiday_type">
                            <option value="National">National</option>
                            <option value="Regional">Regional</option>
                            <option value="Optional">Optional</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveHoliday('edit')">Save Changes</button>
            </div>
        </div>
    </div>
</div>


<script>

    $(document).on('click', '#shiftTable .btn-edit', function() {
        $('#edit_shift_id').val($(this).data('id'));
        $('#edit_shift_name').val($(this).data('name'));
        $('#edit_shift_start').val($(this).data('start'));
        $('#edit_shift_end').val($(this).data('end'));
    });

    $(document).on('click', '#holidayTable .btn-edit', function() {
        $('#edit_holiday_id').val($(this).data('id'));
        $('#edit_holiday_name').val($(this).data('name'));
        $('#edit_holiday_date').val($(this).data('date'));
        $('#edit_holiday_type').val($(this).data('type'));
    });

    function saveShift(mode) {
        var formId = mode === 'add' ? 'addShiftForm' : 'editShiftForm';
        var form = document.getElementById(formId);
        var formData = new FormData(form);
        $.ajax({
            url: 'shifts_holidays_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.trim().toLowerCase() === 'success') {
                    Swal.fire({ icon: 'success', title: 'Saved', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response });
                }
            }
        });
    }

    function saveHoliday(mode) {
        var formId = mode === 'add' ? 'addHolidayForm' : 'editHolidayForm';
        var form = document.getElementById(formId);
        var formData = new FormData(form);
        $.ajax({
            url: 'shifts_holidays_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.trim().toLowerCase() === 'success') {
                    Swal.fire({ icon: 'success', title: 'Saved', timer: 1500, showConfirmButton: false }).then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response });
                }
            }
        });
    }

    $('#shifts_holidays').addClass('active');

    $(document).ready(function() {
        $('#shiftTable').DataTable({ 
        pageLength: 10, searching: false, paging: false, info: false,
        language: { emptyTable: "No shifts found" }
        });
        $('#holidayTable').DataTable({ 
        pageLength: 25, order: [[2, 'asc']],
        language: { emptyTable: "No holidays found" }
        });
    });

</script>