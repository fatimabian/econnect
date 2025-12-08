<?php
session_start();
include "db_connect.php";

$errors = ['phone'=>'','otp'=>''];
$showOtpModal = false;

// Ensure pending activation exists
if(!isset($_SESSION['pending_activation_user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['pending_activation_user'];

// OTP sending function
function sendOtp($phone, &$otp) {
    $otp = rand(100000,999999);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_expiry'] = time() + 300; // 5 mins

    $api_token = "dda33f23a9d96e5f433c56d8907c072b40830ef7";
    $sms_data = [
        "api_token" => $api_token,
        "phone_number" => $phone,
        "message" => "Your OTP is: $otp (valid for 5 minutes)"
    ];

    $options = ['http'=>[
        'header'=>"Content-Type: application/json\r\n",
        'method'=>'POST',
        'content'=>json_encode($sms_data)
    ]];
    $context = stream_context_create($options);
    return @file_get_contents("https://sms.iprogtech.com/api/v1/sms_messages", false, $context);
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 1: Send OTP
    if(isset($_POST['sendOtp'])) {
        $phone = preg_replace('/^0/', '+63', trim($_POST['phone']));
        if(empty($phone)) {
            $errors['phone'] = "Enter your phone number.";
        } else {
            if(sendOtp($phone, $otp)) {
                $_SESSION['pending_activation_phone'] = $phone;
                $showOtpModal = true;
            } else {
                $errors['phone'] = "Failed to send OTP. Try again.";
            }
        }
    }

    // STEP 2: Verify OTP
    if(isset($_POST['verifyOtp'])) {
        $entered_otp = trim($_POST['otp']);
        if(!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
            $errors['otp'] = "OTP expired. Request again.";
            $showOtpModal = true;
        } elseif($entered_otp != $_SESSION['otp']) {
            $errors['otp'] = "Invalid OTP.";
            $showOtpModal = true;
        } else {
            // Activate account based on user type
            $phone = $_SESSION['pending_activation_phone'];
            $type = $user['type'];
            $id = $user['id'];

            if($type === 'barangay_admin') {
                $stmt = $conn->prepare("UPDATE barangay_admins SET status='Active', phone_number=? WHERE id=?");
            } elseif($type === 'crew') {
                $stmt = $conn->prepare("UPDATE collection_crew SET status='Active', phone=? WHERE id=?");
            }
            $stmt->bind_param("si",$phone,$id);
            $stmt->execute();
            $stmt->close();

            // Cleanup
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['pending_activation_user'], $_SESSION['pending_activation_phone']);

            $_SESSION['login_success'] = "Account activated! You can now login.";
            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Phone Verification - ECOnnect</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="reg.css">
</head>
<body>

<header class="custom-header">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <a href="home.php" class="d-flex align-items-center text-decoration-none">
            <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
            <span style="color:white;font-weight:600;">ECOnnect</span>
        </a>
        <a href="login.php" class="btn-back">Login</a>
    </div>
</header>

<div class="flex justify-center items-start min-h-screen pt-24">
    <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md border border-green-200">
        <h2 class="text-2xl font-semibold text-green-700 mb-2 text-center">Activate Account</h2>
        <p class="text-gray-600 mb-4 text-center">Enter your phone number to receive an OTP.</p>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Phone Number</label>
                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                <p class="text-red-600 text-sm mt-1"><?= $errors['phone'] ?></p>
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

<?php include("footer.php"); ?>
</body>
</html>
