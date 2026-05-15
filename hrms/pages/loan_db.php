<?php
include '../includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ─── GET EMPLOYEE DETAILS ─────────────────────────────────────────── */
if ($action === 'get_employee') {
    $uid = intval($_GET['user_id'] ?? 0);
    if (!$uid) {
        echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
        exit();
    }

    $sql = "SELECT u.user_id, u.user_name, u.doj,
                   d.designation_name,
                   dept.dept_name
            FROM users u
            LEFT JOIN designations d   ON u.designation_id = d.designation_id
            LEFT JOIN departments dept ON u.dept_id = dept.dept_id
            WHERE u.user_id = $uid AND u.dele_te = '0' AND u.is_left='no' 
            LIMIT 1";

    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo json_encode([
            'success'  => true,
            'user_name'=> $row['user_name'],
            'dept'     => $row['dept_name'] ?? '',
            'desig'    => $row['designation_name'] ?? '',
            'doj'      => $row['doj'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
    }
    exit();
}

/* ─── ADD LOAN / ADVANCE ────────────────────────────────────────────── */
if ($action === 'add_loan_advance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id      = intval($_POST['user_id'] ?? 0);
    $user_name    = mysqli_real_escape_string($conn, trim($_POST['user_name'] ?? ''));
    $dept         = mysqli_real_escape_string($conn, trim($_POST['dept'] ?? ''));
    $desig        = mysqli_real_escape_string($conn, trim($_POST['desig'] ?? ''));
    $doj          = mysqli_real_escape_string($conn, trim($_POST['doj'] ?? ''));
    $loan_type    = in_array($_POST['loan_type'] ?? '', ['Loan','Advance']) ? $_POST['loan_type'] : 'Loan';
    $amount       = floatval($_POST['amount'] ?? 0);
    $emi_amount   = floatval($_POST['emi_amount'] ?? 0);
    $interest_rate = floatval($_POST['interest_rate'] ?? 12);
    $start_month  = mysqli_real_escape_string($conn, trim($_POST['start_month'] ?? ''));
    $end_month    = mysqli_real_escape_string($conn, trim($_POST['end_month'] ?? ''));

    if (!$user_id || !$user_name || !$loan_type || !$amount || !$emi_amount || !$start_month) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
        exit();
    }

    $start_db = $start_month . '-01';
    $doj_db   = !empty($doj) ? "'$doj'" : 'NULL';
    $end_val  = !empty($end_month) ? "'" . $end_month . "-01'" : 'NULL';

    $ins = "INSERT INTO loan_advances
                (user_id, user_name, dept, desig, doj, loan_type, amount, emi_amount, interest_rate, start_month, end_month, status)
            VALUES
                ($user_id, '$user_name', '$dept', '$desig', $doj_db, '$loan_type', $amount, $emi_amount, $interest_rate, '$start_db', $end_val, 'Ongoing')";

    if (mysqli_query($conn, $ins)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

/* ─── GET LOAN DEDUCTIONS ───────────────────────────────────────────── */
if ($action === 'get_deductions') {
    $loan_id_filter = intval($_GET['loan_id'] ?? 0);
    $where = $loan_id_filter ? "WHERE ld.loan_id = $loan_id_filter" : '';

    $sql = "SELECT ld.*, la.loan_type
            FROM loan_deduction ld
            LEFT JOIN loan_advances la ON ld.loan_id = la.loan_id
            $where
            ORDER BY ld.loan_id DESC, ld.Month DESC";

    $res  = mysqli_query($conn, $sql);
    $rows = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $rows[] = $r;
        }
    }
    echo json_encode(['success' => true, 'data' => $rows]);
    exit();
}

/* ─── GET SINGLE LOAN FOR PDF ───────────────────────────────────────── */
if ($action === 'get_loan') {
    $loan_id = intval($_GET['loan_id'] ?? 0);
    $sql = "SELECT * FROM loan_advances WHERE loan_id = $loan_id LIMIT 1";
    $res = mysqli_query($conn, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Loan not found']);
    }
    exit();
}

/* ─── GET ACTIVE LOANS FOR DEDUCTION PROCESSING ─────────────────────── */
/*
 * Returns all Ongoing loans active in the selected month, with pre-calculated
 * deduction values. Marks each row as already_processed if a record already
 * exists in loan_deduction for that loan + month combination.
 *
 * Calculation rules:
 *  last_month_balance  = previous month's balance_amt  (or original loan amount for first month)
 *  interest            = ((last_month_balance × interest_rate / 100) / 365) × 26
 *  emi                 = emi_amount from loan_advances
 *  final_deduction     = emi + interest
 *  balance_amt         = max(0, last_month_balance − emi)
 */
if ($action === 'get_loans_for_deduction') {
    $month = trim($_GET['month'] ?? '');

    // Validate YYYY-MM format
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        echo json_encode(['success' => false, 'message' => 'Invalid month format. Expected YYYY-MM.']);
        exit();
    }

    $month_date = $month . '-01';          // YYYY-MM-01 for DATE comparisons
    $month_esc  = mysqli_real_escape_string($conn, $month);
    $month_date_esc = mysqli_real_escape_string($conn, $month_date);

    // All Ongoing loans active in this month
    $sql = "SELECT * FROM loan_advances
            WHERE start_month <= '$month_date_esc'
              AND (end_month IS NULL OR end_month = '' OR end_month >= '$month_date_esc')
              AND status = 'Ongoing'
            ORDER BY loan_id ASC";

    $res   = mysqli_query($conn, $sql);
    $loans = [];

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $loan_id = (int)$row['loan_id'];

            // ── Already processed this month? ──
            $chk = mysqli_query($conn,
                "SELECT * FROM loan_deduction
                 WHERE loan_id = $loan_id AND Month = '$month_esc'
                 LIMIT 1"
            );
            $already     = ($chk && mysqli_num_rows($chk) > 0);
            $saved_record = null;
            if ($already) {
                $sr = mysqli_fetch_assoc($chk);
                $saved_record = [
                    'last_month_balance' => floatval($sr['last_month_balance']),
                    'emi'                => floatval($sr['emi']),
                    'interest'           => floatval($sr['intrest']),
                    'final_deduction'    => floatval($sr['final_deduction']),
                    'balance_amt'        => floatval($sr['balance_amt']),
                ];
            }

            // ── Last month's balance ──
            $prev_month     = date('Y-m', strtotime($month_date . ' -1 month'));
            $prev_month_esc = mysqli_real_escape_string($conn, $prev_month);

            $prev = mysqli_query($conn,
                "SELECT loan_amt, balance_amt FROM loan_deduction
                 WHERE loan_id = $loan_id AND Month = '$prev_month_esc'
                 LIMIT 1"
            );

            if ($prev && mysqli_num_rows($prev) > 0) {
                $pr           = mysqli_fetch_assoc($prev);
                $last_balance = floatval($pr['balance_amt']);
                $loan_amt     = floatval($pr['loan_amt']);
            } else {
                // First EMI — start from the original loan amount
                $last_balance = floatval($row['amount']);
                $loan_amt     = floatval($row['amount']);
            }

            // ── Calculations ──
            $emi             = floatval($row['emi_amount']);
            $interest_rate   = floatval($row['interest_rate']);
            $interest        = round((($loan_amt * $interest_rate / 100) / 365) * 26, 2);
            $final_deduction = round($emi + $interest, 2);
            $balance_amt     = max(0, round($last_balance - $final_deduction, 2));

            $loans[] = [
                'loan_id'            => $loan_id,
                'user_id'            => (int)$row['user_id'],
                'user_name'          => $row['user_name'],
                'loan_type'          => $row['loan_type'],
                'loan_amt'           => floatval($row['amount']),
                'interest_rate'      => $interest_rate,
                'last_month_balance' => $last_balance,
                'emi'                => $emi,
                'interest'           => $interest,
                'final_deduction'    => $final_deduction,
                'balance_amt'        => $balance_amt,
                'already_processed'  => $already,
                'saved_record'       => $saved_record,
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $loans, 'month' => $month]);
    exit();
}

/* ─── PROCESS & SAVE MONTHLY DEDUCTIONS ─────────────────────────────── */
/*
 * Saves the deduction records for the selected month into loan_deduction.
 * Skips any loan that already has a record for that month (idempotent).
 * Automatically marks a loan as Paid when balance reaches 0.
 */
if ($action === 'process_monthly_deduction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $month = trim($_POST['month'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        echo json_encode(['success' => false, 'message' => 'Invalid month format.']);
        exit();
    }

    $month_esc   = mysqli_real_escape_string($conn, $month);
    $deductions  = json_decode($_POST['deductions'] ?? '[]', true);

    if (empty($deductions) || !is_array($deductions)) {
        echo json_encode(['success' => false, 'message' => 'No deduction data provided.']);
        exit();
    }

    $saved   = 0;
    $skipped = 0;
    $errors  = [];

    foreach ($deductions as $ded) {
        $loan_id  = intval($ded['loan_id']            ?? 0);
        $empid    = intval($ded['user_id']             ?? 0);
        $empname  = mysqli_real_escape_string($conn, $ded['user_name']          ?? '');
        $loan_amt = floatval($ded['loan_amt']          ?? 0);
        $last_bal = floatval($ded['last_month_balance']?? 0);
        $emi      = floatval($ded['emi']               ?? 0);
        $interest = floatval($ded['interest']          ?? 0);
        $final_d  = floatval($ded['final_deduction']   ?? 0);
        $balance  = floatval($ded['balance_amt']       ?? 0);

        if (!$loan_id || !$empid) { continue; }

        // Skip if already processed for this month
        $chk = mysqli_query($conn,
            "SELECT deduction_id FROM loan_deduction
             WHERE loan_id = $loan_id AND Month = '$month_esc' LIMIT 1"
        );
        if ($chk && mysqli_num_rows($chk) > 0) {
            $skipped++;
            continue;
        }

        $ins = "INSERT INTO loan_deduction
                    (empid, empname, loan_amt, last_month_balance, emi, intrest, final_deduction, balance_amt, Month, loan_id)
                VALUES
                    ($empid, '$empname', $loan_amt, $last_bal, $emi, $interest, $final_d, $balance, '$month_esc', $loan_id)";

        if (mysqli_query($conn, $ins)) {
            $saved++;

            // Auto-mark loan as Paid when balance reaches zero
            if ($balance <= 0) {
                mysqli_query($conn,
                    "UPDATE loan_advances SET status = 'Paid' WHERE loan_id = $loan_id"
                );
            }
        } else {
            $errors[] = "Loan #$loan_id: " . mysqli_error($conn);
        }
    }

    echo json_encode([
        'success' => empty($errors) || $saved > 0,
        'saved'   => $saved,
        'skipped' => $skipped,
        'errors'  => $errors,
    ]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);