<?php
session_start();
$crew_id = $_SESSION['crew_id'] ?? 0; // fallback if not set
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Live Truck GPS</title>
<style>
/* Body & page reset */
body, html {
    margin:0; padding:0; height:100%; font-family: Arial, sans-serif;
}

/* Content wrapper, below header and beside sidebar */
.content {
    margin-top: 70px; 
    margin-left: 260px; 
    padding: 15px;
    min-height: calc(100vh - 70px);
}

/* Map styling */
#map {
    width: 100%;
    height: 60vh;
    border-radius: 10px;
}

/* Status panel */
.status-panel {
    background: #C4D5C5;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 500;
}

/* Complete Pickup button */
button {
    padding: 10px 15px;
    background: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: 600;
}
button:hover { background: #45a049; }

/* Mobile responsiveness */
@media (max-width: 768px) {
    .content {
        margin-left: 0; /* sidebar overlays on mobile */
    }
}
</style>
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<div class="content">
    <div class="status-panel">
        <p><strong>Status:</strong> <span id="status">Waiting for GPS...</span></p>
        <p><strong>ETA:</strong> <span id="eta">Calculating...</span></p>
    </div>

    <div id="map"></div>

    <button id="completeBtn">Complete Pickup</button>
</div>

<script>
let crewId = <?= $crew_id ?>;
let map, marker, directionsService, directionsRenderer;
let statusSpan = document.getElementById("status");
let etaSpan = document.getElementById("eta");

// Initialize Google Map
function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 14.165, lng: 120.938 }, // default center
        zoom: 14,
    });

    marker = new google.maps.Marker({
        map: map,
        title: "Truck Location",
    });

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({ map: map });
}

// Send GPS to server
function sendGPS(lat, lng) {
    statusSpan.innerText = "Sending GPS...";
    let form = new FormData();
    form.append("crew_id", crewId);
    form.append("lat", lat);
    form.append("lng", lng);

    fetch("update_location.php", { method: "POST", body: form })
    .then(res => res.json())
    .then(data => {
        statusSpan.innerText = "GPS updated";

        if(data.nextPickup){
            drawRoute(lat, lng, data.nextPickup.lat, data.nextPickup.lng);
        } else {
            etaSpan.innerText = "No upcoming pickup";
        }
    })
    .catch(err => {
        statusSpan.innerText = "Error sending GPS";
        console.error(err);
    });
}

// Draw route and calculate ETA
function drawRoute(fromLat, fromLng, toLat, toLng){
    let request = {
        origin: { lat: fromLat, lng: fromLng },
        destination: { lat: toLat, lng: toLng },
        travelMode: 'DRIVING'
    };
    directionsService.route(request, function(result, status){
        if(status === 'OK'){
            directionsRenderer.setDirections(result);
            etaSpan.innerText = result.routes[0].legs[0].duration.text;
        } else {
            etaSpan.innerText = "ETA unavailable";
        }
    });
}

// Live GPS tracking from phone
function success(pos){
    let lat = pos.coords.latitude;
    let lng = pos.coords.longitude;

    marker.setPosition({lat, lng});
    map.setCenter({lat, lng});
    sendGPS(lat, lng);
}

function error(err){
    console.warn("GPS error:", err);
    statusSpan.innerText = "GPS error";
}

// Watch position continuously
navigator.geolocation.watchPosition(success, error, { enableHighAccuracy:true, maximumAge:0 });

// Complete Pickup button
document.getElementById("completeBtn").onclick = () => {
    fetch("complete_pickup.php?crew_id="+crewId)
    .then(r=>r.text())
    .then(alert);
};
</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBgvwpGJg-Qo5Wx7Qh_8W5IJtNE5agWOqc&callback=initMap" async defer></script>
</body>
</html>
