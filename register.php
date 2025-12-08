<?php
session_start();
include "db_connect.php";

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

// --- STEP 1: Send OTP ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sendOtp'])) {
    $fname = trim($_POST['fname']);
    $mname = trim($_POST['mname']);
    $lname = trim($_POST['lname']);
    $suffix = trim($_POST['suffix']);
    $email = trim($_POST['email']);
    $contact = preg_replace('/^0/', '+63', trim($_POST['contact']));
    $region = trim($_POST['region']);
    $province = trim($_POST['province']);
    $city = trim($_POST['city']);
    $barangay = trim($_POST['barangay']);
    $street = trim($_POST['street']);
    $zip = trim($_POST['zip']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Required field validation
    $requiredFields = [
        'fname' => 'First Name',
        'lname' => 'Last Name', 
        'contact' => 'Contact Number',
        'email' => 'Email',
        'region' => 'Region',
        'province' => 'Province',
        'city' => 'City/Municipality',
        'barangay' => 'Barangay',
        'username' => 'Username',
        'password' => 'Password',
        'confirm_password' => 'Confirm Password'
    ];
    
    foreach ($requiredFields as $field => $label) {
        if (empty(trim($_POST[$field] ?? ''))) {
            $errors[$field] = "Please input $label.";
        }
    }

    // Password match
    if (!empty($password) && !empty($confirm_password) && $password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Duplicate checks
    if (!array_filter($errors)) {
        $checks = [
            ['email', $email, 'Email already registered.'],
            ['contact', $contact, 'Contact number already registered.'],
            ['username', $username, 'Username already taken.']
        ];
        
        foreach ($checks as [$column, $value, $msg]) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE $column = ?");
            $stmt->bind_param("s", $value);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $errors[$column] = $msg;
            }
            
            $stmt->close();
        }
    }

    // Send OTP via IPROG SMS
    if (!array_filter($errors)) {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_expiry'] = time() + 300; // 5 minutes
        $_SESSION['pending_user'] = compact(
            'fname', 'mname', 'lname', 'suffix', 'email', 'contact',
            'region', 'province', 'city', 'barangay', 'street', 'zip',
            'username', 'password'
        );

        // IPROG SMS API
        $api_token = getenv('IPROGTECH_API_TOKEN') ?: "dda33f23a9d96e5f433c56d8907c072b40830ef7";
        $sms_data = [
            "api_token" => $api_token,
            "phone_number" => $contact,
            "message" => "Your ECOnnect OTP is: $otp (valid for 5 minutes)"
        ];

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($sms_data)
            ]
        ];

        $context = stream_context_create($options);
        $response = @file_get_contents("https://sms.iprogtech.com/api/v1/sms_messages", false, $context);

        if ($response === FALSE) {
            $errors['otp'] = "Failed to send OTP. Try again.";
        } else {
            $showOtpModal = true;
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

        $stmt = $conn->prepare("INSERT INTO users 
            (fname, mname, lname, suffix, contact, email, region, province, 
             city, barangay, street, zip, username, password)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("ssssssssssssss",
            $user['fname'], $user['mname'], $user['lname'], $user['suffix'],
            $user['contact'], $user['email'], $user['region'], $user['province'],
            $user['city'], $user['barangay'], $user['street'], $user['zip'],
            $user['username'], $hashedPassword
        );
        
        if ($stmt->execute()) {
            unset($_SESSION['otp'], $_SESSION['otp_expiry'], $_SESSION['pending_user']);
            header("Location: login.php");
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
    <title>Register - ECOnnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reg.css">
</head>
<body>
    <!-- Header -->
    <header class="custom-header">
        <div class="header-content">
            <a href="index.php" class="logo-container">
                <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
                <span class="logo-text">ECOnnect</span>
            </a>
            <a href="index.php" class="btn-back">Home</a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="register-container">
            <h2>Join Us!</h2>
            <p class="subtext">Create your account to get started</p>

            <!-- Google Sign Up Button (commented out as per original) -->
            <!--
            <div class="mb-4">
                <button class="google-btn">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="22" alt="Google Logo">
                    Sign up with Google
                </button>
            </div>
            -->

            <div class="divider-or">Sign Up</div>

            <form action="" method="POST">
                <!-- Personal Information -->
                <h5>Personal Information</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">First Name *</label>
                        <input type="text" class="form-control" name="fname" value="<?= htmlspecialchars($_POST['fname'] ?? '') ?>" required>
                        <div class="error-message"><?= $errors['fname'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" class="form-control" name="mname" value="<?= htmlspecialchars($_POST['mname'] ?? '') ?>">
                        <div class="error-message"><?= $errors['mname'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Last Name *</label>
                        <input type="text" class="form-control" name="lname" value="<?= htmlspecialchars($_POST['lname'] ?? '') ?>" required>
                        <div class="error-message"><?= $errors['lname'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Suffix</label>
                        <input type="text" class="form-control" name="suffix" value="<?= htmlspecialchars($_POST['suffix'] ?? '') ?>" placeholder="Jr., III, etc.">
                        <div class="error-message"><?= $errors['suffix'] ?></div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Contact Number *</label>
                        <input type="text" class="form-control" name="contact" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>" required>
                        <div class="error-message"><?= $errors['contact'] ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        <div class="error-message"><?= $errors['email'] ?></div>
                    </div>
                </div>

                <!-- Address Information -->
                <hr>
                <h5>Address Information</h5>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Region *</label>
                        <select id="regionSelect" name="region" class="form-select" required>
                            <option value="">Select Region</option>
                        </select>
                        <div class="error-message"><?= $errors['region'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Province *</label>
                        <select id="provinceSelect" name="province" class="form-select" required>
                            <option value="">Select Province</option>
                        </select>
                        <div class="error-message"><?= $errors['province'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">City/Municipality *</label>
                        <select id="municipalitySelect" name="city" class="form-select" required>
                            <option value="">Select City/Municipality</option>
                        </select>
                        <div class="error-message"><?= $errors['city'] ?></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Barangay *</label>
                        <select id="barangaySelect" name="barangay" class="form-select" required>
                            <option value="">Select Barangay</option>
                        </select>
                        <div class="error-message"><?= $errors['barangay'] ?></div>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Street/House No./Subdivision</label>
                        <input type="text" class="form-control" name="street" value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
                        <div class="error-message"><?= $errors['street'] ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ZIP/Postal Code</label>
                        <input type="text" class="form-control" name="zip" value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>">
                        <div class="error-message"><?= $errors['zip'] ?></div>
                    </div>
                </div>

                <!-- Account Security -->
                <hr>
                <h5>Account Security</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                        <div class="error-message"><?= $errors['username'] ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                        <div class="error-message"><?= $errors['password'] ?></div>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" name="confirm_password" required>
                        <div class="error-message"><?= $errors['confirm_password'] ?></div>
                    </div>
                </div>

                <!-- Terms Agreement -->
                <div class="mt-4 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" required id="termsCheck">
                        <label class="form-check-label" for="termsCheck">
                            I agree to the Terms of Service and Privacy Policy
                        </label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" name="sendOtp" class="create-btn">Create Account</button>
                
                <!-- Login Link -->
                <p class="login-link">
                    Already have an account? <a href="login.php">Log In</a>
                </p>
            </form>
        </div>
    </main>

    <!-- OTP Modal -->
    <div id="otpModal" class="otp-modal <?= $showOtpModal ? '' : 'hidden' ?>">
        <div class="otp-content">
            <h3 class="otp-title">OTP Verification</h3>
            <p class="otp-description">Enter the OTP sent to your contact number:</p>
            
            <form method="POST" id="otpForm">
                <input type="text" name="otp" placeholder="Enter OTP" required class="otp-input" maxlength="6" pattern="\d{6}">
                <div class="error-message text-center mb-3"><?= $errors['otp'] ?></div>
                <button type="submit" name="finalRegister" class="verify-btn">Verify OTP</button>
            </form>
        </div>
    </div>

    <!-- Address Dropdown Script -->
    <script src="address.js"></script>
</body>
</html>