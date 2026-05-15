<?php         
include '../includes/db.php';          
include '../leave_module/leave_config.php';          

$action = $_REQUEST['action'] ?? '';

if ($action === 'review_application') {

    $app_id  = intval($_POST['application_id'] ?? 0);
    $status  = in_array($_POST['status'] ?? '', ['Approved', 'Rejected', 'Pending'])
               ? $_POST['status'] : '';
    $remarks = mysqli_real_escape_string($conn, trim($_POST['remarks'] ?? ''));
    $by      = mysqli_real_escape_string($conn, $_SESSION['user_name'] ?? 'HR');

    if (!$app_id || !$status) {
        echo 'Missing required data.'; exit;
    }

    // Fetch existing application
    $app = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM leave_applications WHERE application_id=$app_id"));

    if (!$app) {
        echo 'Application not found.'; exit;
    }

    // When approving, re-check balance to prevent race conditions
    if ($status === 'Approved') {
        $uid   = intval($app['user_id']);
        $lt_id = intval($app['leave_type_id']);
        $year  = date('Y', strtotime($app['from_date']));
        $max   = $LEAVE_TYPES[$lt_id]['max_days'] ?? 0;

        $bal = getLeaveBalance($conn, $uid, $lt_id, $year, $max);

        // Exclude the current application's days if it was previously Approved
        $already_approved_days = ($app['status'] === 'Approved') ? floatval($app['total_days']) : 0;
        $effective_remaining   = $bal['remaining'] + $already_approved_days;

        if (floatval($app['total_days']) > $effective_remaining) {
            echo "Cannot approve: employee only has {$effective_remaining} day(s) remaining for this leave type.";
            exit;
        }
    }

    // Update the record
    $upd = mysqli_query($conn,
        "UPDATE leave_applications
         SET status='$status',
             reviewed_by='$by',
             reviewed_at=NOW(),
             remarks='$remarks'
         WHERE application_id=$app_id");

    echo $upd ? 'success' : 'DB error: ' . mysqli_error($conn);
    exit;
}

echo 'Unknown action.';
?>