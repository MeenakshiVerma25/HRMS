<?php
session_start();
include 'includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    // $sql = "SELECT * FROM users WHERE user_id='$user_id' AND password='$password'";
    // $result = mysqli_query($conn, $sql);
    // $row = mysqli_fetch_assoc($result);

    $stmt = $conn->prepare("Select * FROM users
        WHERE user_id = ? AND password = ?");

    $stmt->bind_param("ss", $user_id, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if($result->num_rows > 0) {
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['user_role'] = $row['user_role'];
        $_SESSION['user_name'] = $row['user_name'];

        header("Location: pages/dashboard.php");
        exit();
    } else {
        $error = "Invalid User ID or Password";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <!-- Google Fonts connection -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans&display=swap">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/login.css">

</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row align-items-center login-box">
                <div class="col-md-6 text-center">
                    <img src="images/login_img.png" alt="Login Image" class="img-fluid login-img">
                </div>
                <div class="col-md-6 login-form">
                    <h2>Welcome Back!</h2>
                    <p class="subtitle">Sign in to continue to your account</p>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="user_id">User ID</label>
                            <input type="text" class="form-control" id="user_id" name="user_id" placeholder="Enter your User ID" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="login-button">Sign In</button>
                        <div class="forgot-link">
                            <a href="#">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
</body>
</html>