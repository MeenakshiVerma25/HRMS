<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'add') {
    $candidate = trim($_POST['candidate'] ?? '');
    $job       = trim($_POST['job'] ?? '');
    $round     = trim($_POST['round'] ?? '');
    $date      = trim($_POST['date'] ?? '');
    $panel     = trim($_POST['panel'] ?? '');
    $score     = trim($_POST['score'] ?? '');

    $created_by = $_SESSION['user_name'] ?? 'Unknown';

    if ($candidate === '' || $job === '' || $round === '' || $date === '' || $panel === '' || $score === '') {
        echo "All fields are required.";
        exit();
    }

    $sql = "INSERT INTO interviews 
            (candidate_id, job_id, round_name, interview_date, panel_name, score, created_by) 
            VALUES 
            ('$candidate', '$job', '$round', '$date', '$panel', '$score', '$created_by')";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

if ($action == 'edit') {
    $id        = intval($_POST['edit_interview_id'] ?? 0);
    $candidate = trim($_POST['edit_candidate'] ?? '');
    $job       = trim($_POST['edit_job'] ?? '');
    $round     = trim($_POST['edit_round'] ?? '');
    $date      = trim($_POST['edit_date'] ?? '');
    $panel     = trim($_POST['edit_panel'] ?? '');
    $score     = trim($_POST['edit_score'] ?? '');

    $updated_by = $_SESSION['user_name'] ?? 'Unknown';
    $updated_at = date('Y-m-d H:i:s');

    if ($id <= 0 || $candidate === '' || $job === '' || $round === '' || $date === '' || $panel === '' || $score === '') {
        echo "All fields are required.";
        exit();
    }

    $sql = "UPDATE interviews 
            SET candidate_id='$candidate',
                job_id='$job',
                round_name='$round',
                interview_date='$date',
                panel_name='$panel',
                score='$score',
                updated_at='$updated_at',
                updated_by='$updated_by'
            WHERE interview_id='$id'";

    if (mysqli_query($conn, $sql)) {
        echo "Interview updated successfully";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

if ($action === 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $iid    = intval($_POST['interview_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if (!$iid || !in_array($status, ['Selected', 'Rejected'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    $check = mysqli_query($conn, "SELECT interview_id FROM interviews WHERE interview_id = '$iid' AND isdeleted = '0' LIMIT 1");

    if (!$check || mysqli_num_rows($check) == 0) {
        echo json_encode(['success' => false, 'message' => 'Interview record not found']);
        exit();
    }

    $upd = mysqli_query($conn, "UPDATE interviews SET status = '$status' WHERE interview_id = '$iid'");

    if ($upd) {
        echo json_encode(['success' => true, 'message' => 'Interview status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

if ($action == 'delete') {
    $interview_id = intval($_GET['id'] ?? 0);

    if ($interview_id <= 0) {
        echo "Invalid interview ID.";
        exit();
    }

    $sql = "UPDATE interviews SET isdeleted='1' WHERE interview_id='$interview_id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: interviews.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}
?>