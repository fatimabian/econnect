<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ECOnnect Footer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="econnect.css">
<style>
.custom-footer {
    background-color: #3f4a36;
    color: white;
    padding: 15px 0;
    font-size: 14px;
}
.footer-link-text {
    color: white;
    text-decoration: none;
    margin-right: 15px;
}
.footer-link-text:hover {
    text-decoration: underline;
}
.footer-links-social a {
    display: flex;
    align-items: center;
}
.footer-links-social i {
    font-size: 18px;
    margin-left: 10px;
    color: white;
    transition: 0.3s;
}
.footer-links-social i:hover {
    color: #a0d468;
}
</style>
</head>
<body>

<footer class="custom-footer mt-auto">
    <div class="container d-flex justify-content-between align-items-center flex-wrap">
        <div class="mb-2 mb-md-0">
            © 2025 ECOnnect | All Rights Reserved.
        </div>

        <div class="footer-links-social d-flex align-items-center">
            <a href="../privacy.php" class="footer-link-text">Privacy Policy</a>
            <a href="../terms.php" class="footer-link-text">Terms of Service</a>

            <!-- Social Icons -->
            <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
