document.addEventListener("DOMContentLoaded", function () {
  const map = L.map("map").setView([14.5995, 120.9842], 13); // Default map view

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // Cache to store geocoded coordinates
  const geocodeCache = {};

  // Geocode function with caching
  function geocodeAddress(address, callback) {
    if (geocodeCache[address]) {
      callback(geocodeCache[address].lat, geocodeCache[address].lon);
      return;
    }

    const encodedAddress = encodeURIComponent(address);
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodedAddress}`;

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data && data.length > 0) {
          const lat = parseFloat(data[0].lat);
          const lon = parseFloat(data[0].lon);
          geocodeCache[address] = { lat, lon };
          callback(lat, lon); // Return coordinates through callback
        } else {
          console.error("No coordinates found for address:", address);
        }
      })
      .catch((error) => console.error("Geocoding error:", error));
  }

  // Initialize routing control (to be used later)
  let routingControl;

  // Function to add routing from user's location to doctor's location
  function createRoute(startLat, startLon, destLat, destLon) {
    // Remove existing route if present
    if (routingControl) {
      map.removeControl(routingControl);
    }

    // Create new route
    routingControl = L.Routing.control({
      waypoints: [L.latLng(startLat, startLon), L.latLng(destLat, destLon)],
      routeWhileDragging: true,
      showAlternatives: false,
      geocoder: L.Control.Geocoder.nominatim(), // Optional: for geocoding
    }).addTo(map);
  }

  // Function to fetch and display doctors on the map
  function fetchAndDisplayDoctors(userLat, userLon) {
    fetch("/api/get_doctors.php")
      .then((response) => response.json())
      .then((doctors) => {
        doctors.forEach((doctor) => {
          geocodeAddress(doctor.address, (lat, lon) => {
            const marker = L.marker([lat, lon]).addTo(map);
            marker.bindPopup(
              `<strong>Dr. ${doctor.name}</strong><br>${doctor.address}<br><button onclick="startRoute(${lat}, ${lon})" class="underline">Get Directions</button>`
            );
          });
        });

        if (userLat && userLon) {
          map.setView([userLat, userLon], 13);
        }
      })
      .catch((error) => console.error("Error fetching doctors:", error));
  }

  // Function to handle the route button click
  window.startRoute = function (destLat, destLon) {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const userLat = position.coords.latitude;
          const userLon = position.coords.longitude;
          createRoute(userLat, userLon, destLat, destLon);
        },
        () => {
          alert("Could not retrieve your location.");
        }
      );
    } else {
      alert("Geolocation is not supported by your browser.");
    }
  };

  // Display user’s location
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const userLat = position.coords.latitude;
        const userLon = position.coords.longitude;
        const userMarker = L.marker([userLat, userLon], {
          color: "blue",
        }).addTo(map);
        userMarker.bindPopup("Your Location").openPopup();

        fetchAndDisplayDoctors(userLat, userLon); // Fetch and display doctors
      },
      () => {
        alert("Could not retrieve your location. Showing default map.");
        fetchAndDisplayDoctors(); // Fetch and display doctors without user location
      }
    );
  } else {
    alert("Geolocation is not supported by your browser.");
    fetchAndDisplayDoctors(); // Fetch and display doctors without user location
  }
});
