<?php
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if(isset($_POST['action']) && $_POST['action'] == 'add') {

    $title = $_POST['job_title'];
    $department = $_POST['Department'];
    $designation = $_POST['Designation'];
    $job_type = $_POST['Type'];
    $vacancies = $_POST['Vacancy'];
    $experience = $_POST['experience_required'];
    $location = $_POST['Location'];
    $salary = $_POST['Salary'];
    $workmode = $_POST['WorkMode'];
    $deadline = $_POST['ApplyBefore'];

    $created_by = $_SESSION['user_name'] ?? 'Unknown';

    if (empty($title) || empty($department) || empty($designation) || empty($job_type) || empty($vacancies) || empty($experience) || empty($location) || empty($salary) || empty($workmode) || empty($deadline)) {
        echo "All fields are required.";
        exit();
    }

    // primary key ID
    $que = "SELECT IFNULL(MAX(job_id), 0) AS id FROM jobs";
    $result = mysqli_query($conn, $que);
    $row = mysqli_fetch_assoc($result);
    $job_id = $row['id'] + 1;

    $uploadDir = "../JD/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $fileName = "";
    if(!empty($_FILES['Description']['name'])) {
        $ext = pathinfo($_FILES['Description']['name'], PATHINFO_EXTENSION);
        $fileName = "JD_" . $job_id . "." . $ext;
        move_uploaded_file($_FILES['Description']['tmp_name'], $uploadDir . $fileName);
    }

    $sql = "INSERT INTO jobs (job_title, dept_id, designation_id, job_type, vacancies, experience_required, location_id, salary, work_mode, last_date_to_apply, jd_file, created_by) 
            VALUES ('$title', '$department', '$designation', '$job_type', '$vacancies', '$experience', '$location', '$salary', '$workmode', '$deadline', '$fileName', '$created_by')";
            // echo $sql; die;
    if (mysqli_query($conn, $sql)) {
        echo "success"; 
    } else {
        echo "Error: " . mysqli_error($conn);
    }

}

if(isset($_POST['action']) && $_POST['action'] == 'edit') {

    $id = intval($_POST['edit_job_id']);
    $title = $_POST['edit_job_title'];
    $department = $_POST['edit_Department'];
    $designation = isset($_POST['edit_Designation']) ? $_POST['edit_Designation'] : '';    $type = $_POST['edit_Type'];
    $vacancies = $_POST['edit_Vacancy'];
    $experience = $_POST['edit_Experience'];
    $location = $_POST['edit_Location'];
    $salary = $_POST['edit_Salary'];
    $workMode = $_POST['edit_WorkMode'];
    $applyBy = $_POST['edit_ApplyBefore'];
    $status = $_POST['edit_Status'];
    
    $getOldDescription = mysqli_query($conn, "SELECT jd_file FROM jobs WHERE job_id=$id");
    $oldRow = mysqli_fetch_assoc($getOldDescription);
    if($oldRow){
        $oldDescription = $oldRow['jd_file'];
    } else {
        echo "Job not found";
        exit();
    }

    if (!empty($_FILES['edit_Description']['name'])) {
        $description = $_FILES['edit_Description']['name'];
        $tmp = $_FILES['edit_Description']['tmp_name'];
        move_uploaded_file($tmp, "../JD/" . $description);
    } else {
        $description = $oldDescription;
    }

    $updated_at = date('d-m-Y H:i:s');
    $updated_by = $_SESSION['user_name'];
    
    if(empty($id)) { echo "Invalid job ID."; exit(); }

    if(empty($title) || empty($department) || empty($designation) || empty($description) || empty($type) || empty($vacancies) || empty($experience) || empty($location) || empty($salary) || empty($workMode) || empty($applyBy) || empty($status)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "UPDATE jobs SET job_title = '$title', dept_id = $department, jd_file = '$description', 
            designation_id = $designation, job_type = '$type', vacancies = $vacancies, 
            experience_required = $experience, location_id = $location, salary = $salary, 
            work_mode = '$workMode', last_date_to_apply = '$applyBy', status = '$status', 
            updated_at = '$updated_at', updated_by = '$updated_by' WHERE job_id = $id";

    if (mysqli_query($conn, $sql)) {
        echo "Job updated successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

if(isset($_GET['action']) && $_GET['action'] == 'delete') {
    $job_id = $_GET['id'] ?? '';

    if(!empty($job_id)) {
        $sql = "UPDATE jobs SET isdeleted = '1' WHERE job_id = '$job_id'";
        
        if(mysqli_query($conn, $sql)) {
            header("Location: jobs.php");
            exit();
        } else {
            echo "Error deleting job: " . mysqli_error($conn);
        }
    }
}

?>