<?php
session_start();
include "db_connect.php";

$errors = ['contact'=>'','otp'=>'','password'=>'','confirm_password'=>''];
$showOtpModal = false;
$showResetModal = false;

// --- STEP 1: Send OTP ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['sendOtp'])){
    $contact = trim($_POST['contact']); // user input
    $alt_contact = preg_replace('/^0/', '+63', $contact); // alternate format
    
    if(empty($contact)){
        $errors['contact'] = "Please enter your mobile number.";
    } else {
        // Check if contact exists in either format
        $stmt = $conn->prepare("SELECT id FROM users WHERE contact=? OR contact=? LIMIT 1");
        $stmt->bind_param("ss", $contact, $alt_contact);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows==0){
            $errors['contact']="Mobile number not registered.";
        } else {
            // Use the format stored in database
            $user = $result->fetch_assoc();
            $db_contact = $contact; // default
            $row_contact_stmt = $conn->prepare("SELECT contact FROM users WHERE id=?");
            $row_contact_stmt->bind_param("i", $user['id']);
            $row_contact_stmt->execute();
            $row_result = $row_contact_stmt->get_result();
            if($row_result->num_rows > 0){
                $db_contact = $row_result->fetch_assoc()['contact'];
            }
            $row_contact_stmt->close();

            // Generate OTP
            $otp = rand(100000,999999);
            $_SESSION['forgot_otp'] = $otp;
            $_SESSION['forgot_otp_expiry'] = time()+300; // 5 mins
            $_SESSION['forgot_contact'] = $db_contact;

            // IPROG SMS API
            $api_token = "dda33f23a9d96e5f433c56d8907c072b40830ef7"; // replace with real token
            $sms_data = [
                "api_token"=>$api_token,
                "phone_number"=>$db_contact,
                "message"=>"Your ECOnnect OTP is: $otp (valid for 5 minutes)"
            ];

            $options = [
                'http'=>[
                    'header'=>"Content-Type: application/json\r\n",
                    'method'=>'POST',
                    'content'=>json_encode($sms_data)
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents("https://sms.iprogtech.com/api/v1/sms_messages", false, $context);

            if($response===FALSE){
                $errors['contact']="Failed to send OTP. Try again.";
            } else {
                $showOtpModal = true;
            }
        }
        $stmt->close();
    }
}

// --- STEP 2: Verify OTP ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['verifyOtp'])){
    $entered_otp = trim($_POST['otp']);
    if(!isset($_SESSION['forgot_otp']) || time() > $_SESSION['forgot_otp_expiry']){
        $errors['otp']="OTP expired. Please try again.";
        $showOtpModal = true;
    } elseif($entered_otp != $_SESSION['forgot_otp']){
        $errors['otp']="Invalid OTP.";
        $showOtpModal = true;
    } else {
        $showResetModal = true;
    }
}

// --- STEP 3: Reset Password ---
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['resetPassword'])){
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if(empty($password)) $errors['password']="Please enter new password.";
    if(empty($confirm_password)) $errors['confirm_password']="Please confirm new password.";
    if($password && $confirm_password && $password !== $confirm_password) $errors['confirm_password']="Passwords do not match.";

    if(!array_filter($errors)){
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE contact=?");
        $stmt->bind_param("ss", $hashedPassword, $_SESSION['forgot_contact']);
        if($stmt->execute()){
            unset($_SESSION['forgot_otp'], $_SESSION['forgot_otp_expiry'], $_SESSION['forgot_contact']);
            header("Location: login.php?msg=password_reset");
            exit;
        } else {
            $errors['password']="Database error: ".$stmt->error;
        }
        $stmt->close();
    } else {
        $showResetModal = true;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - ECOnnect</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="reg.css">
</head>
<body>

<header class="custom-header">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <a href="index.php" class="d-flex align-items-center text-decoration-none">
            <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
            <span style="color:white;font-weight:600;">ECOnnect</span>
        </a>
        <a href="login.php" class="btn-back">Login</a>
    </div>
</header>

<div class="flex justify-center items-start min-h-screen pt-24">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md border border-green-200">
        <h2 class="text-2xl font-semibold text-green-700 mb-2 text-center">Forgot Password</h2>
        <p class="text-gray-600 mb-4 text-center">Enter your registered mobile number to reset your password</p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Mobile Number</label>
                <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                <p class="text-red-600 text-sm mt-1"><?= $errors['contact'] ?></p>
            </div>
            <button type="submit" name="sendOtp" class="w-full bg-green-600 text-white p-2 rounded-lg font-semibold hover:bg-green-700 transition duration-200">Send OTP</button>
        </form>
    </div>
</div>


<!-- OTP Modal -->
<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 <?= $showOtpModal ? 'flex' : 'hidden' ?> z-50 items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md border border-green-200 shadow-2xl">
        <h3 class="text-xl font-semibold text-green-700 mb-4 text-center">OTP Verification</h3>
        <p class="text-gray-600 mb-4 text-center">Enter the OTP sent to your mobile number:</p>
        <form method="POST" class="space-y-4">
            <input type="text" name="otp" placeholder="Enter OTP" required class="w-full p-2 border border-green-300 rounded-lg text-center">
            <p class="text-red-600 text-sm text-center"><?= $errors['otp'] ?></p>
            <button type="submit" name="verifyOtp" class="w-full bg-green-600 text-white p-2 rounded-lg font-semibold hover:bg-green-700 transition duration-200">Verify OTP</button>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetModal" class="fixed inset-0 bg-black bg-opacity-50 <?= $showResetModal ? 'flex' : 'hidden' ?> z-50 items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md border border-green-200 shadow-2xl">
        <h3 class="text-xl font-semibold text-green-700 mb-4 text-center">Reset Password</h3>
        <form method="POST" class="space-y-4">
            <input type="password" name="password" placeholder="New Password" required class="w-full p-2 border border-green-300 rounded-lg">
            <p class="text-red-600 text-sm"><?= $errors['password'] ?></p>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required class="w-full p-2 border border-green-300 rounded-lg">
            <p class="text-red-600 text-sm"><?= $errors['confirm_password'] ?></p>
            <button type="submit" name="resetPassword" class="w-full bg-green-600 text-white p-2 rounded-lg font-semibold hover:bg-green-700 transition duration-200">Reset Password</button>
        </form>
    </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>
