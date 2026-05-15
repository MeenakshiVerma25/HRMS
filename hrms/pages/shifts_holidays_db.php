<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$created_by = $_SESSION['user_name'] ?? 'Unknown';

// add shift
if (isset($_POST['action']) && $_POST['action'] == 'add_shift') {
    $shift_name = $_POST['shift_name'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];

    if (empty($shift_name) || empty($start_time) || empty($end_time)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "INSERT INTO shifts (shift_name, start_time, end_time, created_by)
            VALUES ('$shift_name', '$start_time', '$end_time', '$created_by')";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

// edit shift
if (isset($_POST['action']) && $_POST['action'] == 'edit_shift') {
    $shift_id   = intval($_POST['shift_id']);
    $shift_name = $_POST['shift_name'];
    $start_time = $_POST['start_time'];
    $end_time   = $_POST['end_time'];

    if (empty($shift_name) || empty($start_time) || empty($end_time)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "UPDATE shifts SET shift_name='$shift_name', start_time='$start_time', end_time='$end_time'
            WHERE shift_id=$shift_id";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

// add holiday
if (isset($_POST['action']) && $_POST['action'] == 'add_holiday') {
    $holiday_name = $_POST['holiday_name'];
    $holiday_date = $_POST['holiday_date'];
    $holiday_type = $_POST['holiday_type'];

    if (empty($holiday_name) || empty($holiday_date) || empty($holiday_type)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "INSERT INTO holidays (holiday_name, holiday_date, holiday_type, created_by)
            VALUES ('$holiday_name', '$holiday_date', '$holiday_type', '$created_by')";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

// edit holiday
if (isset($_POST['action']) && $_POST['action'] == 'edit_holiday') {
    $holiday_id   = intval($_POST['holiday_id']);
    $holiday_name = $_POST['holiday_name'];
    $holiday_date = $_POST['holiday_date'];
    $holiday_type = $_POST['holiday_type'];

    if (empty($holiday_name) || empty($holiday_date) || empty($holiday_type)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "UPDATE holidays SET holiday_name='$holiday_name', holiday_date='$holiday_date', holiday_type='$holiday_type'
            WHERE holiday_id=$holiday_id";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

?>
