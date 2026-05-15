<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function syncCandidateStatusFromOffer($conn, $candidate_id, $status) {
    $candidate_id = intval($candidate_id);

    if ($status === 'Accepted') {
        mysqli_query($conn, "UPDATE candidates SET status='Hired' WHERE candidate_id='$candidate_id' AND isdeleted='0'");
    } else {
        mysqli_query($conn, "UPDATE candidates SET status='Shortlisted' WHERE candidate_id='$candidate_id' AND isdeleted='0' AND status!='Joined'");
    }
}

if ($action == 'add') {

    $candidate_id    = trim($_POST['candidate'] ?? '');
    $job_id          = trim($_POST['job'] ?? '');
    $designation_id  = trim($_POST['designation_id'] ?? '');
    $dept_id         = trim($_POST['dept_id'] ?? '');
    $location_id     = trim($_POST['location_id'] ?? '');
    $ctc             = trim($_POST['ctc'] ?? '');
    $doj             = trim($_POST['doj'] ?? '');
    $offer_date      = trim($_POST['offer_date'] ?? '');
    $status          = 'Pending';

    $created_by = $_SESSION['user_name'] ?? 'Unknown';

    if ($candidate_id === '' || $job_id === '' || $designation_id === '' || $dept_id === '' || $location_id === '' || $ctc === '' || $doj === '' || $offer_date === '') {
        echo "All fields are required.";
        exit();
    }

    if ($offer_date > $doj) {
        echo "Offer date cannot be after DOJ.";
        exit();
    }

    $checkOffer = mysqli_query($conn, "SELECT offer_id FROM offers WHERE candidate_id='$candidate_id' AND isdeleted='0' LIMIT 1");
    if ($checkOffer && mysqli_num_rows($checkOffer) > 0) {
        echo "Offer already exists for this candidate.";
        exit();
    }

    $sql = "INSERT INTO offers 
            (candidate_id, job_id, designation_id, dept_id, location_id, ctc, doj, offer_date, status, created_by)
            VALUES 
            ('$candidate_id', '$job_id', '$designation_id', '$dept_id', '$location_id', '$ctc', '$doj', '$offer_date', '$status', '$created_by')";

    if (mysqli_query($conn, $sql)) {
        syncCandidateStatusFromOffer($conn, $candidate_id, $status);
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

if ($action == 'edit') {

    $offer_id         = intval($_POST['edit_offer_id'] ?? 0);
    $designation_id   = trim($_POST['edit_designation_id'] ?? '');
    $dept_id          = trim($_POST['edit_dept_id'] ?? '');
    $location_id      = trim($_POST['edit_location_id'] ?? '');
    $ctc              = trim($_POST['edit_ctc'] ?? '');
    $doj              = trim($_POST['edit_doj'] ?? '');
    $offer_date       = trim($_POST['edit_offer_date'] ?? '');

    $updated_by = $_SESSION['user_name'] ?? 'Unknown';
    $updated_at = date('Y-m-d H:i:s');

    if ($offer_id <= 0 || $designation_id === '' || $dept_id === '' || $location_id === '' || $ctc === '' || $doj === '' || $offer_date === '') {
        echo "All fields are required.";
        exit();
    }

    if ($offer_date > $doj) {
        echo "Offer date cannot be after DOJ.";
        exit();
    }

    $sql = "UPDATE offers SET
                designation_id = '$designation_id',
                dept_id = '$dept_id',
                location_id = '$location_id',
                ctc = '$ctc',
                doj = '$doj',
                offer_date = '$offer_date',
                updated_at = '$updated_at',
                updated_by = '$updated_by'
            WHERE offer_id = '$offer_id'";

    if (mysqli_query($conn, $sql)) {
        echo "Offer updated successfully.";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}

if ($action == 'update_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $offer_id = intval($_POST['offer_id'] ?? 0);
    $status   = trim($_POST['status'] ?? '');

    if (!$offer_id || !in_array($status, ['Pending', 'Accepted'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit();
    }

    $offerQry = mysqli_query($conn, "SELECT candidate_id FROM offers WHERE offer_id='$offer_id' AND isdeleted='0' LIMIT 1");
    $offerRow = mysqli_fetch_assoc($offerQry);

    if (!$offerRow) {
        echo json_encode(['success' => false, 'message' => 'Offer not found']);
        exit();
    }

    $candidate_id = intval($offerRow['candidate_id']);

    $upd = mysqli_query($conn, "UPDATE offers SET status='$status' WHERE offer_id='$offer_id'");

    if ($upd) {
        syncCandidateStatusFromOffer($conn, $candidate_id, $status);
        echo json_encode(['success' => true, 'message' => 'Offer status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    exit();
}

if ($action == 'delete') {

    $offer_id = intval($_GET['id'] ?? 0);

    if ($offer_id <= 0) {
        echo "Invalid offer ID.";
        exit();
    }

    $candQry = mysqli_query($conn, "SELECT candidate_id FROM offers WHERE offer_id='$offer_id' LIMIT 1");
    $candRow = mysqli_fetch_assoc($candQry);
    $candidate_id = intval($candRow['candidate_id'] ?? 0);

    $sql = "UPDATE offers SET isdeleted = '1' WHERE offer_id = '$offer_id'";

    if (mysqli_query($conn, $sql)) {
        if ($candidate_id > 0) {
            mysqli_query($conn, "UPDATE candidates SET status='Shortlisted' WHERE candidate_id='$candidate_id' AND isdeleted='0' AND status!='Joined'");
        }
        header("Location: offers.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}
?>