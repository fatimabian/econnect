<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ECOnnect Terms & Conditions</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body {
        background: url('img/tree.jpg') no-repeat center center fixed;
        background-size: cover;
        min-height: 100vh;
    }
    .custom-header {
        background-color: #3f4a36;
        padding: 15px 0;
    }
    .header-logo {
        height: 40px;
        margin-right: 10px;
    }
    .btn-back {
        color: white;
        background: transparent;
        border: 1px solid white;
        border-radius: 8px;
        padding: 5px 12px;
        text-decoration: none;
        font-weight: 500;
    }
    .btn-back:hover {
        background: white;
        color: #3f4a36;
        transition: 0.3s ease;
    }
    .content-card {
        background-color: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        padding: 30px;
        margin-top: 50px;
        margin-bottom: 50px;
    }
</style>
</head>
<body>

<!-- Header -->
<header class="custom-header">
    <div class="container d-flex justify-content-between align-items-center">
        <a href="index.php" class="d-flex align-items-center text-decoration-none">
            <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
            <span class="text-white fw-bold">ECOnnect</span>
        </a>
        <a href="javascript:history.back()" class="btn-back">Back</a>
    </div>
</header>

<!-- Content -->
<div class="container">
    <div class="content-card">
        <h2 class="text-2xl font-bold mb-4 text-green-700">Terms & Conditions</h2>
        <p class="mb-4 text-gray-700">
            Welcome to <strong>ECOnnect</strong>. By accessing or using our platform, you agree to comply with these Terms and Conditions. Please read them carefully.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">1. User Accounts</h5>
        <p class="text-gray-700 mb-2">
            Users must create an account to access ECOnnect services. You are responsible for maintaining the confidentiality of your username and password.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">2. Service Usage</h5>
        <p class="text-gray-700 mb-2">
            ECOnnect provides scheduling and notification services for solid waste collection. Users must provide accurate personal and address information.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">3. Prohibited Activities</h5>
        <ul class="list-disc list-inside text-gray-700">
            <li>Submitting false information.</li>
            <li>Misusing the platform or interfering with its operations.</li>
            <li>Attempting to access unauthorized areas or accounts.</li>
        </ul>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">4. Intellectual Property</h5>
        <p class="text-gray-700 mb-2">
            All content, logos, and designs of ECOnnect are protected under copyright laws. Users may not reproduce or distribute content without permission.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">5. Limitation of Liability</h5>
        <p class="text-gray-700 mb-2">
            ECOnnect is not liable for any direct or indirect damages resulting from the use or inability to use the platform. Users use the service at their own risk.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">6. Modifications</h5>
        <p class="text-gray-700 mb-2">
            ECOnnect may modify these Terms and Conditions at any time. Users are encouraged to review this page periodically.
        </p>

        <p class="text-gray-700 mt-6">
            By using ECOnnect, you acknowledge that you have read, understood, and agree to these Terms and Conditions.
        </p>
    </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>
