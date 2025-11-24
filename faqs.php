<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs</title>

    <style>
.content-wrapper {
    max-width: 800px;
    margin: 100px auto 150px auto;
    padding: 20px;
    background-color: #f5f5f5;
    border-radius: 10px;
}

.content-wrapper h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #3f4a36;
}

.faq-box {
    background-color: #e0ecd8;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.faq-box h4 {
    margin-bottom: 8px;
    color: #2f4b2f;
}

.faq-box p {
    margin: 0;
    font-size: 15px;
}
</style>


</head>
<body>
    <?php
session_start();
include 'header.php';
?>

    <div class="content-wrapper">
    <h2>Frequently Asked Questions (FAQs)</h2>

    <div class="faq-box">
        <h4>Q1: How do I check my waste collection schedule?</h4>
        <p>A: You can check your next and previous schedules on your Inbox or the Calendar section in your dashboard.</p>
    </div>

    <div class="faq-box">
        <h4>Q2: How can I submit a complaint?</h4>
        <p>A: Go to the Complaint page, fill out the form, and submit. You can track its status in your Inbox.</p>
    </div>

    <div class="faq-box">
        <h4>Q3: Can I change my registered address?</h4>
        <p>A: Currently, address changes are managed by the admin. Please contact support if you need to update it.</p>
    </div>

    <div class="faq-box">
        <h4>Q4: How can I contact support?</h4>
        <p>A: Use the Contact Us page or email support@example.com for assistance.</p>
    </div>
</div>

<?php include 'footer.php'; ?>

</body>
</html>