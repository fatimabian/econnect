<?php
session_start();
include "db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // -----------------------------
    // HARDCODED SUPER ADMIN
    // -----------------------------
    if ($username === "superAdmin" && $password === "1superadmin") {
        $_SESSION['super_admin_id'] = 1; // fixed ID for super admin
        header("Location: superadmin/superAdmin.php");
        exit;
    }

    $userFound = false;

    // -----------------------------
    // BARANGAY ADMIN
    // -----------------------------
    $stmt = $conn->prepare("SELECT id, password FROM barangay_admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $userFound = true;
        if (password_verify($password, $row['password'])) {
            $_SESSION['barangay_admin_id'] = $row['id'];
            header("Location: admin/adminDash.php");
            exit;
        }
    }
    $stmt->close();

    // -----------------------------
    // COLLECTION CREW
    // -----------------------------
    if (!$userFound) {
        $stmt = $conn->prepare("SELECT id, password FROM collection_crew WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $userFound = true;
            if (password_verify($password, $row['password'])) {
                $_SESSION['crew_id'] = $row['id'];
                header("Location: crew/crewDash.php");
                exit;
            }
        }
        $stmt->close();
    }

    // -----------------------------
    // CITIZEN USERS
    // -----------------------------
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

    // -----------------------------
    // LOGIN FAILED
    // -----------------------------
    $_SESSION['login_error'] = "Incorrect username or password.";
    header("Location: login.php");
    exit;
}
?>
