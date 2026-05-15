<?php
session_start();
include '../includes/db.php';
include 'leave_config.php';

$action = $_POST['action'] ?? '';

if ($action === 'get_user_details') {
    header('Content-Type: application/json');

    $id = trim($_POST['id'] ?? '');

    if (!$id) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Invalid user id'
        ]);
        exit;
    }

    $uid = mysqli_real_escape_string($conn, $id);

    $user = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT user_id, user_name, user_email
         FROM users
         WHERE user_id='$uid' AND dele_te='0' AND is_left='no' "));

    if (!$user) {
        echo json_encode([
            'ok'  => false,
            'msg' => 'Invalid user id'
        ]);
        exit;
    }

    $history = [];
    $hq = mysqli_query($conn,
        "SELECT application_id, leave_type_id, from_date, to_date,
                total_days, status, applied_at, remarks
         FROM leave_applications
         WHERE user_id='{$user['user_id']}'
         ORDER BY applied_at DESC
         LIMIT 20");

    if ($hq) {
        while ($row = mysqli_fetch_assoc($hq)) {
            $history[] = [
                'application_id'  => $row['application_id'],
                'leave_type_id'   => $row['leave_type_id'],
                'leave_type_name' => $LEAVE_TYPES[$row['leave_type_id']]['name'] ?? '—',
                'from_date'       => date('d M Y', strtotime($row['from_date'])),
                'to_date'         => date('d M Y', strtotime($row['to_date'])),
                'total_days'      => $row['total_days'],
                'applied_at'      => date('d M Y', strtotime($row['applied_at'])),
                'status'          => $row['status'],
                'remarks'         => !empty($row['remarks']) ? $row['remarks'] : '—'
            ];
        }
    }

    echo json_encode([
        'ok'         => true,
        'user_id'    => $user['user_id'],
        'user_name'  => $user['user_name'],
        'user_email' => $user['user_email'],
        'history'    => $history
    ]);
    exit;
}


if ($action === 'get_balance') {
    header('Content-Type: application/json');

    $id   = trim($_POST['id'] ?? '');
    $ltid = intval($_POST['leave_type_id'] ?? 0);
    $from = trim($_POST['from_date'] ?? '');

    if (!$id || !$ltid || !$from || !isset($LEAVE_TYPES[$ltid])) {
        echo json_encode(['ok' => false]);
        exit;
    }

    $uid = mysqli_real_escape_string($conn, $id);

    $user = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT user_id
         FROM users
         WHERE user_id='$uid' AND dele_te='0' AND is_left='no' "));

    if (!$user) {
        echo json_encode(['ok' => false]);
        exit;
    }

    $year = date('Y', strtotime($from));
    $max  = $LEAVE_TYPES[$ltid]['max_days'];
    $bal  = getLeaveBalance($conn, $user['user_id'], $ltid, $year, $max);

    echo json_encode([
        'ok'        => true,
        'entitled'  => $bal['entitled'],
        'used'      => $bal['used'],
        'remaining' => $bal['remaining']
    ]);
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {

    $id            = trim($_POST['id'] ?? '');
    $leave_type_id = intval($_POST['leave_type_id'] ?? 0);
    $from_date     = trim($_POST['from_date'] ?? '');
    $to_date       = trim($_POST['to_date'] ?? '');
    $reason        = trim($_POST['reason'] ?? '');

    if (!$id || !$leave_type_id || !$from_date || !$to_date) {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = "All fields are required.";
        header("Location: leave.php");
        exit;
    }

    if (!isset($LEAVE_TYPES[$leave_type_id])) {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = "Invalid leave type.";
        header("Location: leave.php");
        exit;
    }

    if ($from_date > $to_date) {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = "From date cannot be after To date.";
        header("Location: leave.php");
        exit;
    }

    $uid = mysqli_real_escape_string($conn, $id);

    $user_row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT user_id, user_name
         FROM users
         WHERE user_id='$uid' AND dele_te='0' AND is_left='no' "));

    if (!$user_row) {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = "Invalid user id.";
        header("Location: leave.php");
        exit;
    }

    $user_id = intval($user_row['user_id']);

    $total_days = calcWorkingDays($from_date, $to_date);

    if ($total_days < 1) {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = "Leave must span at least 1 working day (Sundays excluded).";
        header("Location: leave.php");
        exit;
    }

    $year = date('Y', strtotime($from_date));
    $max  = $LEAVE_TYPES[$leave_type_id]['max_days'];
    $bal  = getLeaveBalance($conn, $user_id, $leave_type_id, $year, $max);

    if ($total_days > $bal['remaining']) {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = "Insufficient leave balance. You have {$bal['remaining']} day(s) remaining for the selected leave type.";
        header("Location: leave.php");
        exit;
    }

    $sf = mysqli_real_escape_string($conn, $from_date);
    $st = mysqli_real_escape_string($conn, $to_date);

    $ovl = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) AS n
         FROM leave_applications
         WHERE user_id=$user_id
           AND status IN ('Pending','Approved')
           AND from_date <= '$st'
           AND to_date >= '$sf'"));

    if (intval($ovl['n']) > 0) {
        header("Location: leave.php?status=error&msg=" . urlencode("You already have an overlapping leave for those dates."));
        exit;
    }

    $rs = mysqli_real_escape_string($conn, $reason);

    $ins = mysqli_query($conn,
        "INSERT INTO leave_applications
            (user_id, leave_type_id, from_date, to_date, total_days, reason, status, applied_at)
         VALUES
            ($user_id, $leave_type_id, '$sf', '$st', $total_days, '$rs', 'Pending', NOW())");

    if ($ins) {
        $_SESSION['flash_status'] = 'success';
        header("Location: leave.php");
    } else {
        $_SESSION['flash_status'] = 'error';
        $_SESSION['flash_msg'] = $msg; // your error message
        header("Location: leave.php");
    }
    exit;
}


header("Location: leave.php");
exit;
?>