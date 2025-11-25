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

/* ===========================
   STEP 1: SEND OTP
=========================== */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendOtp'])) {

    // Sanitize Inputs
    $fname    = trim($_POST['fname']);
    $mname    = trim($_POST['mname']);
    $lname    = trim($_POST['lname']);
    $suffix   = trim($_POST['suffix']);
    $email    = trim($_POST['email']);
    $contact  = preg_replace('/^0/', '+63', trim($_POST['contact']));
    $region   = $_POST['region'];
    $province = $_POST['province'];
    $city     = $_POST['city'];
    $barangay = $_POST['barangay'];
    $street   = $_POST['street'];
    $zip      = $_POST['zip'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password Match
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Duplicate Checks
    $checks = [
        ['email', $email, 'Email already registered.'],
        ['contact', $contact, 'Contact number already registered.'],
        ['username', $username, 'Username already taken.']
    ];

    foreach ($checks as [$column, $value, $message]) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE $column = ?");
        $stmt->bind_param("s", $value);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[$column] = $message;
        }
        $stmt->close();
    }

    // If no errors → send OTP
    if (!array_filter($errors)) {

        $otp = rand(100000, 999999);

        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300;
        $_SESSION['pending_user'] = compact(
            'fname','mname','lname','suffix','email','contact',
            'region','province','city','barangay','street','zip',
            'username','password'
        );

        // Vonage
        $apiKey = "7980a169";
        $apiSecret = "ISnASXhxL4ktm50v";

        $basic = new Basic($apiKey, $apiSecret);
        $client = new Client($basic);

        try {
            $sms = new SMS($contact, "ECOnnect", "Your ECOnnect OTP is: $otp. Valid for 5 minutes.");
            $response = $client->sms()->send($sms);

            if ($response->current()->getStatus() == 0) {
                $showOtpModal = true;
            } else {
                $errors['otp'] = "Failed to send OTP.";
            }
        } catch (Exception $e) {
            $errors['otp'] = "OTP Error: " . $e->getMessage();
        }
    }
}

/* ===========================
   STEP 2: FINAL REGISTER
=========================== */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['finalRegister'])) {
    $entered_otp = trim($_POST['otp']);

    if (!isset($_SESSION['otp']) || time() > $_SESSION['otp_expiry']) {
        $errors['otp'] = "OTP expired.";
        $showOtpModal = true;
    } elseif ($entered_otp != $_SESSION['otp']) {
        $errors['otp'] = "Invalid OTP.";
        $showOtpModal = true;
    } else {
        // Save user to DB
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
            $errors['otp'] = "Database Error: " . $stmt->error;
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="reg.css">
</head>

<body>

<header class="custom-header">
    <div class="container d-flex justify-content-between align-items-center py-2">
        <a href="home.php" class="text-decoration-none d-flex align-items-center">
            <img src="img/logoo.png" class="header-logo">
            <span class="text-white fw-bold">ECOnnect</span>
        </a>
        <a href="index.php" class="btn-back">Home</a>
    </div>
</header>

<div class="register-container">
    <h2>Join Us!</h2>
    <p class="subtext">Create your account to get started</p>

    <div class="divider-or">OR</div>

<!-- ========================================================= -->
<!--      THIS IS THE CORRECT PLACE FOR YOUR FULL FORM        -->
<!-- ========================================================= -->

<form action="" method="POST">

    <!-- PERSONAL INFORMATION -->
    <h5 class="mb-3">Personal Information</h5>

    <div class="row g-3">
        <div class="col-md-3">
            <label>First Name</label>
            <input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($_POST['fname'] ?? '') ?>" required>
            <p class="text-red-600 text-sm"><?= $errors['fname'] ?></p>
        </div>

        <div class="col-md-3">
            <label>Middle Name</label>
            <input type="text" name="mname" class="form-control" value="<?= htmlspecialchars($_POST['mname'] ?? '') ?>">
            <p class="text-red-600 text-sm"><?= $errors['mname'] ?></p>
        </div>

        <div class="col-md-3">
            <label>Last Name</label>
            <input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($_POST['lname'] ?? '') ?>" required>
            <p class="text-red-600 text-sm"><?= $errors['lname'] ?></p>
        </div>

        <div class="col-md-3">
            <label>Suffix</label>
            <input type="text" name="suffix" class="form-control" value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>">
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label>Contact Number</label>
            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
            <p class="text-red-600 text-sm"><?= $errors['contact'] ?></p>
        </div>

        <div class="col-md-6">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <p class="text-red-600 text-sm"><?= $errors['email'] ?></p>
        </div>
    </div>

    <hr class="mt-4">

    <!-- ADDRESS -->
    <h5 class="mb-3">Address Information</h5>

    <div class="row g-3">
        <div class="col-md-3">
            <label>Region</label>
            <select id="regionSelect" name="region" class="form-control" required></select>
            <p class="text-red-600 text-sm"><?= $errors['region'] ?></p>
        </div>

        <div class="col-md-3">
            <label>Province</label>
            <select id="provinceSelect" name="province" class="form-control" required></select>
            <p class="text-red-600 text-sm"><?= $errors['province'] ?></p>
        </div>

        <div class="col-md-3">
            <label>City/Municipality</label>
            <select id="municipalitySelect" name="city" class="form-control" required></select>
            <p class="text-red-600 text-sm"><?= $errors['city'] ?></p>
        </div>

        <div class="col-md-3">
            <label>Barangay</label>
            <select id="barangaySelect" name="barangay" class="form-control" required></select>
            <p class="text-red-600 text-sm"><?= $errors['barangay'] ?></p>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label>Street / House No.</label>
            <input type="text" class="form-control" name="street">
        </div>

        <div class="col-md-6">
            <label>ZIP</label>
            <input type="text" class="form-control" name="zip">
        </div>
    </div>

    <hr class="mt-4">

    <!-- ACCOUNT SECURITY -->
    <h5 class="mb-3">Account Security</h5>

    <label>Username</label>
    <input type="text" name="username" class="form-control" required>
    <p class="text-red-600 text-sm"><?= $errors['username'] ?></p>

    <div class="row g-3 mt-2">
        <div class="col-md-6">
            <label>Password</label>
            <input type="password" class="form-control" name="password" required>
            <p class="text-red-600 text-sm"><?= $errors['password'] ?></p>
        </div>

        <div class="col-md-6">
            <label>Confirm Password</label>
            <input type="password" class="form-control" name="confirm_password" required>
            <p class="text-red-600 text-sm"><?= $errors['confirm_password'] ?></p>
        </div>
    </div>

    <div class="mt-4 mb-3">
        <input type="checkbox" required>
        <label>I agree to the Terms of Service</label>
    </div>

    <button type="submit" name="sendOtp" class="create-btn w-100">Create Account</button>

</form>

</div>

<!-- OTP MODAL -->
<div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 <?= $showOtpModal ? 'flex' : 'hidden' ?> items-center justify-center z-50">
    <div class="bg-white p-6 rounded-xl shadow-xl w-full max-w-md">
        <h3 class="text-xl font-semibold text-green-700 text-center">OTP Verification</h3>
        <p class="text-center mb-4">Enter the OTP sent to your number:</p>

        <form method="POST">
            <input type="text" name="otp" class="w-full p-2 border rounded text-center" required>
            <p class="text-red-600 text-sm text-center"><?= $errors['otp'] ?></p>

            <button type="submit" name="finalRegister" class="w-full bg-green-600 text-white p-2 rounded mt-3">
                Verify OTP
            </button>
        </form>
    </div>
</div>

<script src="address.js"></script>

<?php include "footer.php"; ?>

</body>
</html>
