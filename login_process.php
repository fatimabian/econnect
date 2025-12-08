<?php
session_start();
include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Super Admin
    // if ($username === "superAdmin" && $password === "1superadmin") {
    //     $_SESSION['super_admin_id'] = 1; // fixed ID for super admin
    //     header("Location: superadmin/superAdmin.php");
    //     exit;
    // }

    // $userFound = false;

$stmt = $conn->prepare("SELECT id, password FROM super_admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userFound = true;
    if (password_verify($password, $row['password'])) {
        $_SESSION['super_admin_id'] = $row['id'];
        header("Location: superadmin/superAdmin.php");
        exit;
    }
}
$stmt->close();


// BARANGAY ADMIN
$stmt = $conn->prepare("SELECT id, password, status FROM barangay_admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userFound = true;
    if ($row['status'] === 'Inactive' && password_verify($password, $row['password'])) {
        // Save user info for OTP verification
        $_SESSION['pending_activation_user'] = [
            'id' => $row['id'],
            'username' => $username,
            'type' => 'barangay_admin'
        ];
        header("Location: phone_verification.php");
        exit;
    } elseif ($row['status'] === 'Active' && password_verify($password, $row['password'])) {
        $_SESSION['barangay_admin_id'] = $row['id'];
        header("Location: admin/adminDash.php");
        exit;
    } else {
        $_SESSION['login_error'] = "Incorrect password.";
        header("Location: login.php");
        exit;
    }
}
$stmt->close();

// COLLECTION CREW
$stmt = $conn->prepare("SELECT id, password, status FROM collection_crew WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $userFound = true;
    if ($row['status'] === 'Inactive' && password_verify($password, $row['password'])) {
        // Save user info for OTP verification
        $_SESSION['pending_activation_user'] = [
            'id' => $row['id'],
            'username' => $username,
            'type' => 'crew'
        ];
        header("Location: phone_verification.php");
        exit;
    } elseif ($row['status'] === 'Active' && password_verify($password, $row['password'])) {
        $_SESSION['crew_id'] = $row['id'];
        header("Location: crew/crewDash.php");
        exit;
    } else {
        $_SESSION['login_error'] = "Incorrect password.";
        header("Location: login.php");
        exit;
    }
}
$stmt->close();



    // CITIZEN USERS
    if (!$userFound) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userFound = true;
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                header("Location: users/usersDash.php");
                exit;
            }
        }
        $stmt->close();
    }


    // LOGIN FAILED
    $_SESSION['login_error'] = "Incorrect username or password.";
    header("Location: login.php");
    exit;
}
?>
