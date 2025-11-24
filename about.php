<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }

        .content-wrapper {
            max-width: 900px;
            margin: 100px auto 150px auto;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .content-wrapper h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #3f4a36;
        }

        .about-box {
            background-color: #e0ecd8;
            padding: 20px;
            border-radius: 8px;
            line-height: 1.6;
            color: #2f4b2f;
            font-size: 15px;
            margin-bottom: 30px;
        }

        .team-section {
            text-align: center;
        }

        .team-section h3 {
            color: #3f4a36;
            margin-bottom: 20px;
        }

        .team-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
        }

        .team-card {
            background-color: #e0ecd8;
            padding: 15px;
            border-radius: 8px;
            width: 250px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }

        .team-card img {
            width: 100%;
            height: auto;
            border-radius: 20px; /* oblong shape */
            object-fit: cover;
            margin-bottom: 15px;
        }

        .team-card h4 {
            margin: 10px 0 5px 0;
            color: #2f4b2f;
        }

        .team-card p {
            margin: 5px 0;
            font-size: 14px;
            color: #2f4b2f;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    include 'header.php';
    ?>

    <div class="content-wrapper">
        <h2>About Us</h2>

        <div class="about-box">
            <p>
                Welcome to EcoTrack! We are dedicated to providing smart and sustainable waste management solutions for communities across the Philippines.
            </p>
            <p>
                Our system allows residents to easily track their waste collection schedules, submit complaints, and stay informed about environmental initiatives in their barangay.
            </p>
            <p>
                EcoTrack’s mission is to promote cleaner, greener, and healthier communities by using technology to simplify waste management for both residents and local administrators.
            </p>
            <p>
                Join us in creating a more sustainable future for everyone.
            </p>
        </div>

        <div class="team-section">
            <h3>Meet the Team</h3>
            <div class="team-cards">
                <div class="team-card">
                    <img src="img/Anjelika.jpg" alt="Anjelika B. Anonuevo">
                    <h4>Anjelika B. Anonuevo</h4>
                    <p>UI/UX Designer</p>
                    <p>Front End Developer</p>
                    <p>Documentation</p>
                </div>
                <div class="team-card">
                    <img src="img/Fatima.jpg" alt="Fatima Bian R. Arnigo">
                    <h4>Fatima Bian R. Arnigo</h4>
                    <p>UI/UX Designer</p>
                    <p>Full Stack Developer</p>
                    <p>Documentation</p>
                </div>
                <div class="team-card">
                    <img src="img/Alexa.jpg" alt="Alexa Sophia L. Landicho">
                    <h4>Alexa Sophia L. Landicho</h4>
                    <p>Front End Developer</p>
                    <p>Database Management</p>
                    <p>Documentation</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
