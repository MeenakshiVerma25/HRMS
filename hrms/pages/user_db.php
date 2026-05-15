<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if(isset($_POST['action']) && $_POST['action'] == 'add') {
    if(
        empty($_POST['user_name']) ||
        empty($_POST['user_email']) ||
        empty($_POST['user_password']) ||
        empty($_POST['user_role']) ||
        empty($_FILES['add_profile']['name'])
    ) {
        echo "All fields are required.";
        exit();
    }

    $user_name = $_POST['user_name'];
    $user_email = $_POST['user_email'];
    $user_password = $_POST['user_password'];
    $user_role = $_POST['user_role'];

    $profile = $_FILES['add_profile']['name'];
    $profile_tmp = $_FILES['add_profile']['tmp_name'];
    move_uploaded_file($profile_tmp, "../images/profiles/$profile");

    $created_by = $_SESSION['user_name'] ?? 'Unknown';

    $sql = "INSERT INTO users (user_name, user_email, password, user_role, profile, createdBy) VALUES ('$user_name', '$user_email', '$user_password', '$user_role', '$profile', '$created_by')";

    if(mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

}

// Replace the edit section (around line 28) with this:

if(isset($_POST['action']) && $_POST['action'] == 'edit') {
    $userId = intval($_POST['user_id']);

    $userName = trim($_POST['edit_user_name']);
    $userEmail = trim($_POST['edit_user_email']);
    $userPassword = trim($_POST['edit_user_password']);
    $userRole = trim($_POST['edit_user_role']);

    if(empty($userName) || empty($userEmail) || empty($userPassword) || empty($userRole)) {
        echo "All fields are required.";
        exit();
    }

    $getOldProfile = mysqli_query($conn, "SELECT profile FROM users WHERE user_id=$userId");
    $oldRow = mysqli_fetch_assoc($getOldProfile);
    if($oldRow){
        $oldProfile = $oldRow['profile'];
    } else {
        echo "User not found";
        exit();
    }

    if (!empty($_FILES['edit_profile']['name'])) {
        $profile = $_FILES['edit_profile']['name'];
        $tmp = $_FILES['edit_profile']['tmp_name'];
        move_uploaded_file($tmp, "../images/profiles/" . $profile);
    } else {
        $profile = $oldProfile;
    }

    $updated_at = date("d-m-Y H:i:s");
    $updated_by = $_SESSION['user_name'] ?? 'Unknown';

    $sql = "UPDATE users SET 
                user_name='$userName', 
                user_email='$userEmail', 
                password='$userPassword', 
                user_role='$userRole', 
                profile='$profile', 
                updatedAt='$updated_at', 
                updatedBy='$updated_by'
            WHERE user_id=$userId";

    if(mysqli_query($conn, $sql)) {
        echo "User updated successfully.";
    } else {
        echo "Error updating record: " . mysqli_error($conn);
    }

}

if(isset($_GET['action']) && $_GET['action'] == 'delete') {
    $user_id = $_GET['id'];

    if(isset($user_id)) {
        $sql = "UPDATE users SET dele_te = '1' WHERE user_id = '$user_id'";
        if(mysqli_query($conn, $sql)) {
            header("Location: show_users.php");
            exit();
        } else {
            echo "Error deleting user: " . mysqli_error($conn);
        }
    }

}

?>