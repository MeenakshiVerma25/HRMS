<?php 
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.html');
    exit();
}

$role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['user_name'] ?? 'Unknown';

// submit / update joining form (step 1)
if (isset($_POST['action']) && $_POST['action'] === 'submit_form') {
// echo "<pre>"; print_r($_POST); exit();
    $candidate_id     = intval($_POST['candidate_id'] ?? 0);
    $offer_id         = intval($_POST['offer_id']     ?? 0);
    $onboarding_id_input = intval($_POST['onboarding_id'] ?? 0);

    // Personal
    $full_name        = trim($_POST['full_name']        ?? '');
    $adharcard_no     = trim($_POST['adharcard_no']     ?? '');

    $profile = $_POST['existing_photo'] ?? '';
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profile = time() . '_' . basename($_FILES['profile_picture']['name']);
        $profile_tmp = $_FILES['profile_picture']['tmp_name'];
        $upload_dir = '../images/profiles/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        move_uploaded_file($profile_tmp, $upload_dir . $profile);
    }

    $father_name      = trim($_POST['father_name']      ?? '');
    $mother_name      = trim($_POST['mother_name']      ?? '');
    $dob              = $_POST['dob']                   ?? '';
    $gender           = $_POST['gender']                ?? '';
    $marital_status   = $_POST['marital_status']        ?? '';
    $phone            = trim($_POST['phone']            ?? '');
    $emergency_contact= trim($_POST['emergency_contact']?? '');
    $blood_group      = trim($_POST['blood_group']      ?? '');

    // Bank
    $bank_name        = trim($_POST['bank_name']        ?? '');
    $account_number   = trim($_POST['account_number']   ?? '');
    $ifsc_code        = strtoupper(trim($_POST['ifsc_code'] ?? ''));
    $account_holder   = trim($_POST['account_holder']   ?? '');
    $pancard_no       = trim($_POST['pan_number']       ?? '');
    $passbook_upload = '';
    if (isset($_FILES['passbook_upload']) && $_FILES['passbook_upload']['error'] === UPLOAD_ERR_OK) {
        $passbook_upload = time() . '_' . basename($_FILES['passbook_upload']['name']);
        $passbook_tmp = $_FILES['passbook_upload']['tmp_name'];
        $upload_dir = '../employeedocs/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        move_uploaded_file($passbook_tmp, $upload_dir . $passbook_upload);
    }

    // Nominee
    $nominee_name     = trim($_POST['nominee_name']     ?? '');
    $nominee_relation = trim($_POST['nominee_relation'] ?? '');
    $nominee_dob      = $_POST['nominee_dob']           ?? '';
    $nominee_phone    = trim($_POST['nominee_phone']    ?? '');

    // Address
    $current_address  = trim($_POST['current_address']  ?? '');
    $permanent_address= trim($_POST['permanent_address']?? '');
    $city             = trim($_POST['city']             ?? '');
    $state            = trim($_POST['state']            ?? '');
    $pincode          = trim($_POST['pincode']          ?? '');

    if (empty($candidate_id) || empty($offer_id) || empty($full_name)) {
        echo json_encode(['status' => 'error', 'message' => 'Required fields missing.']); 
        exit();
    }

    $submitted_at = date('Y-m-d H:i:s');

    // Check if form already exists
    $check_sql = "SELECT onboarding_id FROM onboarding_forms 
                  WHERE candidate_id = $candidate_id AND offer_id = $offer_id";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update existing form
        $onboarding_id = mysqli_fetch_assoc($check_result)['onboarding_id'];
        
        $photo_sql = $profile ? "photo='$profile'," : "";

        $sql = "UPDATE onboarding_forms SET 
                full_name='$full_name',
                $photo_sql
                father_name='$father_name',
                mother_name='$mother_name',
                adharcard_no='$adharcard_no',
                dob='$dob',
                gender='$gender',
                marital_status='$marital_status',
                phone='$phone',
                emergency_contact='$emergency_contact',
                blood_group='$blood_group',
                bank_name='$bank_name',
                account_number='$account_number',
                ifsc_code='$ifsc_code',
                account_holder='$account_holder',
                pancard_no='$pancard_no',
                passbook_upload='$passbook_upload',
                nominee_name='$nominee_name',
                nominee_relation='$nominee_relation',
                nominee_dob='$nominee_dob',
                nominee_phone='$nominee_phone',
                current_address='$current_address',
                permanent_address='$permanent_address',
                city='$city',
                state='$state',
                pincode='$pincode',
                submitted_at='$submitted_at'
                WHERE onboarding_id = $onboarding_id";
        
        if (mysqli_query($conn, $sql)) {
            // Update users table if this candidate is already an employee
            $update_user_sql = "UPDATE users SET 
                                user_name='$full_name',
                                profile='$profile'
                                WHERE candidate_id = $candidate_id";
            mysqli_query($conn, $update_user_sql);
            
            echo json_encode([
                'status' => 'success',
                'onboarding_id' => $onboarding_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => mysqli_error($conn)
            ]);
        }
    } else {
        // Insert new form
        $sql = "INSERT INTO onboarding_forms 
                (candidate_id, offer_id, full_name, father_name, mother_name, adharcard_no, dob, gender, marital_status, photo,
                 phone, emergency_contact, blood_group, bank_name, account_number, ifsc_code, account_holder,
                 pancard_no, passbook_upload, nominee_name, nominee_relation, nominee_dob, nominee_phone, current_address, permanent_address, city, state, pincode, submitted_at)
                VALUES 
                ($candidate_id, $offer_id, '$full_name', '$father_name', '$mother_name', '$adharcard_no', '$dob', '$gender', '$marital_status',
                 '$profile', '$phone', '$emergency_contact', '$blood_group', '$bank_name', '$account_number', '$ifsc_code', '$account_holder',
                 '$pancard_no', '$passbook_upload', '$nominee_name', '$nominee_relation', '$nominee_dob', '$nominee_phone', '$current_address', '$permanent_address', '$city', '$state', '$pincode', '$submitted_at')";

        if(mysqli_query($conn, $sql)) {
            $onboarding_id = mysqli_insert_id($conn);

            // default checklist items
            $checklist_defaults = [
                'Offer Letter Signed',
                'ID Proof Submitted',
                'Address Proof Submitted',
                'Educational Certificates Submitted',
                'Bank Details Verified',
                'IT Asset Assigned',
                'System Access Granted',
                'Induction Completed',
                'Employee ID Generated',
            ];

            foreach ($checklist_defaults as $item) {
                $item = mysqli_real_escape_string($conn, $item);
                mysqli_query($conn, "INSERT INTO onboarding_checklist 
                    (onboarding_id, item_name) 
                    VALUES ($onboarding_id, '$item')");
            }

            // update offer onboarding_status
            mysqli_query($conn, "UPDATE offers 
                SET onboarding_status='Form Submitted' 
                WHERE offer_id=$offer_id");

            echo json_encode([
                'status' => 'success',
                'onboarding_id' => $onboarding_id
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => mysqli_error($conn)
            ]);
        }
    }
    exit();
}

// Upload document (step 2)
if (isset($_POST['action']) && $_POST['action'] === 'upload_doc') {

    $onboarding_id = intval($_POST['onboarding_id'] ?? 0);
    $doc_type      = mysqli_real_escape_string($conn, trim($_POST['doc_type'] ?? ''));

    if (empty($onboarding_id) || empty($doc_type) || empty($_FILES['doc_file']['name'])) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']); 
        exit();
    }

    $upload_dir = '../onboarding_docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext       = pathinfo($_FILES['doc_file']['name'], PATHINFO_EXTENSION);
    $file_name = 'OB' . $onboarding_id . '_' . preg_replace('/\s+/', '_', $doc_type) . '_' . time() . '.' . $ext;
    $dest      = $upload_dir . $file_name;

    if (!move_uploaded_file($_FILES['doc_file']['tmp_name'], $dest)) {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed.']); 
        exit();
    }

    $sql = "INSERT INTO onboarding_documents
            (onboarding_id, doc_type, file_name, uploaded_by)
            VALUES ($onboarding_id, '$doc_type', '$file_name', '$user_name')";

    if (mysqli_query($conn, $sql)) {
        mysqli_query($conn, "UPDATE offers o
            JOIN onboarding_forms f ON f.offer_id = o.offer_id
            SET o.onboarding_status = 'Documents Uploaded'
            WHERE f.onboarding_id = $onboarding_id
            AND o.onboarding_status IN ('Not Started','Form Submitted')");

        echo json_encode([
            'status' => 'success',
            'doc_id' => mysqli_insert_id($conn),
            'file_name' => $file_name
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => mysqli_error($conn)
        ]);
    }
    exit();
}

// Verify Document (Step 2)
if (isset($_POST['action']) && $_POST['action'] === 'verify_doc') {

    // error_log("Role: " . $role);
    // error_log("POST data: " . print_r($_POST, true));

    // if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    //     echo json_encode(['status' => 'error', 'message' => 'Access denied.']); 
    //     exit();
    // }

    $doc_id        = intval($_POST['doc_id'] ?? 0);
    $onboarding_id = intval($_POST['onboarding_id'] ?? 0);
    $verified      = intval($_POST['verified'] ?? 0);

    $sql = "UPDATE onboarding_documents 
            SET verified = $verified, 
                verified_at = NOW(), 
                verified_by = '$user_name' 
            WHERE doc_id = $doc_id";

    if (mysqli_query($conn, $sql)) {
        // Check if all documents are verified
        $check_sql = "SELECT COUNT(*) as total, SUM(verified) as verified_count 
                      FROM onboarding_documents 
                      WHERE onboarding_id = $onboarding_id";
        $res = mysqli_query($conn, $check_sql);
        $counts = mysqli_fetch_assoc($res);
        
        $total_docs = $counts['total'];
        $verified_count = $counts['verified_count'];

        // If all documents are verified, update status to 'Verified'
        if ($total_docs > 0 && $total_docs == $verified_count) {
            mysqli_query($conn, "UPDATE offers o
                JOIN onboarding_forms f ON f.offer_id = o.offer_id
                SET o.onboarding_status = 'Verified'
                WHERE f.onboarding_id = $onboarding_id");
        }
        
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => mysqli_error($conn)
        ]);
    }
    exit();
}

// Toggle Checklist Item (Step 2)
if (isset($_POST['action']) && $_POST['action'] === 'toggle_checklist') {

    $checklist_id = intval($_POST['checklist_id'] ?? 0);
    $is_completed = intval($_POST['is_completed'] ?? 0);
    $completed_at = $is_completed ? date('Y-m-d H:i:s') : 'NULL';
    $completed_by = $is_completed ? "'$user_name'" : "NULL";

    $sql = "UPDATE onboarding_checklist 
            SET is_completed = $is_completed,
                completed_at = " . ($is_completed ? "'$completed_at'" : "NULL") . ",
                completed_by = " . ($is_completed ? "'$user_name'" : "NULL") . "
            WHERE checklist_id = $checklist_id";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => mysqli_error($conn)
        ]);
    }
    exit();
}

// convert candidate to employee (step 3)
if (isset($_POST['action']) && $_POST['action'] === 'convert_to_employee') {

    // if (!in_array($role, ['Super_admin', 'HR_admin'])) {
    //     echo json_encode(['status' => 'error', 'message' => 'Access denied.']); 
    //     exit();
    // }

    $onboarding_id = intval($_POST['onboarding_id'] ?? 0);
    $user_role_new = trim($_POST['user_role'] ?? 'Employee');
    $user_password = trim($_POST['emp_password'] ?? '');

    if (empty($onboarding_id) || empty($user_password)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']); 
        exit();
    }

    // Fetch onboarding + offer details
    $sql = "SELECT f.*, o.designation_id, o.dept_id, o.location_id, o.ctc, o.doj, o.offer_id,
            c.email, c.job_id, c.candidate_id
            FROM onboarding_forms f
            JOIN offers o ON f.offer_id = o.offer_id
            JOIN candidates c ON f.candidate_id = c.candidate_id
            WHERE f.onboarding_id = '$onboarding_id'";
            // echo $sql; die;
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'Onboarding record not found.']); 
        exit();
    }
    $cid = $data['candidate_id'];
    // Check not already converted - FIXED: changed 'users' table check
    $dup_sql = "SELECT user_id FROM users WHERE candidate_id = '$cid'";
    $dup_result = mysqli_query($conn, $dup_sql);

    if (mysqli_num_rows($dup_result) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'This candidate is already an employee.']); 
        exit();
    }

    $created_by_me = $user_name;
    //$upload_dir = 'images/profiles/';
          //move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $data['photo']);

    $insert_sql = "INSERT INTO users 
        (user_name, user_email, password, user_role, profile, createdBy,
         candidate_id, onboarding_id, designation_id, dept_id, location_id, ctc, doj)
        VALUES (
            '" . mysqli_real_escape_string($conn, $data['full_name']) . "',
            '" . mysqli_real_escape_string($conn, $data['email']) . "',
            '" . mysqli_real_escape_string($conn, $user_password) . "',
            '" . mysqli_real_escape_string($conn, $user_role_new) . "',
            '" . mysqli_real_escape_string($conn, $data['photo']) . "',
            '$user_name',
            " . $data['candidate_id'] . ",
            $onboarding_id,
            " . ($data['designation_id'] ? $data['designation_id'] : 'NULL') . ",
            " . ($data['dept_id'] ? $data['dept_id'] : 'NULL') . ",
            " . ($data['location_id'] ? $data['location_id'] : 'NULL') . ",
            '" . mysqli_real_escape_string($conn, $data['ctc']) . "',
            '" . $data['doj'] . "'
        )";

    if (mysqli_query($conn, $insert_sql)) {
        $new_user_id = mysqli_insert_id($conn);

        // update offer status
        mysqli_query($conn, "UPDATE offers 
                            SET onboarding_status='Converted' 
                            WHERE offer_id=" . intval($data['offer_id']));

        // Update candidate status
        mysqli_query($conn, "UPDATE candidates 
                            SET status='Joined' 
                            WHERE candidate_id=" . intval($data['candidate_id']));

        // Auto-create salary master entry with pending status
        $check_salary = mysqli_query($conn, "SELECT salary_id FROM salary_master WHERE user_id = '$new_user_id' LIMIT 1");
        if (!$check_salary || mysqli_num_rows($check_salary) == 0) {
            $ctc_monthly = 0;
            if (!empty($data['ctc'])) {
                $clean_ctc = preg_replace('/[^0-9.]/', '', $data['ctc']);
                $ctc_monthly = floatval($clean_ctc);
            }

            mysqli_query($conn, "INSERT INTO salary_master
                (user_id, basic, hra, special_allowance, ta, other_allowance, effective_from, created_by, status)
                VALUES
                ('$new_user_id', 0, 0, 0, 0, 0, " . (!empty($data['doj']) ? "'" . $data['doj'] . "'" : "NULL") . ", '$user_name', 'Pending')");
        }

        echo json_encode([
            'status' => 'success',
            'user_id' => $new_user_id,
            'name' => $data['full_name']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => mysqli_error($conn)
        ]);
    }
    exit();
}

// get onboarding data (AJAX fetch for forms)
if (isset($_POST['action']) && $_POST['action'] === 'get_onboarding') {

    $onboarding_id = intval($_POST['onboarding_id'] ?? 0);

    if (!$onboarding_id) { 
        echo json_encode(null); 
        exit(); 
    }

    $sql = "SELECT * FROM onboarding_forms WHERE onboarding_id = $onboarding_id";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    echo json_encode($row);
    exit();
}

// get single employee details (AJAX)
if (isset($_POST['action']) && $_POST['action'] == 'get_profile') {  
    $user_id = intval($_POST['user_id'] ?? 0);   

    $sql = "SELECT u.*, f.*, o.offer_id, o.ctc, o.doj
            FROM users u
            LEFT JOIN onboarding_forms f ON u.onboarding_id = f.onboarding_id
            LEFT JOIN offers o ON f.offer_id = o.offer_id
            WHERE u.user_id = $user_id";

    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);

    if (!$data) {
        echo "<div class='text-danger'>No data found</div>";
        exit();
    }

    $docs_sql = "SELECT * FROM onboarding_documents WHERE onboarding_id = " . intval($data['onboarding_id']);
    $docs_res = mysqli_query($conn, $docs_sql);
?>

<div class="row">
    <div class="col-md-4 text-center">
        <?php if(!empty($data['profile']) && file_exists('../images/profiles/' . $data['profile'])): ?>
            <img src="../images/profiles/<?= htmlspecialchars($data['profile']) ?>" 
                 class="rounded-circle mb-3" 
                 width="120" height="120" 
                 style="object-fit: cover;">
        <?php else: ?>
            <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" 
                 style="width:120px;height:120px; background: var(--icon-bg); border: 2px solid var(--rose-deep);">
                <i class="fa fa-user fa-3x" style="color: var(--rose-mid);"></i>
            </div>
        <?php endif; ?>
        <h5><?= htmlspecialchars($data['user_name']) ?></h5>
        <p class="text-muted">
            <i class="fa fa-briefcase me-1"></i> <?= htmlspecialchars($data['user_role'] ?? 'Employee') ?>
        </p>
        <?php if(!empty($data['user_id'])): ?>
            <span class="badge-rose">ID: <?= htmlspecialchars($data['user_id']) ?></span>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <div class="row mb-3">
            <div class="col-6">
                <strong><i class="fa fa-envelope me-1"></i> Email:</strong><br>
                <?= htmlspecialchars($data['user_email'] ?? '—') ?>
            </div>
            <div class="col-6">
                <strong><i class="fa fa-phone me-1"></i> Phone:</strong><br>
                <?= htmlspecialchars($data['phone'] ?? '—') ?>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong><i class="fa fa-calendar me-1"></i> Date of Joining:</strong><br>
                <?= !empty($data['doj']) ? date('d-m-Y', strtotime($data['doj'])) : '—' ?>
            </div>
            <div class="col-6">
                <strong><i class="fa fa-money me-1"></i> CTC:</strong><br>
                ₹<?= number_format($data['ctc'] ?? 0, 2) ?>
            </div>
        </div>
        
        <hr>
        
        <h6><i class="fa fa-user-circle me-2"></i> Personal Details</h6>
        <div class="row mb-3">
            <div class="col-6">
                <strong>Father's Name:</strong><br>
                <?= htmlspecialchars($data['father_name'] ?? '—') ?>
            </div>
            <div class="col-6">
                <strong>Mother's Name:</strong><br>
                <?= ($data['mother_name']) ? htmlspecialchars($data['mother_name']) : '—' ?>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong>Date of Birth:</strong><br>
                <?= !empty($data['dob']) ? date('d-m-Y', strtotime($data['dob'])) : '—' ?>
            </div>
            <div class="col-6">
                <strong>Blood Group:</strong><br>
                <?= htmlspecialchars($data['blood_group'] ?? '—') ?>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong>Marital Status:</strong><br>
                <?= htmlspecialchars($data['marital_status'] ?? '—') ?>
            </div>
            <div class="col-6">
                <strong>Emergency Contact:</strong><br>
                <?= htmlspecialchars($data['emergency_contact'] ?? '—') ?>
            </div>
        </div>
        
        <hr>
        
        <h6><i class="fa fa-university me-2"></i> Bank Details</h6>
        <div class="row mb-3">
            <div class="col-6">
                <strong>Bank Name:</strong><br>
                <?= htmlspecialchars($data['bank_name'] ?? '—') ?>
            </div>
            <div class="col-6">
                <strong>Account Number:</strong><br>
                <?= htmlspecialchars($data['account_number'] ?? '—') ?>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-6">
                <strong>IFSC Code:</strong><br>
                <?= htmlspecialchars($data['ifsc_code'] ?? '—') ?>
            </div>
            <div class="col-6">
                <strong>PAN Number:</strong><br>
                <?= htmlspecialchars($data['pancard_no'] ?? '—') ?>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-6">
                <strong>Account Holder:</strong><br>
                <?= htmlspecialchars($data['account_holder'] ?? '—') ?>
            </div>
            <div class="col-6">
                <strong>Passbook:</strong><br>
                <?php if(!empty($data['passbook_upload'])): ?>
                    <a href="../employeedocs/<?= $data['passbook_upload'] ?>" target="_blank" style="color: var(--rose-mid); text-decoration: none;">
                        <i class="fa fa-eye"></i> View Passbook
                    </a>
                <?php else: ?>
                    <span style="color: var(--text-secondary);">—</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(!empty($data['nominee_name'])): ?>
        <hr>
        <h6><i class="fa fa-users me-2"></i> Nominee Details</h6>
        <div class="row mb-3">
            <div class="col-6">
                <strong>Nominee Name:</strong><br>
                <?= htmlspecialchars($data['nominee_name']) ?>
            </div>
            <div class="col-6">
                <strong>Relation:</strong><br>
                <?= htmlspecialchars($data['nominee_relation'] ?? '—') ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if($docs_res && mysqli_num_rows($docs_res) > 0): ?>
        <hr>
        <h6><i class="fa fa-folder-open me-2"></i> Documents</h6>
        <div style="list-style: none; padding-left: 0;">
            <?php while($d = mysqli_fetch_assoc($docs_res)): ?>
                <div style="padding: 8px 12px; margin-bottom: 6px; background: var(--bg-card); border: 1px solid var(--border); border-radius: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                    <span>
                        <i class="fa fa-file-pdf-o me-2"></i>
                        <?= htmlspecialchars($d['doc_type']) ?>
                    </span>
                    <div>
                        <a href="../onboarding_docs/<?= urlencode($d['file_name']) ?>" target="_blank" style="color: var(--rose-mid); text-decoration: none; margin-right: 10px;">
                            <i class="fa fa-eye"></i> View
                        </a>
                        <?php if($d['verified']): ?>
                            <span style="background: rgba(76, 175, 130, 0.15); color: #4caf82; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; border: 1px solid rgba(76, 175, 130, 0.3);">
                                <i class="fa fa-check-circle"></i> Verified
                            </span>
                        <?php else: ?>
                            <span style="background: rgba(255, 193, 7, 0.15); color: #ffc107; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; border: 1px solid rgba(255, 193, 7, 0.3);">
                                <i class="fa fa-clock-o"></i> Pending
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php exit();
}
?>