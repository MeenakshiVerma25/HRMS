<?php 
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}
if(isset($_POST['action']) && $_POST['action'] == 'Save') {

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $job_id = $_POST['Job'];
    $education = $_POST['education'];
    $experience = $_POST['experience'];
    $apply_date = $_POST['apply_date'];
    $interview_date = $_POST['interview_date'];

    $resume = $_FILES['Resume']['name'];
    $resume_tmp = $_FILES['Resume']['tmp_name'];
    move_uploaded_file($resume_tmp, "../resume/$resume");

    $created_by = $_SESSION['user_name'] ?? 'Unknown';

    if(empty($full_name) || empty($email) || empty($phone) || empty($job_id) || empty($education) || empty($experience) || empty($apply_date) || empty($interview_date)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "INSERT INTO candidates (full_name, email, phone, job_id, education, experience, applied_date, interview_date, resume, created_by) 
            VALUES ('$full_name', '$email', '$phone', '$job_id', '$education', '$experience', '$apply_date', '$interview_date', '$resume', '$created_by')";

    if(mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

if(isset($_GET['action']) && $_GET['action'] == 'edit') {
    $id = intval($_POST['edit_candidate_id']);
    $full_name = $_POST['edit_full_name'];
    $email = $_POST['edit_email'];
    $phone = $_POST['edit_phone'];
    $job_id = $_POST['edit_Job'];
    $education = $_POST['edit_education'];
    $experience = $_POST['edit_experience'];
    $apply_date = $_POST['edit_apply_date'];
    $interview_date = $_POST['edit_interview_date'];

    $getOldResume = mysqli_query($conn, "SELECT resume FROM candidates WHERE candidate_id=$id");
    $oldRow = mysqli_fetch_assoc($getOldResume);
    if($oldRow){
        $oldResume = $oldRow['resume'];
    } else {
        echo "Candidate not found";
        exit();
    }

    if (!empty($_FILES['edit_Resume']['name'])) {
        $resume = $_FILES['edit_Resume']['name'];
        $tmp = $_FILES['edit_Resume']['tmp_name'];
        move_uploaded_file($tmp, "../resume/" . $resume);
    } else {
        $resume = $oldResume;
    }

    $updated_at = date('Y-m-d H:i:s');
    $updated_by = $_SESSION['user_name'];

    if(empty($id)) { echo "Invalid candidate ID."; exit(); }

    if(empty($full_name) || empty($email) || empty($phone) || empty($job_id) || empty($education) || empty($experience) || empty($apply_date) || empty($interview_date)) {
        echo "All fields are required.";
        exit();
    }

    $sql = "UPDATE candidates 
        SET full_name='$full_name',
            email='$email',
            phone='$phone',
            job_id='$job_id',
            education='$education',
            experience='$experience',
            applied_date='$apply_date',
            interview_date='$interview_date',
            resume='$resume',
            updated_at='$updated_at',
            updated_by='$updated_by' 
        WHERE candidate_id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "Candidate updated successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'update_status') {
    header('Content-Type: application/json');

    $candidate_id = intval($_POST['candidate_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if (!$candidate_id || !in_array($status, ['Applied', 'Shortlisted', 'Rejected', 'Hired', 'Joined'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    $updated_at = date('Y-m-d H:i:s');
    $updated_by = $_SESSION['user_name'] ?? 'Unknown';

    $sql = "UPDATE candidates 
            SET status='$status', updated_at='$updated_at', updated_by='$updated_by' 
            WHERE candidate_id='$candidate_id' AND isdeleted='0'";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    
    $candidate_id = $_GET['id'] ?? '';

    if(!empty($candidate_id)) {
        $sql = "UPDATE candidates SET isdeleted = '1' WHERE candidate_id = '$candidate_id'";
        
        if(mysqli_query($conn, $sql)) {
            $interviewsql = "UPDATE interviews SET isdeleted = '1' WHERE candidate_id = '$candidate_id'";
            mysqli_query($conn, $interviewsql);
            header("Location: candidates.php");
            exit();
        } else {
            echo "Error deleting candidate: " . mysqli_error($conn);
        }
    }
}

?>