<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if(isset($_POST['action']) && $_POST['action'] == 'add') {
    $new_department = $_POST['dept_name'];

    if (empty($new_department)) {
        echo "Department name cannot be empty.";
        exit();
    }

    $sql = "INSERT INTO departments (dept_name) 
            VALUES ('$new_department')";

    if (mysqli_query($conn, $sql)) {
        echo "success";  
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

if(isset($_GET['action']) && $_GET['action'] == 'edit') {

    $dept_id = $_POST['edit_dept_id'];
    $dept_name = $_POST['edit_dept_name'];

    if (empty($dept_name)) {
        echo "Department name cannot be empty.";
        exit();
    }

    $sql = "UPDATE departments SET dept_name = '$dept_name' WHERE dept_id = $dept_id";

    if (mysqli_query($conn, $sql)) {
        echo "Department updated successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

?>