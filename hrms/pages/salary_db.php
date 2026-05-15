<?php 
session_start();
include '../includes/db.php';

if(!isset($_SESSION['user_id'])){
    header('Location: ../index.html');
    exit();
}

if (isset($_POST['action']) && $_POST['action'] == 'add') {
    $user_id = intval($_POST['user_id']);
    $basic = floatval($_POST['basic']);
    $hra = floatval($_POST['hra']);
    $special_allowance = floatval($_POST['special_allowance']);
    $ta = floatval($_POST['ta']);
    $other_allowances = floatval($_POST['other_allowance']);
    $effective_from = $_POST['effective_from'] ?? '';
    $created_by = $_SESSION['user_name'] ?? 'System';

    if (empty($user_id)) {
        echo "Please select an employee.";
        exit();
    }

    $eff_sql = !empty($effective_from) ? "'$effective_from'" : "NULL";

    $sql = "INSERT INTO salary_master (user_id, basic, hra, special_allowance, ta, other_allowance, effective_from, created_by)
            VALUES ($user_id, $basic, $hra, $special_allowance, $ta, $other_allowances, $eff_sql, '$created_by')";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

if (isset($_POST['action']) && $_POST['action'] == 'edit') {
    $salary_id = intval($_POST['salary_id']);
    $basic = floatval($_POST['basic']);
    $hra = floatval($_POST['hra']);
    $special_allowance = floatval($_POST['special_allowance']);
    $ta = floatval($_POST['ta']);
    $other_allowances = floatval($_POST['other_allowance']);
    $effective_from = $_POST['effective_from'] ?? '';
    $updated_by = $_SESSION['user_name'] ?? 'System';
    $updated_at = date('d-m-Y H:i:s');

    $eff_sql = !empty($effective_from) ? "'$effective_from'" : "NULL";

    $sql = "UPDATE salary_master SET
            basic=$basic,
            hra=$hra,
            special_allowance=$special_allowance,
            ta=$ta,
            other_allowance=$other_allowances,
            effective_from=$eff_sql,
            status='Active',
            updated_by='$updated_by',
            updated_at='$updated_at'
        WHERE salary_id=$salary_id";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}

?>