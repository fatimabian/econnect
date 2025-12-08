<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECOnnect - Connecting You to a Greener Future</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="econnect.css">
</head>
<body>

<div class="container-fluid p-0">

<?php include 'header.php'; ?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-content">
        <div class="container">
            <div class="hero-card text-center">
                <h2>Connecting You to a Greener Future</h2>
                <p>Sustainable Solutions for a Better Tomorrow</p>
                <a href="#how-it-works" class="btn btn-explore">Explore Our Offerings</a>
            </div>
        </div>
    </div>
</section>

<!-- MISSION & VISION -->
<section class="mission-vision py-5" id="mission-vision">
    <div class="container mission-wrapper p-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex mb-4 align-items-start">
                    <img src="img/sprout.png" class="me-3 mission-img" alt="Sprout">
                    <div>
                        <h3 class="title-heading">Mission</h3>
                        <p class="text-secondary">
                            "Our mission is to provide an efficient, transparent, and digitally-enabled waste
                            management system that empowers communities to become effective collectors and authorities.
                            ECOnnect delivers real-time scheduling, notifications, and updates to achieve reliable waste
                            collection and sustainable cities."
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-start">
                    <img src="img/sprout.png" class="me-3 mission-img" alt="Sprout">
                    <div>
                        <h3 class="title-heading">Vision</h3>
                        <p class="text-secondary">
                            "To build aware, united, and vibrant communities through technology-driven waste management,
                            empowering every household to contribute to a greener future."
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 d-flex justify-content-center pt-4 pt-lg-0">
                <img src="img/orange.png" class="poster-img" alt="Sustainability Poster">
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works py-5" id="how-it-works">
    <div class="container how-wrapper p-5">
        <h3 class="text-center mb-4 title-heading">How It Works</h3>
        <p class="text-center mb-5 text-secondary">ECOnnect works in 3 simple steps</p>

        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <h4>Check Your Schedule</h4>
                    <p class="step-text">View your barangay's waste collection timetable anytime.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <h4>Receive Notifications</h4>
                    <p class="step-text">Get updates when pickup is delayed, cancelled, or on the way.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="step-card">
                    <h4>Report Issues</h4>
                    <p class="step-text">Send reports directly to the LGU.</p>
                </div>
            </div>
        </div>

        <div class="row text-center icons-row mt-4">
            <div class="col-md-4">
                <img src="img/leaf.png" class="process-icon" alt="Bloom">
                <p class="icon-label">Bloom</p>
            </div>
            <div class="col-md-4">
                <img src="img/partnership.png" class="process-icon" alt="Connect">
                <p class="icon-label">Connect</p>
            </div>
            <div class="col-md-4">
                <img src="img/chart.png" class="process-icon" alt="Grow">
                <p class="icon-label">Grow</p>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT US -->
<section class="contact-us-section py-5" id="contact-us">
    <div class="container">
        <div class="contact-box bg-light p-5 rounded shadow-lg">

            <h3 class="text-center mb-4 fw-bold" style="color:#3f4a36;">Contact Us</h3>
            <p class="text-center mb-5">We’d love to connect with you!</p>

            <div class="row align-items-center">

                <!-- LEFT SIDE: CONTACT DETAILS -->
                <div class="col-md-6 mb-4 mb-md-0">
                    <h5 class="fw-bold" style="color:#3f4a36;">📍 Address</h5>
                    <p class="mb-3">Bagong Pook, Rosario, Batangas</p>

                    <h5 class="fw-bold" style="color:#3f4a36;">📧 Email</h5>
                    <p class="mb-3">
                        <a href="mailto:econnect@gmail.com" class="text-dark text-decoration-none fw-semibold">
                            econnect@gmail.com
                        </a>
                    </p>

                    <h5 class="fw-bold" style="color:#3f4a36;">📞 Phone</h5>
                    <p>
                        <a href="tel:09935048513" class="text-dark text-decoration-none fw-semibold">
                            0993 504 8513
                        </a>
                    </p>
                </div>

                <!-- RIGHT SIDE: SOCIAL ICONS -->
                <div class="col-md-6 text-center">
                    <h5 class="fw-bold mb-3" style="color:#3f4a36;">Follow Us</h5>

                    <div class="d-flex justify-content-center gap-4">

                        <a href="https://facebook.com" target="_blank" class="social-icon-link">
                            <i class="fab fa-facebook-f social-icon-fa"></i>
                        </a>

                        <a href="https://instagram.com" target="_blank" class="social-icon-link">
                            <i class="fab fa-instagram social-icon-fa"></i>
                        </a>

                        <a href="https://twitter.com" target="_blank" class="social-icon-link">
                            <i class="fab fa-twitter social-icon-fa"></i>
                        </a>

                    </div>

                    <p class="mt-3 text-secondary">Stay connected with our updates</p>
                </div>
            </div>
        </div>
    </div>
</section>


<?php include 'footer.php'; ?>

</div> <!-- End of container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
