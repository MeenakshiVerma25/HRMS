<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $new_location = $_POST['location_name'];

    if (empty($new_location)) {
        echo "Location name cannot be empty.";
        exit();
    }

    $sql = "INSERT INTO locations (location_name) 
            VALUES ('$new_location')";

    if (mysqli_query($conn, $sql)) {
        echo "success";  
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $location_id = $_POST['edit_location_id'];
    $location_name = $_POST['edit_location_name'];

    if (empty($location_name)) {
        echo "Location name cannot be empty.";
        exit();
    }

    $sql = "UPDATE locations SET location_name = '$location_name' WHERE location_id = '$location_id'";

    if (mysqli_query($conn, $sql)) {
        echo "Location updated successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

?>