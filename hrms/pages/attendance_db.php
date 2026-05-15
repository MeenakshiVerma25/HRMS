<?php
session_start();
include '../includes/db.php';

if (isset($_POST['action']) && $_POST['action'] == 'Mark Attendance') {

    $employee_id     = intval($_POST['attendance_user_id']);
    $attendance_date = $_POST['attendance_date'];
    $in_time         = $_POST['in_time'];
    $out_time        = $_POST['out_time'];
    $total_hours      = $_POST['total_hours'];

    if (empty($employee_id) || empty($attendance_date) || empty($in_time) || empty($out_time)) {
        echo "All fields required";
        exit();
    }

    // Insert / Update attendance
    $sql = "INSERT INTO attendance (user_id, attendance_date, in_time, out_time, total_hours)
            VALUES ('$employee_id', '$attendance_date', '$in_time', '$out_time', '$total_hours')
            ON DUPLICATE KEY UPDATE 
                in_time='$in_time',
                out_time='$out_time',
                total_hours='$total_hours'";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }

}

if (isset($_POST['action']) && $_POST['action'] == 'check_report') {

    $user_id = intval($_POST['user_id']);
    $month = date('m');
    $year = date('Y');

    $sql = "SELECT attendance_id FROM attendance 
            WHERE user_id='$user_id'
            AND MONTH(attendance_date)='$month'
            AND YEAR(attendance_date)='$year'
            LIMIT 1";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        echo "has_data";
    } else {
        echo "no_data";
    }

    exit();
}

?>