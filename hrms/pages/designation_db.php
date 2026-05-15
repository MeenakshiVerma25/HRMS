<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}
if(isset($_GET['action']) && ($_GET['action'] == 'save')) {
    $department = $_POST['dept_name'];
    $new_designation = $_POST['designation_name'];

    if (empty($department) || empty($new_designation)) {
        echo "Department and Designation names cannot be empty.";
        exit();
    }

    $sql = "INSERT INTO designations (dept_id, designation_name) VALUES ('$department', '$new_designation')";
    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

if(isset($_POST['action'])  && ($_POST['action'] == 'edit')) {
    $id = intval($_POST['edit_designation_id']);
    $dept_id = intval($_POST['edit_dept_name']);
    $designation_name = $_POST['edit_designation_name'];

    if(empty($id)) { echo "Invalid designation ID."; exit(); }

    if(empty($dept_id) || empty($designation_name)) {
        echo "Department and Designation names cannot be empty.";
        exit();
    }

    $sql = "UPDATE designations SET dept_id = $dept_id, designation_name = '$designation_name' WHERE designation_id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "Designation updated successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

?>
