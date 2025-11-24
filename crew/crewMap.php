<?php
session_start();
$crew_id = $_SESSION['crew_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live Truck GPS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<h3>Live GPS Tracking</h3>
<p>Status: Sending GPS...</p>

<button id="completeBtn">Complete Pickup</button>

<script>
let crewId = <?= $crew_id ?>;

// SEND GPS TO SERVER EVERY 5 SECONDS
function sendGPS(lat, lng) {
    let form = new FormData();
    form.append("crew_id", crewId);
    form.append("lat", lat);
    form.append("lng", lng);

    fetch("update_location.php", {
        method: "POST",
        body: form
    });
}

function success(pos) {
    let lat = pos.coords.latitude;
    let lng = pos.coords.longitude;

    sendGPS(lat, lng);
}

function error(err) {
    console.warn("GPS Error: ", err);
}

navigator.geolocation.watchPosition(success, error, {
    enableHighAccuracy: true,
    maximumAge: 0
});

// COMPLETE PICKUP
document.getElementById("completeBtn").onclick = () => {
    fetch("complete_pickup.php?crew_id="+crewId)
    .then(r=>r.text())
    .then(alert);
};
</script>

</body>
</html>
