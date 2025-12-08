<?php
session_start();

// If already logged in, redirect to the right dashboard
if (isset($_SESSION['super_admin_id'])) {
    header("Location: superadmin/superAdmin.php");
    exit;
}
if (isset($_SESSION['barangay_admin_id'])) {
    header("Location: admin/adminDash.php");
    exit;
}
if (isset($_SESSION['crew_id'])) {
    header("Location: crew/crewDash.php");
    exit;
}
if (isset($_SESSION['user_id'])) {
    header("Location: users/usersDash.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - ECOnnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* General Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: url('img/tree.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .custom-header {
            background-color: #3f4a36;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1020;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 60px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            padding: 0 20px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .header-logo {
            height: 30px;
            margin-right: 15px;
            border-radius: 45px;
        }
        
        .logo-text {
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .btn-back {
            color: white;
            background: transparent;
            border: 1px solid white;
            border-radius: 8px;
            padding: 5px 15px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .btn-back:hover {
            background: white;
            color: #3f4a36;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 100px 20px 40px;
            width: 100%;
        }
        
        /* Login Card */
        .login-card {
            width: 100%;
            max-width: 450px;
            background: #e2eed9;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0px 4px 25px rgba(0,0,0,0.25);
            text-align: center;
        }
        
        .login-card h2 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            color: #3f4a36;
        }
        
        .login-card p {
            font-size: 14px;
            color: #4f5b44;
            margin-bottom: 25px;
        }
        
        /* Form Styles */
        .form-control {
            border-radius: 25px;
            padding: 12px 18px;
            border: 1px solid #8fa382;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #3f4a36;
            box-shadow: 0 0 0 0.25rem rgba(63, 74, 54, 0.25);
        }
        
        .btn-login {
            background-color: #162607 !important;
            color: #fff !important;
            padding: 12px;
            width: 100%;
            border-radius: 25px;
            font-size: 16px;
            margin-top: 10px;
            font-weight: 600;
            border: none;
            transition: background-color 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: #1f3a08 !important;
        }
        
        /* Divider */
        .or-divider {
            display: flex;
            align-items: center;
            margin: 18px 0;
        }
        
        .or-divider hr {
            flex: 1;
            border: none;
            border-top: 1px solid #8fa382;
        }
        
        .or-divider span {
            margin: 0 12px;
            color: #3f4a36;
            font-weight: 500;
        }
        
        /* Google Button */
        .google-btn {
            background: #fff;
            border: 1px solid #c1c1c1;
            padding: 12px;
            border-radius: 25px;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            margin-top: 15px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        
        .google-btn:hover {
            background: #f8f9fa;
            border-color: #3f4a36;
        }
        
        /* Bottom Text */
        .bottom-text {
            margin-top: 18px;
            font-size: 14px;
            color: #4f5b44;
        }
        
        .bottom-text a {
            color: #3f4a36;
            font-weight: 600;
            text-decoration: none;
        }
        
        .bottom-text a:hover {
            text-decoration: underline;
        }
        
        /* Error Message */
        .error-msg {
            color: #dc3545;
            font-weight: 600;
            margin-bottom: 15px;
            padding: 10px;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 8px;
            font-size: 14px;
        }
        
        /* Forgot Password Link */
        .forgot-password {
            color: #3f4a36;
            font-size: 14px;
            text-decoration: none;
            float: right;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        /* Responsive Styles */
        @media (max-width: 768px) {
            .login-card {
                padding: 30px 25px;
                margin: 20px;
            }
            
            .login-card h2 {
                font-size: 24px;
            }
            
            .header-content {
                padding: 0 15px;
            }
            
            .logo-text {
                font-size: 1.1rem;
            }
            
            .header-logo {
                height: 25px;
                margin-right: 10px;
            }
            
            .btn-back {
                padding: 4px 12px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 90px 15px 30px;
            }
            
            .login-card {
                padding: 25px 20px;
            }
            
            .login-card h2 {
                font-size: 22px;
            }
            
            .logo-text {
                font-size: 1rem;
            }
            
            .btn-back {
                padding: 3px 10px;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 400px) {
            .header-content {
                padding: 0 10px;
            }
            
            .logo-text {
                display: none;
            }
            
            .login-card h2 {
                font-size: 20px;
            }
            
            .form-control {
                padding: 10px 15px;
            }
            
            .btn-login {
                padding: 10px;
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="custom-header">
        <div class="header-content">
            <a href="home.php" class="logo-container">
                <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
                <span class="logo-text">ECOnnect</span>
            </a>
            <a href="index.php" class="btn-back">Home</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="login-card">
            <h2>Welcome Back!</h2>
            <p>Log in to your account</p>

            <div class="or-divider">
                <hr><span>Log In</span><hr>
            </div>

            <?php
            if (isset($_SESSION['login_error'])) {
                echo '<div class="error-msg">' . $_SESSION['login_error'] . '</div>';
                unset($_SESSION['login_error']);
            }
            ?>

            <form action="login_process.php" method="POST">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="mb-1">
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="mb-3 text-end">
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>
                <button type="submit" class="btn btn-login">Log In</button>
            </form>

            <div class="or-divider">
                <hr><span></span><hr>
            </div>
            
            <!-- Google Sign In Button (commented out as per original) -->
            <!--
            <a href="#" class="google-btn">
                <img src="img/google.png" width="20" alt="Google Logo">
                Sign in with Google
            </a>
            -->

            <div class="bottom-text">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS Bundle (optional, if you need Bootstrap JS components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>