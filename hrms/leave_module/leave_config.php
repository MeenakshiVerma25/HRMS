<?php

$LEAVE_TYPES = [
    1 => ['name' => 'Casual Leave',    'max_days' => 12],
    2 => ['name' => 'Sick Leave',      'max_days' => 12],
];

// Working days between two dates inclusive (excludes Sundays only)
function calcWorkingDays($from_date, $to_date) {
    $start  = new DateTime($from_date);
    $end    = new DateTime($to_date);
    $end->modify('+1 day');
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
    $days   = 0;
    foreach ($period as $d) {
        if ($d->format('N') != 7) $days++; // exclude Sunday
    }
    return max(0, $days);
}

// Derive leave balance from approved applications
function getLeaveBalance($conn, $user_id, $leave_type_id, $year, $max_days) {
    $uid  = intval($user_id);
    $ltid = intval($leave_type_id);
    $yr   = intval($year);
    $row  = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(total_days), 0) AS used
         FROM leave_applications
         WHERE user_id=$uid
           AND leave_type_id=$ltid
           AND YEAR(from_date)=$yr
           AND status='Approved' "));
    $used      = floatval($row['used'] ?? 0);
    $remaining = max(0, $max_days - $used);
    return ['entitled' => $max_days, 'used' => $used, 'remaining' => $remaining];
}
?>