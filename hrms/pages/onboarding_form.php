<?php
include '../includes/header.php';

if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Access denied.</div></div>";
    include '../includes/footer.php'; exit();
}

$offer_id      = intval($_GET['offer_id']      ?? 0);
$candidate_id  = intval($_GET['candidate_id']  ?? 0);
$onboarding_id = intval($_GET['onboarding_id'] ?? 0);

if (!$offer_id || !$candidate_id) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Invalid parameters.</div></div>";
    include '../includes/footer.php'; exit();
}

// Fetch offer + candidate data
$sql = "SELECT o.*, c.full_name, c.email, c.phone,
        d.designation_name, l.location_name
        FROM offers o
        JOIN candidates c    ON o.candidate_id   = c.candidate_id
        LEFT JOIN designations d ON o.designation_id = d.designation_id
        LEFT JOIN locations    l ON o.location_id    = l.location_id
        WHERE o.offer_id = $offer_id AND o.candidate_id = $candidate_id";
$offer = mysqli_fetch_assoc(mysqli_query($conn, $sql));

if (!$offer) {
    echo "<div class='dashboard-wrapper'><div class='alert alert-danger'>Offer not found.</div></div>";
    include '../includes/footer.php'; exit();
}

// Fetch existing onboarding form if editing
$ob = [];
if ($onboarding_id) {
    $ob_row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM onboarding_forms WHERE onboarding_id = $onboarding_id"));
    if ($ob_row) $ob = $ob_row;
}

function val($ob, $key, $fallback = '') {
    return htmlspecialchars($ob[$key] ?? $fallback);
}
?>

<div class="dashboard-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="dash-section-title">
                <a href="onboarding.php" style="color:var(--text-secondary);text-decoration:none;font-size:16px">
                    <i class="fa fa-arrow-left me-2"></i>
                </a>
                Joining Form
            </h2>
            <p class="dash-section-sub">Complete joining details for <strong><?= htmlspecialchars($offer['full_name']) ?></strong></p>
        </div>
        <div style="text-align:right">
            <div style="font-size:13px;color:var(--text-secondary)">Designation: <strong><?= htmlspecialchars($offer['designation_name'] ?? '—') ?></strong></div>
            <div style="font-size:13px;color:var(--text-secondary)">DOJ: <strong><?= $offer['doj'] ?></strong></div>
            <div style="font-size:13px;color:var(--text-secondary)">CTC: <strong><?= $offer['ctc'] ?></strong></div>
        </div>
    </div>

    <!-- Stepper -->
    <div class="ob-stepper mb-4">
        <div class="ob-step active" data-step="personal"><i class="fa fa-user"></i><span>Personal</span></div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="bank"><i class="fa fa-university"></i><span>Bank</span></div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="nominee"><i class="fa fa-heart"></i><span>Nominee</span></div>
        <div class="ob-step-line"></div>
        <div class="ob-step" data-step="address"><i class="fa fa-map-marker-alt"></i><span>Address</span></div>
    </div>

    <div class="content-card">
        <form id="joiningForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="submit_form">
            <input type="hidden" name="candidate_id" value="<?= $candidate_id ?>">
            <input type="hidden" name="offer_id"     value="<?= $offer_id ?>">
            <input type="hidden" name="onboarding_id" value="<?= $onboarding_id ?>">
            <input type="hidden" name="existing_photo" value="<?= val($ob, 'photo') ?>">

            <!-- ── STEP 1: Personal Details ─────────────────────────────── -->
            <div class="ob-tab-content" id="tab-personal">
                <h5 class="ob-section-title"><i class="fa fa-user me-2" style="color:var(--accent)"></i>Personal Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name"
                               value="<?= val($ob, 'full_name', $offer['full_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" name="dob" value="<?= val($ob, 'dob') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" accept="image/*" class="form-control" name="profile_picture">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Adhar Card Number</label>
                        <input type="text" class="form-control" name="adharcard_no" value="<?= val($ob, 'adharcard_no') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Father's Name</label>
                        <input type="text" class="form-control" name="father_name" value="<?= val($ob, 'father_name') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Mother's Name</label>
                        <input type="text" class="form-control" name="mother_name" value="<?= val($ob, 'mother_name') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Select</option>
                            <?php foreach (['Male','Female','Other'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($ob['gender'] ?? '') == $g ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Marital Status</label>
                        <select name="marital_status" class="form-select">
                            <option value="">Select</option>
                            <?php foreach (['Single','Married','Divorced','Widowed'] as $m): ?>
                            <option value="<?= $m ?>" <?= ($ob['marital_status'] ?? '') == $m ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Blood Group</label>
                        <select name="blood_group" class="form-select">
                            <option value="">Select</option>
                            <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= ($ob['blood_group'] ?? '') == $bg ? 'selected' : '' ?>><?= $bg ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="phone"
                               value="<?= val($ob, 'phone', $offer['phone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" name="emergency_contact"
                               value="<?= val($ob, 'emergency_contact') ?>">
                    </div>
                </div>
                <div class="ob-btn-group mt-4">
                    <button type="button" class="btn-rose" onclick="nextStep('bank')">
                        Next: Bank Details <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- ── STEP 2: Bank Details ──────────────────────────────────── -->
            <div class="ob-tab-content d-none" id="tab-bank">
                <h5 class="ob-section-title"><i class="fa fa-university me-2" style="color:var(--accent)"></i>Bank Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Bank Name</label>
                        <input type="text" class="form-control" name="bank_name" value="<?= val($ob, 'bank_name') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Holder Name</label>
                        <input type="text" class="form-control" name="account_holder"
                               value="<?= val($ob, 'account_holder', $offer['full_name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Account Number</label>
                        <input type="text" class="form-control" name="account_number"
                               value="<?= val($ob, 'account_number') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IFSC Code</label>
                        <input type="text" class="form-control" name="ifsc_code"
                               value="<?= val($ob, 'ifsc_code') ?>"
                               style="text-transform:uppercase" maxlength="11">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PAN Number</label>
                        <input type="text" class="form-control" name="pan_number" value="<?= val($ob, 'pancard_no') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload Passbook Front Page</label>
                        <input type="file" class="form-control" accept="image/*" name="passbook_upload">
                    </div>
                </div>
                <div class="ob-btn-group mt-4">
                    <button type="button" class="btn btn-light" onclick="prevStep('personal')">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </button>
                    <button type="button" class="btn-rose" onclick="nextStep('nominee')">
                        Next: Nominee <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- ── STEP 3: Nominee Details ───────────────────────────────── -->
            <div class="ob-tab-content d-none" id="tab-nominee">
                <h5 class="ob-section-title"><i class="fa fa-heart me-2" style="color:var(--accent)"></i>Nominee Details</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nominee Name</label>
                        <input type="text" class="form-control" name="nominee_name" value="<?= val($ob, 'nominee_name') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Relationship</label>
                        <select name="nominee_relation" id="" class="form-control">
                            <option value="">Select Relationship</option>
                            <option value="Spouse" <?= val($ob, 'nominee_relation') == 'Spouse' ? 'selected' : '' ?>>Spouse</option>
                            <option value="Father" <?= val($ob, 'nominee_relation') == 'Father' ? 'selected' : '' ?>>Father</option>
                            <option value="Mother" <?= val($ob, 'nominee_relation') == 'Mother' ? 'selected' : '' ?>>Mother</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nominee Date of Birth</label>
                        <input type="date" class="form-control" name="nominee_dob" value="<?= val($ob, 'nominee_dob') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nominee Phone</label>
                        <input type="text" class="form-control" name="nominee_phone" value="<?= val($ob, 'nominee_phone') ?>">
                    </div>
                </div>
                <div class="ob-btn-group mt-4">
                    <button type="button" class="btn btn-light" onclick="prevStep('bank')">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </button>
                    <button type="button" class="btn-rose" onclick="nextStep('address')">
                        Next: Address <i class="fa fa-arrow-right ms-1"></i>
                    </button>
                </div>
            </div>

            <!-- ── STEP 4: Address Details ───────────────────────────────── -->
            <div class="ob-tab-content d-none" id="tab-address">
                <h5 class="ob-section-title"><i class="fa fa-map-marker-alt me-2" style="color:var(--accent)"></i>Address Details</h5>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Current Address</label>
                        <textarea class="form-control" name="current_address" rows="2"><?= val($ob, 'current_address') ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Permanent Address</label>
                        <textarea class="form-control" name="permanent_address" rows="2"><?= val($ob, 'permanent_address') ?></textarea>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" id="sameAddress">
                            <label class="form-check-label" for="sameAddress" style="font-size:13px;color:var(--text-secondary)">
                                Same as current address
                            </label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" value="<?= val($ob, 'city') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">State</label>
                        <input type="text" class="form-control" name="state" value="<?= val($ob, 'state') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pincode</label>
                        <input type="text" class="form-control" name="pincode" maxlength="10" value="<?= val($ob, 'pincode') ?>">
                    </div>
                </div>
                <div class="ob-btn-group mt-4">
                    <button type="button" class="btn btn-light" onclick="prevStep('nominee')">
                        <i class="fa fa-arrow-left me-1"></i> Back
                    </button>
                    <button type="button" class="btn-rose" onclick="submitForm()">
                        <i class="fa fa-save me-1"></i>
                        <?= $onboarding_id ? 'Update Joining Form' : 'Save Joining Form' ?>
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
/* ── Stepper ────────────────────────────────────────────────── */
.ob-stepper {
    display: flex;
    align-items: center;
}
.ob-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    cursor: pointer;
}
.ob-step i {
    width: 40px; height: 40px;
    border-radius: 50%;
    background: var(--card-bg);
    border: 2px solid var(--border-color);
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    color: var(--text-secondary);
    transition: all .2s;
}
.ob-step span {
    font-size: 11px;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
}
.ob-step.active i {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
}
.ob-step.active span { color: var(--accent); }
.ob-step.done i {
    background: #37a55a22;
    border-color: #37a55a;
    color: #37a55a;
}
.ob-step-line {
    flex: 1;
    height: 2px;
    background: var(--border-color);
    margin: 0 8px;
    margin-bottom: 16px;
}
/* ── Section Title ─────────────────────────────────────────── */
.ob-section-title {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid var(--border-color);
}
/* ── Button Group ──────────────────────────────────────────── */
.ob-btn-group {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid var(--border-color);
    padding-top: 16px;
}
</style>

<script>
    $('#onboarding').addClass('active');

    var steps = ['personal', 'bank', 'nominee', 'address'];

    function updateStepper(activeStep) {
        var reached = false;
        steps.forEach(function(s) {
            var el = $('[data-step="' + s + '"]');
            if (s === activeStep) {
                el.removeClass('done').addClass('active');
                reached = true;
            } else if (!reached) {
                el.removeClass('active').addClass('done');
            } else {
                el.removeClass('active done');
            }
        });
    }

    function showTab(step) {
        $('.ob-tab-content').addClass('d-none');
        $('#tab-' + step).removeClass('d-none');
        updateStepper(step);
    }

    function nextStep(step) { showTab(step); }
    function prevStep(step) { showTab(step); }

    // Same address checkbox
    $('#sameAddress').on('change', function() {
        if ($(this).is(':checked')) {
            $('[name="permanent_address"]').val($('[name="current_address"]').val());
        }
    });

    // IFSC uppercase
    $('[name="ifsc_code"]').on('input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    function submitForm() {
        var formData = new FormData(document.getElementById('joiningForm'));

        // Basic validation
        if (!formData.get('full_name').trim()) {
            Swal.fire('Error', 'Full name is required.', 'error');
            showTab('personal'); return;
        }

        $.ajax({
            url: 'onboarding_db.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                try { var res = JSON.parse(response); } catch(e) { var res = {status:'error', message:response}; }
                if (res.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Form Saved!',
                        text: 'Joining form has been saved successfully.',
                        timer: 1800,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'onboarding_docs.php?onboarding_id=' + res.onboarding_id;
                    });
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    }
</script>
