<?php
include '../includes/header.php';

if(!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$sql = "SELECT sm.*, u.user_name, d.designation_name
        FROM salary_master sm
        LEFT JOIN users u ON sm.user_id = u.user_id
        LEFT JOIN designations d ON u.designation_id = d.designation_id
        ORDER BY sm.salary_id DESC";
$result = mysqli_query($conn, $sql);

$users_sql = mysqli_query($conn, "SELECT user_id, user_name FROM users WHERE dele_te='0' AND is_left='no' ORDER BY user_name");

?>

<style>
    .badge-status{
        display:inline-flex;
        align-items:center;
        gap:5px;
        padding:4px 10px;
        border-radius:20px;
        font-size:0.73rem;
        font-weight:600;
    }
</style>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h2 class="dash-section-title">Salary Master</h2>
            <p class="dash-section-sub">Define salary structure for employees</p>
        </div>
        <button type="button" class="btn-rose" data-bs-toggle="modal" data-bs-target="#addSalaryModal">
            <i class="fa fa-plus me-2"></i>Add Salary Structure
        </button>
    </div>

    <div class="content-card datatables_wrapper">
        <div class="table-responsive">
            <table id="salaryTable" class="table dataTable">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Employee</th>
                        <th>Basic Salary</th>
                        <th>HRA</th>
                        <th>Special Allowance</th>
                        <th>TA</th>
                        <th>Other Allowances</th>
                        <th>Gross Salary</th>
                        <th>PF</th>
                        <th>ESIC</th>
                        <th>Telephone</th>
                        <th>Canteen</th>
                        <th>Net Gross</th>
                        <th>Effective From</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($result) > 0):
                            while ($row = mysqli_fetch_assoc($result)):
                                $total = $row['basic'] + $row['hra'] + $row['special_allowance'] + $row['ta'] + $row['other_allowance'];
                                $pf = round($row['basic'] * 0.12);
                                $esic = ($total > 0 && $total <= 21000) ? round($total * 0.0075, 2) : 0;
                                $telephone = 400; // Fixed telephone allowance
                                $canteen = 0;   // Fixed canteen allowance
                                $gross = $total;
                                $net = $gross - $pf - $esic - $telephone - $canteen;

                        ?>
                        <tr style="font-size: 0.875rem;">
                            <td><?= $row['salary_id'] ?></td>
                            <td><?= htmlspecialchars($row['user_name'] ?? '—') ?></td>
                            <td>&#8377;<?= number_format($row['basic']) ?></td>
                            <td>&#8377;<?= number_format($row['hra']) ?></td>
                            <td>&#8377;<?= number_format($row['special_allowance']) ?></td>
                            <td>&#8377;<?= number_format($row['ta']) ?></td>
                            <td>&#8377;<?= number_format($row['other_allowance']) ?></td>
                            <td><strong>&#8377;<?= number_format($gross) ?></strong></td>
                            <td>&#8377;<?= number_format($pf) ?></td>
                            <td>&#8377;<?= number_format($esic) ?></td>
                            <td>&#8377;<?= number_format($telephone) ?></td>
                            <td>&#8377;<?= number_format($canteen) ?></td>
                            <td><strong>&#8377;<?= number_format($net) ?></strong></td>
                            <td><?= $row['effective_from'] ? date('d M Y', strtotime($row['effective_from'])) : '—' ?></td>
                            <td>
                                <?php if (($row['status'] ?? 'Pending') === 'Active'): ?>
                                    <span class="badge-status" style="background:rgba(46,125,82,0.1);color:#2e7d52;border:1px solid rgba(46,125,82,0.25);">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status" style="background:rgba(201,151,90,0.13);color:#b87a2a;border:1px solid rgba(201,151,90,0.3);">
                                        Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="javascript:void(0)"
                                    class="btn-action btn-edit me-1"
                                    data-id="<?= $row['salary_id'] ?>"
                                    data-user="<?= $row['user_id'] ?>"
                                    data-basic="<?= $row['basic'] ?>"
                                    data-hra="<?= $row['hra'] ?>"
                                    data-da="<?= $row['special_allowance'] ?>"
                                    data-ta="<?= $row['ta'] ?>"
                                    data-other="<?= $row['other_allowance'] ?>"
                                    data-effective="<?= $row['effective_from'] ?>"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editSalaryModal"
                                    title="Edit">
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

<!-- Add salary modal -->
 <div class="modal fade" id="addSalaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSalaryForm">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label>Employee</label>
                        <select class="form-select" name="user_id" id="sal_user_id" required>
                            <option value="">— Select Employee —</option>
                            <?php
                            $users_r = mysqli_query($conn, "SELECT user_id, user_name FROM users WHERE dele_te='0' AND is_left='no' ORDER BY user_name");
                            while ($u = mysqli_fetch_assoc($users_r)): ?>
                                <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['user_name']) ?> (<?= $u['user_id'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Basic Salary</label>
                            <input type="number" class="form-control sal-input" name="basic" id="add_basic" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>HRA</label>
                            <input type="number" class="form-control sal-input" name="hra" id="add_hra" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Special Allowance</label>
                            <input type="number" class="form-control sal-input" name="special_allowance" id="add_special_allowance" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>TA (Travel Allow.)</label>
                            <input type="number" class="form-control sal-input" name="ta" id="add_ta" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Other Allowance</label>
                            <input type="number" class="form-control sal-input" name="other_allowance" id="add_other" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Effective From</label>
                            <input type="date" class="form-control" name="effective_from" id="add_effective">
                        </div>
                    </div>
                    <div class="p-2 rounded mb-2" style="background:var(--card-bg);border:1px solid var(--border-hi)">
                        <strong>Gross Salary: &#8377;<span id="add_gross">0</span></strong>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveSalary('add')">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- edit salary modal -->
<div class="modal fade" id="editSalaryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Salary Structure</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSalaryForm">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="salary_id" id="edit_salary_id">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Basic Salary</label>
                            <input type="number" class="form-control edit-sal-input" name="basic" id="edit_basic" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>HRA</label>
                            <input type="number" class="form-control edit-sal-input" name="hra" id="edit_hra" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Special Allowance</label>
                            <input type="number" class="form-control edit-sal-input" name="special_allowance" id="edit_special_allowance" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3"> 
                            <label>TA (Travel Allow.)</label>
                            <input type="number" class="form-control edit-sal-input" name="ta" id="edit_ta" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Other Allowance</label>
                            <input type="number" class="form-control edit-sal-input" name="other_allowance" id="edit_other" placeholder="0" min="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Effective From</label>
                            <input type="date" class="form-control" name="effective_from" id="edit_effective">  
                        </div>
                    </div>
                    <div class="p-2 rounded mb-2" style="background:var(--card-bg);border:1px solid var(--border-hi)">
                        <strong>Gross Salary: &#8377;<span id="edit_gross">0</span></strong>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="saveSalary('edit')">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).on('input', '.sal-input', function() {
        let basic = parseFloat($('#add_basic').val()) || 0;
        let hra = parseFloat($('#add_hra').val()) || 0;
        let special_allowance = parseFloat($('#add_special_allowance').val()) || 0;
        let ta = parseFloat($('#add_ta').val()) || 0;
        let other = parseFloat($('#add_other').val()) || 0;
        let gross = basic + hra + special_allowance + ta + other;
        $('#add_gross').text(gross.toLocaleString('en-IN'));
    });

    $(document).on('input', '.edit-sal-input', function() {
        var basic = parseFloat($('#edit_basic').val()) || 0;
        var hra   = parseFloat($('#edit_hra').val())   || 0;
        var special_allowance = parseFloat($('#edit_special_allowance').val()) || 0;
        var ta    = parseFloat($('#edit_ta').val())    || 0;
        var other = parseFloat($('#edit_other').val()) || 0;
        $('#edit_gross').text((basic + hra + special_allowance + ta + other).toLocaleString('en-IN'));
    });

    $(document).on('click', '.btn-edit', function() {
        $('#edit_salary_id').val($(this).data('id'));
        $('#edit_user_id').val($(this).data('user'));
        $('#edit_basic').val($(this).data('basic'));
        $('#edit_hra').val($(this).data('hra'));
        $('#edit_special_allowance').val($(this).data('special_allowance'));
        $('#edit_ta').val($(this).data('ta'));
        $('#edit_other').val($(this).data('other'));
        $('#edit_effective').val($(this).data('effective'));
        // Recalculate gross
        var basic = parseFloat($(this).data('basic')) || 0;
        var hra   = parseFloat($(this).data('hra'))   || 0;
        var special_allowance = parseFloat($(this).data('special_allowance')) || 0;
        var ta    = parseFloat($(this).data('ta'))    || 0;
        var other = parseFloat($(this).data('other')) || 0;
        $('#edit_gross').text((basic + hra + special_allowance + ta + other).toLocaleString('en-IN'));
    });

    function saveSalary(mode) {
        var formID = mode === 'add' ? 'addSalaryForm' : 'editSalaryForm';
        var form = document.getElementById(formID);
        
        var formData = new FormData(form);

        $.ajax({
            url: 'salary_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.trim().toLowerCase() === 'success') {
                    Swal.fire({ icon: 'success', title: 'Saved', text: 'Salary structure saved successfully', timer: 1500, showConfirmButton: false })
                        .then(() => location.reload());
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response });
                }
            }
        });
    }

    $('#salary_master').addClass('active');

    $(document).ready(function() {
        $('#salaryTable').DataTable({ pageLength: 10, order: [[0, 'desc']] });
    });

</script>