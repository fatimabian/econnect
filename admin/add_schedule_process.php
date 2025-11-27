<?php
session_start();
include "db_connect.php";

// ---------------------------
// CHECK IF ADMIN LOGGED IN
// ---------------------------
$admin_id = $_SESSION['barangay_admin_id'] ?? null;
if (!$admin_id) {
    header("Location: ../login.php");
    exit;
}

// ---------------------------
// GET FORM DATA
// ---------------------------
$barangay = $_POST['barangay'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';

if (empty($barangay) || empty($date) || empty($time)) {
    $_SESSION['error'] = "Please fill all fields.";
    header("Location: collection_management.php");
    exit;
}

// ---------------------------
// INSERT SCHEDULE
// ---------------------------
$stmt = $conn->prepare("INSERT INTO collection_schedule (barangay, date, time) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $barangay, $date, $time);

if ($stmt->execute()) {

    // ---------------------------
    // GET PHONE NUMBERS FOR USERS IN THIS BARANGAY
    // ---------------------------
    $phones = [];
    $res = $conn->prepare("SELECT contact FROM users WHERE barangay=? AND status='Active' AND contact IS NOT NULL");
    $res->bind_param("s", $barangay);
    $res->execute();
    $result = $res->get_result();
    while ($row = $result->fetch_assoc()) {
        $phone = preg_replace('/\s+/', '', $row['contact']); // remove spaces

        // Convert 09XXXXXXXXX to +639XXXXXXXXX
        if (preg_match('/^09\d{9}$/', $phone)) {
            $phone = '+63' . substr($phone, 1);
        }

        if (!empty($phone)) $phones[] = $phone;
    }
    $res->close();

    // ---------------------------
    // SEND BULK SMS USING IPROG (one request per number)
    // ---------------------------
    if (!empty($phones)) {
        $api_token = "dda33f23a9d96e5f433c56d8907c072b40830ef7";
        $message = "Barangay $barangay: New waste collection schedule on $date at $time. Please be ready. -ECOnnect";

        $url = "https://sms.iprogtech.com/api/v1/sms_messages";

        foreach ($phones as $phone) {
            $data = [
                "api_token"    => $api_token,
                "phone_number" => $phone,
                "message"      => $message
            ];

            $options = [
                'http' => [
                    'header'  => "Content-Type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data),
                ]
            ];

            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);

            if ($response === FALSE) {
                // Log errors for troubleshooting
                file_put_contents('sms_error_log.txt', date('Y-m-d H:i:s') . " - Failed to send to $phone".PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents('sms_log.txt', date('Y-m-d H:i:s') . " - Sent to $phone: $response".PHP_EOL, FILE_APPEND);
            }
        }
    }

    $_SESSION['success'] = "Schedule added and SMS sent!";
    header("Location: collection_management.php");
    exit;

} else {
    $_SESSION['error'] = "Failed to add schedule.";
    header("Location: collection_management.php");
    exit;
}
?>
