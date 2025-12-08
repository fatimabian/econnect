<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ECOnnect - Connecting You to a Greener Future</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="econnect.css">
</head>

<body>

<!-- HEADER -->
<header class="custom-header">
    <div class="header-container">
        <a href="index.php" class="logo-container">
            <img src="img/logoo.png" alt="ECOnnect Logo" class="header-logo">
            <span class="logo-text">ECOnnect</span>
        </a>

        <!-- Desktop Navigation -->
        <nav class="desktop-nav">
            <ul class="nav-links">
                <li class="nav-item"><a class="nav-link" href="index.php#mission-vision">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="faqs.php">FAQs</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#contact-us">Contact</a></li>
            </ul>

            <div class="auth-buttons">
                <button class="btn btn-login" onclick="window.location.href='login.php'">Log In</button>
                <button class="btn btn-signup" onclick="window.location.href='register.php'">Sign Up</button>
            </div>
        </nav>

        <!-- Mobile menu toggle -->
        <button class="mobile-menu-btn" type="button" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li class="nav-item"><a class="nav-link" href="index.php#mission-vision">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="faqs.php">FAQs</a></li>
            <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
            <li class="nav-item"><a class="nav-link" href="index.php#contact-us">Contact</a></li>
        </ul>
        <div class="mobile-auth-buttons">
            <button class="btn btn-login" onclick="window.location.href='login.php'">Log In</button>
            <button class="btn btn-signup" onclick="window.location.href='register.php'">Sign Up</button>
        </div>
    </div>
</header>

<!-- SPACER FOR FIXED HEADER -->
<div class="header-spacer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuToggle').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobileMenu');
        mobileMenu.classList.toggle('active');
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const mobileMenu = document.getElementById('mobileMenu');
        const menuToggle = document.getElementById('mobileMenuToggle');
        
        if (!mobileMenu.contains(event.target) && !menuToggle.contains(event.target)) {
            mobileMenu.classList.remove('active');
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if(targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if(targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                document.getElementById('mobileMenu').classList.remove('active');
            }
        });
    });
</script>
</body>
</html>