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

// Only the login form is below, no includes that redirect back to login
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In</title>
<style>
/* --- YOUR EXISTING CSS --- */
body {
    background: url('img/tree.jpg') no-repeat center center/cover;
    font-family: "Poppins", sans-serif;
}
.custom-header { background-color: #3f4a36; position: fixed; top: 0; width: 100%; z-index: 1020; box-shadow: 0 2px 4px rgba(0,0,0,0.1); height:60px; }
.header-logo { height:30px; margin-right:20px; border-radius:45px; }
.btn-back { color:white; background:transparent; border:1px solid white; border-radius:8px; padding:5px 12px; text-decoration:none; font-weight:500; }
.btn-back:hover { background:white; color:#3f4a36; transition:0.3s ease; }
.login-card { width:450px; margin:80px auto; background:#e2eed9; padding:40px 55px; border-radius:25px; box-shadow:0px 4px 25px rgba(0,0,0,0.25); text-align:center; }
.login-card h2 { font-size:28px; font-weight:700; margin-bottom:5px; color:#3f4a36; }
.login-card p { font-size:14px; color:#4f5b44; margin-bottom:25px; }
.form-control { border-radius:25px; padding:12px 18px; }
button.btn-login { background-color:#162607!important; color:#fff!important; padding:12px; width:100%; border-radius:25px; font-size:16px; margin-top:10px; font-weight:600; border:none; transition:0.3s ease; }
button.btn-login:hover { background-color:#1f3a08!important; }
.google-btn { background:#fff; border:1px solid #c1c1c1; padding:12px; border-radius:25px; width:100%; display:flex; justify-content:center; gap:10px; font-weight:500; margin-top:15px; }
.or-divider { display:flex; align-items:center; margin:18px 0; }
.or-divider hr { flex:1; border:none; border-top:1px solid #8fa382; }
.or-divider span { margin:0 12px; color:#3f4a36; font-weight:500; }
.bottom-text { margin-top:18px; font-size:14px; }
.bottom-text a { color:#3f4a36; font-weight:600; text-decoration:none; }
.bottom-text a:hover { text-decoration:underline; }
.error-msg { color:red; font-weight:600; margin-bottom:15px; }
</style>
</head>
<body>
<header class="custom-header">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <a href="home.php" class="d-flex align-items-center text-decoration-none">
            <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
            <span style="color:white; font-weight:600;">ECOnnect</span>
        </a>
        <a href="index.php" class="btn-back">Home</a>
    </div>
</header>

<div class="login-card">
    <h2>Welcome Back!</h2>
    <p>Log in to your account</p>

    <?php
    if (isset($_SESSION['login_error'])) {
        echo '<div class="error-msg">'.$_SESSION['login_error'].'</div>';
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
        <div class="text-end mb-3">
            <a href="forgot_password.php" style="color:#3f4a36; font-size:14px;">Forgot Password?</a>
        </div>
        <button type="submit" class="btn-login">Log In</button>
    </form>

    <div class="or-divider">
        <hr><span>OR</span><hr>
    </div>
    <button class="google-btn">
        <img src="img/google.png" width="20"> Sign in with Google
    </button>

    <div class="bottom-text">
        Don’t have an account? <a href="register.php">Register</a>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
