<?php
session_start();
include '../db_connect.php'; // Adjusted path for your folder structure

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch current user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inknut+Antiqua:wght@700&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'nav.php'; ?> 

<div class="settings-container">
    <h2>User Settings</h2>
    <p>Manage your profile and preferences</p>

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">Profile updated successfully!</p>
    <?php endif; ?>

    <form action="update_user_settings.php" method="POST">

        <!-- PERSONAL INFORMATION + CHANGE PASSWORD SIDE BY SIDE -->
        <div class="settings-row">

            <!-- PERSONAL INFORMATION -->
            <div class="settings-section">
                <div class="section-title">Personal Information</div>

                <div class="field-row">
                    <input type="text" name="fname" value="<?php echo htmlspecialchars($user['fname']); ?>" placeholder="First Name">
                    <input type="text" name="mname" value="<?php echo htmlspecialchars($user['mname']); ?>" placeholder="Middle Name">
                </div>

                <div class="field-row">
                    <input type="text" name="lname" value="<?php echo htmlspecialchars($user['lname']); ?>" placeholder="Last Name">
                    <input type="text" name="suffix" value="<?php echo htmlspecialchars($user['suffix']); ?>" placeholder="Suffix">
                </div>

                <div class="field-row">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" placeholder="Email Address">
                </div>

                <div class="field-row">
                    <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact']); ?>" placeholder="Phone Number">
                </div>
            </div>

            <!-- CHANGE PASSWORD -->
            <div class="settings-section change-password">
                <div class="section-title">Change Password</div>

                <div class="field-row">
                    <input type="password" name="last_password" placeholder="Last Password">
                </div>

                <div class="field-row">
                    <input type="password" name="new_password" placeholder="New Password">
                </div>

                <div class="field-row">
                    <input type="password" name="confirm_password" placeholder="Confirm Password">
                </div>
            </div>
        </div>

        <!-- LOCATION AND BUTTONS ROW -->
        <div class="location-btn-row">

            <!-- LOCATION AND ADDRESS -->
            <div class="settings-section location-small">
                <div class="section-title">Location and Address</div>

                <div class="field-row">
                    <input type="text" name="street" value="<?php echo htmlspecialchars($user['street']); ?>" placeholder="Street/House No./Subdivision">
                    <input type="text" name="barangay" value="<?php echo htmlspecialchars($user['barangay']); ?>" placeholder="Barangay">
                </div>

                <div class="field-row">
                    <input type="text" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" placeholder="City/Municipality">
                    <input type="text" name="province" value="<?php echo htmlspecialchars($user['province']); ?>" placeholder="Province">
                </div>

                <div class="field-row">
                    <input type="text" name="region" value="<?php echo htmlspecialchars($user['region']); ?>" placeholder="Region">
                    <input type="text" name="zip" value="<?php echo htmlspecialchars($user['zip']); ?>" placeholder="Zip/Postal Code">
                </div>
            </div>

            <!-- BUTTONS -->
            <div class="button-column">
                <button type="button" class="btn-cancel">Cancel</button>
                <button type="submit" class="btn-submit">Submit</button>
            </div>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>

<style>
    /* --- GENERAL LAYOUT --- */
    .settings-container {
        max-width: 1100px;
        margin: 40px auto;
        box-sizing: border-box;
    }

    .settings-container h2 {
        margin-top: -5px;
        font-size: 28px;
        font-weight: 700;
        text-align: left;
    }

    .settings-container p {
        margin-top: -8px;
        margin-bottom: 25px;
        font-size: 15px;
    }

    /* ROW LAYOUT */
    .settings-row {
        display: flex;
        gap: 25px;
        flex-wrap: wrap;
    }

    /* --- SECTIONS --- */
    .settings-section {
        background: #d3dfcd;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
        flex: 1;
        min-width: 350px;
    }

    /* Smaller width for Change Password */
    .change-password {
        flex: 0 0 35%;
        min-width: 260px;
    }

    .section-title {
        font-weight: 700;
        text-align: center;
        margin-bottom: 15px;
        font-size: 18px;
    }

    /* --- INPUT FIELDS --- */
    .field-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .field-row input {
        flex: 1;
        padding: 10px;
        border-radius: 18px;
        border: 1px solid #bbb;
        font-size: 14px;
        box-sizing: border-box;
    }

    /* LOCATION AND BUTTONS SIDE BY SIDE */
    .location-btn-row {
        display: flex;
        gap: 25px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .location-small {
        flex: 1;
        min-width: 350px;
    }

    /* --- BUTTONS --- */
    .button-column {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 5px;
    }

    .button-column button {
        padding: 10px 25px;
        border: none;
        border-radius: 16px;
        cursor: pointer;
        font-size: 15px;
        width: 140px;
    }

    .btn-cancel {
        background: #dddddd;
    }

    .btn-submit {
        background: #526a48;
        color: white;
    }

    /* Left sidebar spacing */
    @media (min-width: 900px) {
        .settings-container {
            margin-left: 240px; /* Adjust according to your side nav width */
        }
    }

    /* Prevent unwanted scrolling */
    html, body {
        overflow-x: hidden;
    }
</style>

</body>
</html>
