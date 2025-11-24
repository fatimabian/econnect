<?php
session_start();
require __DIR__ . '/vendor/autoload.php'; 
include "db_connect.php";

use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

$errors = [
    'fname' => '',
    'mname' => '',
    'lname' => '',
    'suffix' => '',
    'email' => '',
    'contact' => '',
    'region' => '',
    'province' => '',
    'city' => '',
    'barangay' => '',
    'street' => '',
    'zip' => '',
    'username' => '',
    'password' => '',
    'confirm_password' => '',
    'otp' => ''
];

$showOtpModal = false;

// --- STEP 1: User clicks Register (send OTP) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendOtp'])) {
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $contact = preg_replace('/^0/', '+63', trim($_POST['contact']));
    $region = $_POST['region'];
    $province = $_POST['province'];
    $city = $_POST['city'];
    $barangay = $_POST['barangay'];
    $street = $_POST['street'];
    $zip = $_POST['zip'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ✅ Password Match
    if ($password !== $confirm_password) $errors['confirm_password'] = "Passwords do not match.";

    // ✅ Duplicate Checks
    $checks = [
        ['email', $email, 'Email already registered.'],
        ['contact', $contact, 'Contact number already registered.'],
        ['username', $username, 'Username already taken.']
    ];

    foreach ($checks as [$column, $value, $message]) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE $column = ?");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[$column] = $message;
        $stmt->close();
    }

    // ✅ If no errors, send OTP via Vonage
    if (!array_filter($errors)) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300; // 5 minutes
        $_SESSION['pending_user'] = compact(
            'fname', 'mname', 'lname', 'suffix',
            'email', 'contact', 'region', 'province', 'city', 'barangay', 'street', 'zip',
            'username', 'password'
        );

        // Vonage API credentials - Alexers
        $apiKey = "7980a169";
        $apiSecret = "ISnASXhxL4ktm50v";
        $basic = new Basic($apiKey, $apiSecret);
        $client = new Client($basic);

        // Vonage API credentials - Econnect
        // $apiKey = "7efb4312";
        // $apiSecret = "JE6YeuECR1rKmcjL";
       //  $basic = new Basic($apiKey, $apiSecret);
        //$client = new Client($basic);

        try {
            $response = $client->sms()->send(
                new SMS($contact, "ECOnnect", "Your ECOnnect OTP is: $otp. It’s valid for 5 minutes.")
            );
            $message = $response->current();
            if ($message->getStatus() == 0) {
                $showOtpModal = true;
            } else {
                $errors['otp'] = "Failed to send OTP. Try again.";
            }
        } catch (Exception $e) {
            $errors['otp'] = "Error sending OTP: " . $e->getMessage();
            file_put_contents('otp_errors.txt', $e->getMessage()."\n", FILE_APPEND);
        }
    }
}

// --- STEP 2: Verify OTP ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finalRegister'])) {
    $entered_otp = trim($_POST['otp']);

    if (!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
        $errors['otp'] = "OTP expired. Please try again.";
        $showOtpModal = true;
    } elseif ($entered_otp != $_SESSION['otp']) {
        $errors['otp'] = "Invalid OTP.";
        $showOtpModal = true;
    } else {
        $user = $_SESSION['pending_user'];
        $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users 
            (fname, mname, lname, suffix, contact, email, region, province, city, barangay, street, zip, username, password) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssssssssss",
            $user['fname'], $user['mname'], $user['lname'], $user['suffix'],
            $user['contact'], $user['email'], $user['region'], $user['province'],
            $user['city'], $user['barangay'], $user['street'], $user['zip'],
            $user['username'], $hashedPassword
        );

        if ($stmt->execute()) {
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['pending_user']);
            header("Location: 123landing.php");
            exit;
        } else {
            $errors['fname'] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="reg.css">

</head>
<body>

    <header class="custom-header">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <a href="home.php" class="d-flex align-items-center text-decoration-none">
            <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
            <span style="color: white; font-weight: 600;">ECOnnect</span>
        </a>

        <a href="index.php" class="btn-back">Home</a>
    </div>
</header>

<div class="register-container">

    <h2>Join Us!</h2>
    <p class="subtext">Create your account to get started</p>

    <div class="mb-4">
        <button class="google-btn w-100">
            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="22">
            Sign up with Google
        </button>
    </div>

    <div class="divider-or">OR</div>

    <form action="" method="POST"> <!-- submit to same file -->

        <h5 class="mb-3">Personal Information</h5>

        <div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Region</label>
        <select id="regionSelect" name="region" class="form-control" required>
            <option value="">Select Region</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Province</label>
        <select id="provinceSelect" name="province" class="form-control" required>
            <option value="">Select Province</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">City / Municipality</label>
        <select id="municipalitySelect" name="city" class="form-control" required>
            <option value="">Select City/Municipality</option>
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Barangay</label>
        <select id="barangaySelect" name="barangay" class="form-control" required>
            <option value="">Select Barangay</option>
        </select>
    </div>
</div>


<div class="row g-3 mt-1">
    <div class="col-md-4">
        <label class="form-label">Street / House No. / Subdivision</label>
        <input type="text" class="form-control" name="street">
    </div>

    <div class="col-md-4">
        <label class="form-label">ZIP / Postal Code</label>
        <input type="text" class="form-control" name="zip">
    </div>
</div>


        <hr class="mt-4">

        <h5 class="mb-3">Address Information</h5>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Region</label>
                <input type="text" class="form-control" name="region" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Province</label>
                <input type="text" class="form-control" name="province" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">City / Municipality</label>
                <input type="text" class="form-control" name="city" required>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-4">
                <label class="form-label">Barangay</label>
                <select class="form-control" name="barangay" required>
                    <option value="" selected>Select Barangay</option> <!-- placeholder -->
                    <option value="Marawoy">Marawoy</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Street / House No. / Subdivision</label>
                <input type="text" class="form-control" name="street">
            </div>

            <div class="col-md-4">
                <label class="form-label">ZIP / Postal Code</label>
                <input type="text" class="form-control" name="zip">
            </div>
        </div>

        <hr class="mt-4">

        <h5 class="mb-3">Account Security</h5>

         <div class="mt-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" required>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Create Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Confirm Password</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
        </div>

        <div class="mt-4 mb-3">
            <input type="checkbox" required>
            <label>I agree to the Terms of Service and Privacy Policy</label>
        </div>

         <button type="submit" name="sendOtp" class="create-btn">Create Account</button>

        <p class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </p>

    </form>
</div>

<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 <?= $showOtpModal ? 'flex' : 'hidden' ?> z-50 items-center justify-center">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md border border-green-200 shadow-2xl">
        <h3 class="text-xl font-semibold text-green-700 mb-4 text-center">OTP Verification</h3>
        <p class="text-gray-600 mb-4 text-center">Enter the OTP sent to your contact number:</p>
        <form method="POST" id="otpForm" class="space-y-4">
            <input type="text" name="otp" placeholder="Enter OTP" required class="w-full p-2 border border-green-300 rounded-lg text-center">
            <p class="text-red-600 text-sm text-center"><?= $errors['otp'] ?></p>
            <button type="submit" name="finalRegister" class="w-full bg-green-600 text-white p-2 rounded-lg font-semibold hover:bg-green-700 transition duration-200">Verify OTP</button>
        </form>
    </div>
</div>
<script src="address.js"></script>


<?php include("footer.php"); ?>
    
</body>
</html>