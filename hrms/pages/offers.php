<?php
include '../includes/header.php';

$sql = "SELECT offers.*, candidates.full_name, candidates.candidate_id,
        designations.designation_name, departments.dept_name, locations.location_name FROM offers
        LEFT JOIN candidates   ON offers.candidate_id   = candidates.candidate_id
        LEFT JOIN designations ON offers.designation_id = designations.designation_id
        LEFT JOIN departments  ON offers.dept_id      = departments.dept_id
        LEFT JOIN locations    ON offers.location_id    = locations.location_id
        WHERE offers.isdeleted = '0'
        ORDER BY offers.offer_id DESC";
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
                <h2 class="dash-section-title">Offer Management</h2>
                <p class="dash-section-sub">Choose whom to offer a position</p>
            </div>
            <button type="button" class="btn-rose"
                data-bs-toggle="modal" data-bs-target="#addOfferModal">
                <i class="fa fa-plus me-2"></i>Create Offer
            </button>
        </div>

        <div class="content-card dataTables_wrapper">
            <div class="table-responsive">
                <table id="offerTable" class="table dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Candidate</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Location</th>
                            <th>CTC</th>
                            <th>DOJ</th>
                            <th>Offer Date</th>
                            <th>Status</th>
                            <th>Decision</th>
                            <th>Letter</th>
                            <?php if(in_array($role, ['Super_admin','HR_admin'])): ?>
                                <th>Onboarding</th>
                            <?php endif; ?>
                            <?php if($role == 'Super_admin') { ?>
                                <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $status = $row['status'] ?? 'Pending';
                                $badgeCls = [
                                    'Pending' => 'badge-pending',
                                    'Accepted' => 'badge-selected'
                                ][$status] ?? 'badge-pending';
                        ?>
                            <tr style="font-size: 0.875rem;">
                                <td><?= $row['offer_id'] ?></td>
                                <td><?= $row['full_name'] ?></td>
                                <td><?= $row['designation_name'] ?></td>
                                <td><?= $row['dept_name'] ?></td>
                                <td><?= $row['location_name'] ?></td>
                                <td><?= $row['ctc'] ?></td>
                                <td><?= !empty($row['doj']) ? date('d-m-Y', strtotime($row['doj'])) : '-' ?></td>
                                <td><?= $row['offer_date'] ?></td>
                                <td>
                                    <span class="badge-status <?= $badgeCls ?>">
                                        <?php if ($status === 'Accepted') echo '<i class="fa-solid fa-check"></i> '; ?>
                                        <?php if ($status === 'Pending')  echo '<i class="fa-solid fa-clock"></i> '; ?>
                                        <?= ($status) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Accepted'): ?>
                                        <button class="act-btn reject-btn"
                                                onclick="updateOfferStatus(<?= $row['offer_id'] ?>,'Pending')"
                                                title="Set Pending">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="act-btn pending-btn"
                                                onclick="updateOfferStatus(<?= $row['offer_id'] ?>,'Accepted')"
                                                title="Accept">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                        <button class="act-btn reject-btn ms-1"
                                                onclick="updateOfferStatus(<?= $row['offer_id'] ?>,'Pending')"
                                                title="Set Pending">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <?php if($role == 'Super_admin'): ?>
                                    <td>
                                        <?php if($row['status'] == 'Accepted') { ?>
                                            <a href="javascript:void(0)" 
                                                class="btn-action btn-offer btn-view-letter"
                                                data-offer-id="<?= $row['offer_id'] ?>"
                                                data-candidate="<?= $row['full_name'] ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewLetterModal">
                                                    <i class="fa fa-file"></i>
                                                </a>
                                        <?php } else { ?> - <?php } ?>
                                    </td>
                                <?php endif; ?>
                                <?php if(in_array($role, ['Super_admin','HR_admin'])): ?>
                                    <?php
                                    $ob_check = mysqli_fetch_assoc(mysqli_query($conn,
                                        "SELECT onboarding_status FROM offers WHERE offer_id=" . intval($row['offer_id'])));
                                    $ob_status = $ob_check['onboarding_status'] ?? 'Not Started';
                                    $ob_id_check = mysqli_fetch_assoc(mysqli_query($conn,
                                        "SELECT onboarding_id FROM onboarding_forms WHERE offer_id=" . intval($row['offer_id'])));
                                    ?>
                                    <td>
                                        <?php if($row['status'] == 'Accepted'): ?>
                                            <?php if($ob_status === 'Converted'): ?>
                                                <span style="color:#37a55a;font-size:12px"><i class="fa fa-check-circle"></i> Joined</span>
                                            <?php else: ?>
                                                <a href="onboarding_form.php?offer_id=<?= $row['offer_id'] ?>&candidate_id=<?= $row['candidate_id'] ?><?php if($ob_id_check): ?>&onboarding_id=<?= $ob_id_check['onboarding_id'] ?><?php endif; ?>"
                                                   class="btn-action btn-edit" title="<?= $ob_status === 'Not Started' ? 'Start Onboarding' : 'Continue Onboarding (' . $ob_status . ')' ?>">
                                                    <i class="fa <?= $ob_status === 'Not Started' ? 'fa-play' : 'fa-redo' ?>"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color:var(--text-secondary)">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <?php if($role == 'Super_admin') { ?>
                                    <td class="text-center">
                                        <a href="javascript:void(0)" 
                                            class="btn-action btn-edit me-1"
                                            data-offer_id="<?= $row['offer_id'] ?>"
                                            data-candidate="<?= $row['full_name'] ?>"
                                            data-designation="<?= $row['designation_id'] ?>"
                                            data-dept="<?= $row['dept_id'] ?>"
                                            data-location="<?= $row['location_id'] ?>"
                                            data-ctc="<?= $row['ctc'] ?>"
                                            data-doj="<?= $row['doj'] ?>"
                                            data-offer_date="<?= $row['offer_date'] ?>"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editOfferModal">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                        <a href="offers_db.php?action=delete&id=<?= $row['offer_id'] ?>" 
                                            class="btn-action btn-delete">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                <?php } ?>
                            </tr>
                        <?php }  }?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../includes/footer.php'; ?>

<!-- View Letter Modal  -->
<div class="modal fade" id="viewLetterModal" tabindex="-1" aria-labelledby="viewLetterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header">
                <h3 class="modal-title" id="viewLetterModalLabel">
                    <i class="fa fa-file-alt me-2"></i>
                    Offer Letter &mdash; <span id="letterCandidateName"></span>
                </h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="flex: 1; overflow: hidden;">
                <iframe id="letterIframe"
                    src=""
                    style="width: 100%; height: 100%; border: none; min-height: 600px;"
                    title="Offer Letter Preview">
                </iframe>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">
                    <i class="fa fa-times me-1"></i> Close
                </button>
                <button type="button" class="btn-rose" id="downloadLetterBtn">
                    <i class="fa fa-download me-2"></i> Download Letter
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Offer Modal -->
<div class="modal fade" id="addOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add Offer</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addOfferForm" method="POST">
                    <input type="hidden" name = "action" value = "add">
                    <div class="mb-3">
                        <label>Candidate</label>
                        <select name="candidate" class="form-select" id="candidate">
                            <option value="">Select Candidate</option>
                            <?php 
                            $sql = "SELECT i.candidate_id, c.full_name 
                                    FROM interviews i
                                    JOIN candidates c ON i.candidate_id = c.candidate_id
                                    WHERE i.isdeleted = '0'
                                    AND i.candidate_id NOT IN (
                                        SELECT candidate_id FROM offers WHERE isdeleted = '0'
                                    )
                                    GROUP BY i.candidate_id, c.full_name
                                    HAVING COUNT(*) > 0
                                    AND SUM(CASE WHEN i.status = 'Selected' THEN 1 ELSE 0 END) = COUNT(*)
                                    ORDER BY i.candidate_id DESC;";
                            $candidate_result = mysqli_query($conn, $sql);
                            if(mysqli_num_rows($candidate_result) > 0){ 
                                while($candidate = mysqli_fetch_assoc($candidate_result)){  ?>
                                    <option value="<?= $candidate['candidate_id'] ?>"><?= $candidate['full_name'] ?></option>
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
                        <label>Designation</label>
                        <select name="designation_id" class="form-select" id="designation_id">
                            <option value="">Select Designation</option>
                            <?php
                            $sql = "SELECT designation_id, designation_name FROM designations ORDER BY designation_name ASC";
                            $row = mysqli_query($conn, $sql);
                            if($row && mysqli_num_rows($row) > 0) {
                                while($result = mysqli_fetch_assoc($row)) { ?>
                                    <option value="<?= $result['designation_id'] ?>"><?= htmlspecialchars($result['designation_name']) ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No designation available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Department</label>
                        <select name="dept_id" class="form-select" id="dept_id">
                            <option value="">Select Department</option>
                            <?php
                            $sql = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC";
                            $row = mysqli_query($conn, $sql);
                            if($row && mysqli_num_rows($row) > 0) {
                                while($result = mysqli_fetch_assoc($row)) { ?>
                                    <option value="<?= $result['dept_id'] ?>"><?= htmlspecialchars($result['dept_name']) ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No department available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <select name="location_id" class="form-select" id="location_id">
                            <option value="">Select Location</option>
                            <?php
                            $sql = "SELECT location_id, location_name FROM locations ORDER BY location_name ASC";
                            $loc_result = mysqli_query($conn, $sql);
                            if($loc_result && mysqli_num_rows($loc_result) > 0) {
                                while($loc = mysqli_fetch_assoc($loc_result)) { ?>
                                    <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                                <?php }
                            } else { ?>
                                <option value=''>No location available</option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>CTC (Monthly)</label>
                        <input type="text" class="form-control" id="ctc" name="ctc" placeholder="e.g. 5,00,000">
                    </div>
                    <div class="mb-3">
                        <label>Date of Joining</label>
                        <input type="date" class="form-control" id="doj" name="doj" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label>Offer Date</label>
                        <input type="date" class="form-control" id="offer_date" name="offer_date" value="<?= date('Y-m-d') ?>" >
                    </div>
                </form>               
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="addOffer()">Save</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Offer</h3>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editOfferForm" method="POST">
                    <input type="hidden" name = "edit_offer_id" id = "edit_offer_id">
                    <div class="mb-3">
                        <label>Candidate</label>
                        <input type="text" class="form-control" id="edit_candidate_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Designation</label>
                        <select name="edit_designation_id" class="form-select" id="edit_designation_id">
                            <option value="">Select Designation</option>
                            <?php
                            $sql = "SELECT designation_id, designation_name FROM designations ORDER BY designation_name ASC";
                            $result = mysqli_query($conn, $sql);
                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { ?>
                                    <option value="<?= $row['designation_id'] ?>"><?= $row['designation_name'] ?></option>
                                <?php } } else { ?>
                                    <option value="">No designations available</option>
                                <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Department</label>
                        <select name="edit_dept_id" class="form-select" id="edit_dept_id">
                            <option value="">Select Department</option>
                            <?php
                            $sql = "SELECT dept_id, dept_name FROM departments ORDER BY dept_name ASC";
                            $result = mysqli_query($conn, $sql);
                            if($result && mysqli_num_rows($result) > 0) {
                                while($row = mysqli_fetch_assoc($result)) { ?>
                                    <option value="<?= $row['dept_id'] ?>"><?= $row['dept_name'] ?></option>
                                <?php } } else { ?>
                                    <option value="">No departments available</option>
                                <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Location</label>
                        <select name="edit_location_id" class="form-select" id="edit_location_id">
                            <option value="">Select Location</option>
                            <?php
                            $sql = "SELECT location_id, location_name FROM locations ORDER BY location_name ASC";
                            $loc_result = mysqli_query($conn, $sql);
                            if($loc_result && mysqli_num_rows($loc_result) > 0) {
                                while($loc = mysqli_fetch_assoc($loc_result)) { ?>
                                    <option value="<?= $loc['location_id'] ?>"><?= htmlspecialchars($loc['location_name']) ?></option>
                                <?php }
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>CTC</label>
                        <input type="number" class="form-control" id="edit_ctc" name="edit_ctc">
                    </div>
                    <div class="mb-3">
                        <label>Date of Joining</label>
                        <input type="date" class="form-control" id="edit_doj" name="edit_doj">
                    </div>
                    <div class="mb-3">
                        <label>Offer Date</label>
                        <input type="date" class="form-control" id="edit_offer_date" name="edit_offer_date">
                    </div>   
                </form>               
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rose" onclick="editOffer()">Save</button>
            </div>
        </div>
    </div>
</div>

<script>
    $("#offers").addClass("active");

    $('#offerTable').DataTable({
        pageLength: 10,
        order: [[0, 'desc']]
    });

    function updateOfferStatus(offerId, status) {
        const label = status === 'Accepted' ? 'Accept' : 'Set Pending';

        Swal.fire({
            title: label + ' Offer?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: label,
            confirmButtonColor: '#c2637a',
            cancelButtonColor: '#6c757d'
        }).then((res) => {
            if (res.isConfirmed) {
                $.post('offers_db.php',
                    { action: 'update_status', offer_id: offerId, status: status },
                    function(response) {
                        const obj = typeof response === 'string' ? JSON.parse(response) : response;

                        if (obj.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated',
                                text: 'Offer status updated successfully',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => location.reload());
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: obj.message || 'Something went wrong'
                            });
                        }
                    }
                );
            }
        });
    }

    
    $(document).on("click", ".btn-edit", function() {
        $("#edit_offer_id").val($(this).data("offer_id"));
        $("#edit_candidate_name").val($(this).data("candidate"));
        $("#edit_designation_id").val($(this).data("designation"));
        $("#edit_dept_id").val($(this).data("dept"));
        $("#edit_location_id").val($(this).data("location"));
        $("#edit_doj").val($(this).data("doj"));
        $("#edit_offer_date").val($(this).data("offer_date"));
        $("#edit_ctc").val($(this).data("ctc"));

        $("#edit_offer_date").attr("max", $(this).data("doj"));

        if ($("#edit_offer_date").val() > $("#edit_doj").val()) {
            $("#edit_offer_date").val($("#edit_doj").val());
        }
        
    });

    //  View Letter Modal 
    $(document).on("click", ".btn-view-letter", function () {
        var offerId   = $(this).data("offer-id");
        var candidate = $(this).data("candidate");

        // Load the letter in preview mode (no auto-print)
        var letterUrl = 'create_offer.php?id=' + offerId + '&preview=1';

        $('#letterCandidateName').text(candidate);
        $('#letterIframe').attr('src', letterUrl);
    });

    // Clear iframe when modal is closed
    $('#viewLetterModal').on('hidden.bs.modal', function () {
        $('#letterIframe').attr('src', '');
    });

    // Download button: trigger the browser's print/save-as-PDF on the iframe content
    $('#downloadLetterBtn').on('click', function () {
        var iframe = document.getElementById('letterIframe');
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        }
    });

    document.addEventListener("DOMContentLoaded", function () {

        const doj = document.getElementById("doj");
        const offerDate = document.getElementById("offer_date");

        offerDate.max = doj.value;

        doj.addEventListener("change", function () {
            offerDate.max = this.value;

            if (offerDate.value > this.value) {
                offerDate.value = this.value;
            }
        });

        const editDoj = document.getElementById("edit_doj");
        const editOfferDate = document.getElementById("edit_offer_date");

        editDoj.addEventListener("change", function () {
            editOfferDate.max = this.value;

            if (editOfferDate.value > this.value) {
                editOfferDate.value = this.value;
            }
        });

    });

    $(document).on("click", ".btn-delete", function(e) {
        e.preventDefault();

        var link = $(this).attr("href");

        Swal.fire({
            title: 'Are you sure?',
            text: "This offer will be deleted!",
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

    function addOffer() {
        var formData = new FormData(document.getElementById('addOfferForm'));

        $.ajax({
            url: 'offers_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.trim().toLowerCase() === "success") {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Offer added successfully',
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

    function editOffer() {
        var formData = new FormData(document.getElementById('editOfferForm'));
        formData.append('action', 'edit');

        $.ajax({
            url: 'offers_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if(response.includes("successfully")) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        text: 'Offer updated successfully',
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