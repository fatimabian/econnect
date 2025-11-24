<?php
session_start();
include 'db_connect.php';

// Handle Contact Us form submission
if (isset($_POST['submit_complaint'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);

    $sql = "INSERT INTO complaints (full_name, email, phone, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $full_name, $email, $phone, $message);

    if ($stmt->execute()) {
        $success_message = "Message sent successfully!";
    } else {
        $error_message = "Failed to send message. Please try again.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECOnnect - Connecting You to a Greener Future</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            <div class="col-md-4">
                <div class="step-card">
                    <h4>Report Issues</h4>
                    <p class="step-text">Track the truck and send reports directly to the LGU.</p>
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
        <div class="contact-form-container p-5">
            <h3 class="text-center mb-4 contact-heading">Contact Us</h3>
            <p class="text-center text-dark">We'd love to hear from you!</p>

            <?php
            if (!empty($success_message)) {
                echo '<div class="alert alert-success text-center">'.$success_message.'</div>';
            } elseif (!empty($error_message)) {
                echo '<div class="alert alert-danger text-center">'.$error_message.'</div>';
            }
            ?>

            <form action="" method="POST" class="row justify-content-center mt-4">
                <div class="col-md-5">
                    <input type="text" name="full_name" class="form-control contact-input mb-3" placeholder="Full Name" required>
                    <input type="email" name="email" class="form-control contact-input mb-3" placeholder="Email Address" required>
                    <input type="tel" name="phone" class="form-control contact-input mb-4" placeholder="Phone Number" required>

                    <p class="contact-text-small">
                        Or reach out to us directly:<br>
                        <a href="tel:09935048514" class="contact-phone-link">0993-504-8514</a>
                    </p>
                </div>

                <div class="col-md-5">
                    <textarea name="message" class="form-control contact-message-box mb-3" rows="6"
                        placeholder="Write your message here..." required></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="submit_complaint" class="btn btn-send-message">Send Message</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>

</div> <!-- End of container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
