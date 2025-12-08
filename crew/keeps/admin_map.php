<div id="map" style="height:500px;"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<script>
let map = L.map('map').setView([13.94,121.17], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

let truckMarker = null;

function loadGPS() {
    fetch("get_truck_location.php")
    .then(r=>r.json())
    .then(data => {
        let lat = data.lat;
        let lng = data.lng;

        if (!truckMarker) {
            truckMarker = L.marker([lat,lng]).addTo(map);
        } else {
            truckMarker.setLatLng([lat,lng]);
        }
    });
}

setInterval(loadGPS, 3000);
</script>
