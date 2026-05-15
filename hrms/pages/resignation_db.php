<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';


if ($action === 'get_employee') {
    $uid = intval($_GET['user_id'] ?? 0);
    if (!$uid) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
        exit();
    }

    $sql = "SELECT u.user_id, u.user_name, u.doj,
                   d.designation_name AS desig,
                   dept.dept_name     AS dept
            FROM users u
            LEFT JOIN designations d   ON u.designation_id = d.designation_id
            LEFT JOIN departments dept ON d.dept_id = dept.dept_id
            WHERE u.user_id = $uid AND u.dele_te = '0' AND u.is_left='no' 
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if (!$res) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . mysqli_error($conn)]);
        exit();
    }
    if (mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo json_encode([
            'success'   => true,
            'user_id'   => $row['user_id'],
            'user_name' => $row['user_name'],
            'dept'      => $row['dept']  ?? '',
            'desig'     => $row['desig'] ?? '',
            'doj'       => $row['doj']   ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    exit();
}


if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id           = intval($_POST['user_id'] ?? 0);
    $user_name         = mysqli_real_escape_string($conn, trim($_POST['user_name'] ?? ''));
    $dept              = mysqli_real_escape_string($conn, trim($_POST['dept'] ?? ''));
    $desig             = mysqli_real_escape_string($conn, trim($_POST['desig'] ?? ''));
    $doj               = mysqli_real_escape_string($conn, trim($_POST['doj'] ?? ''));
    $resignation_date  = mysqli_real_escape_string($conn, trim($_POST['resignation_date'] ?? ''));
    $last_working_date = mysqli_real_escape_string($conn, trim($_POST['last_working_date'] ?? ''));

    if (!$user_id || !$user_name || !$resignation_date || !$last_working_date) {
        echo 'Please fill all required fields.';
        exit();
    }

    // Prevent duplicate active resignation for this employee
    $check = mysqli_query($conn, "SELECT resignation_id FROM resignation
                                   WHERE user_id = $user_id AND status != 'rejected' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        echo 'An active resignation already exists for this employee.';
        exit();
    }

    $doj_val = $doj ? "'$doj'" : "NULL";

    $ins = "INSERT INTO resignation
                (user_id, user_name, dept, desig, doj, resignation_date, last_working_date, status)
            VALUES
                ($user_id, '$user_name', '$dept', '$desig', $doj_val,
                 '$resignation_date', '$last_working_date', 'pending')";

    if (mysqli_query($conn, $ins)) {
        echo 'success';
    } else {
        echo 'Error: ' . mysqli_error($conn);
    }
    exit();
}


if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rid    = intval($_POST['resignation_id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));

    if (!$rid || !in_array($status, ['approved', 'rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    $resQry = mysqli_query($conn, "SELECT user_id, last_working_date FROM resignation WHERE resignation_id = $rid LIMIT 1");
    $resRow = mysqli_fetch_assoc($resQry);

    if (!$resRow) {
        echo json_encode(['success' => false, 'message' => 'Resignation record not found']);
        exit();
    }

    $user_id = (int)$resRow['user_id'];
    $last_working_date = !empty($resRow['last_working_date']) ? $resRow['last_working_date'] : null;

    $upd = "UPDATE resignation SET status = '$status' WHERE resignation_id = '$rid'";
    if (mysqli_query($conn, $upd)) {
        if ($status === 'approved') {
            // Set user as left
            mysqli_query($conn, "UPDATE users SET is_left = 'yes', left_date = '$last_working_date' WHERE user_id = $user_id");
        } elseif ($status === 'rejected') {
            // Optional: reset any interim changes if needed
            mysqli_query($conn, "UPDATE users SET is_left = 'no', left_date = NULL WHERE user_id = $user_id");
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}


if ($action === 'get_fff_preview') {
    $rid = intval($_GET['resignation_id'] ?? 0);
    if (!$rid) { echo json_encode(['success' => false, 'message' => 'Missing ID']); exit(); }

    $sql = "SELECT r.resignation_id, r.user_id, r.user_name, r.dept, r.desig, r.doj,
                   r.resignation_date, r.last_working_date,
                   sm.basic, sm.hra, sm.special_allowance, sm.ta, sm.other_allowance
            FROM resignation r
            LEFT JOIN salary_master sm
                   ON r.user_id = sm.user_id AND sm.isdelete = '0'
            WHERE r.resignation_id = $rid
            ORDER BY sm.salary_id DESC
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit();
    }
    $row = mysqli_fetch_assoc($res);

    // Tenure
    $tenure_text      = '';
    $years_of_service = 0;
    if ($row['doj'] && $row['last_working_date']) {
        $d1   = new DateTime($row['doj']);
        $d2   = new DateTime($row['last_working_date']);
        $diff = $d1->diff($d2);
        $years_of_service = $diff->y;
        $tenure_text = $diff->y . ' Years, ' . $diff->m . ' Months, ' . $diff->d . ' Days';
    }

    // Salary components
    $basic = floatval($row['basic'] ?? 0);
    $gross = $basic
           + floatval($row['hra']               ?? 0)
           + floatval($row['special_allowance']  ?? 0)
           + floatval($row['ta']                 ?? 0)
           + floatval($row['other_allowance']    ?? 0);

    // Gratuity: (Basic / 26) × 15 × Years  — only if ≥ 5 years service
    $gratuity_auto = ($years_of_service >= 5 && $basic > 0)
                   ? round(($basic / 26) * 15 * $years_of_service, 2)
                   : 0;

    echo json_encode([
        'success'            => true,
        'resignation_id'     => $row['resignation_id'],
        'user_id'            => $row['user_id'],
        'user_name'          => $row['user_name'],
        'dept'               => $row['dept'],
        'desig'              => $row['desig'],
        'doj'                => $row['doj'],
        'resignation_date'   => $row['resignation_date'],
        'last_working_date'  => $row['last_working_date'],
        'tenure'             => $tenure_text,
        'basic'              => $basic,
        'gross'              => $gross,
        'gratuity_auto'      => $gratuity_auto,
        'fff_statement_date' => date('Y-m-d'),
    ]);
    exit();
}