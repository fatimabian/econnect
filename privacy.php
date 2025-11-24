<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ECOnnect Privacy Policy</title>
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
        margin-top: 70px;
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
        <h2 class="text-2xl font-bold mb-4 text-green-700">Privacy Policy</h2>
        <p class="mb-4 text-gray-700">
            At <strong>ECOnnect</strong>, we value your privacy. This policy explains how we collect, use, and protect your personal information when you use our platform.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">Information We Collect</h5>
        <ul class="list-disc list-inside text-gray-700">
            <li>Personal Information: Name, contact number, email, and address.</li>
            <li>Account Information: Username and password.</li>
            <li>Usage Data: How you interact with the platform, including schedules and notifications.</li>
        </ul>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">How We Use Your Information</h5>
        <ul class="list-disc list-inside text-gray-700">
            <li>To provide and maintain our services.</li>
            <li>To send notifications regarding waste collection schedules and updates.</li>
            <li>To improve and personalize your experience on ECOnnect.</li>
        </ul>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">Data Sharing and Security</h5>
        <p class="text-gray-700 mb-2">
            We do not sell your personal information. Your data is securely stored and only shared with relevant local authorities and collection teams for service purposes. We implement measures to protect your data from unauthorized access.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">Cookies</h5>
        <p class="text-gray-700 mb-2">
            Our platform may use cookies to improve user experience. Cookies help us remember your preferences and analyze usage patterns.
        </p>

        <h5 class="font-semibold mt-4 mb-2 text-green-600">Your Rights</h5>
        <p class="text-gray-700 mb-2">
            You may request access to, correction, or deletion of your personal information by contacting our support team.
        </p>

        <p class="text-gray-700 mt-6">
            By using ECOnnect, you agree to the terms outlined in this Privacy Policy. We may update this policy periodically, and any changes will be reflected on this page.
        </p>
    </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>
