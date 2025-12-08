<?php
session_start();
include "db_connect.php";

$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_type = $_POST['recipient_type'] ?? '';
    $message = trim($_POST['message'] ?? '');

    if (!$recipient_type || !$message) {
        $_SESSION['error'] = "Please select a recipient and enter a message.";
        header("Location: notification.php");
        exit;
    }

    if ($recipient_type === 'citizens') {
        // Send message to all users
        $sql = "SELECT id FROM users";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $user_id = $row['id'];
            $stmt = $conn->prepare("INSERT INTO user_inbox (admin_id, user_id, message, status, created_at) VALUES (?, ?, ?, 'Unread', NOW())");
            $stmt->bind_param("iis", $admin_id, $user_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($recipient_type === 'crew') {
        // Send message to all crew
        $sql = "SELECT id FROM collection_crew";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
            $crew_id = $row['id'];
            $stmt = $conn->prepare("INSERT INTO crew_inbox (crew_id, admin_id, message, status, created_at) VALUES (?, ?, ?, 'Unread', NOW())");
            $stmt->bind_param("iis", $crew_id, $admin_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }

    $_SESSION['success'] = "Message sent successfully!";
    header("Location: notification.php");
    exit;
}
