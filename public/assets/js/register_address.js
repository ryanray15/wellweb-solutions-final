document.addEventListener("DOMContentLoaded", function () {
  // Initialize the map and set the view to a default location
  const map = L.map("map").setView([14.5995, 120.9842], 13); // Coordinates for Manila, Philippines as default

  // Add OpenStreetMap tiles
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // Add a marker on the default location
  const marker = L.marker([14.5995, 120.9842]).addTo(map);

  // Reference to the address input field
  const addressField = document.getElementById("address");

  // Optional: Set the map to user’s current location if available
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      map.setView([lat, lon], 13); // Center map on user's location
      marker.setLatLng([lat, lon]); // Move marker to user's location
    });
  }

  // Function to perform reverse geocoding
  function fetchAddress(lat, lon) {
    const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`;

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data && data.display_name) {
          // Set the fetched address or place name in the address input field
          addressField.value = data.display_name;
        } else {
          addressField.value = `Lat: ${lat}, Lon: ${lon}`;
        }
      })
      .catch((error) => {
        console.error("Error fetching address:", error);
        addressField.value = `Lat: ${lat}, Lon: ${lon}`;
      });
  }

  // Map click event to get the latitude, longitude and fetch address
  map.on("click", function (e) {
    const lat = e.latlng.lat;
    const lon = e.latlng.lng;
    marker.setLatLng([lat, lon]); // Move marker to the clicked location
    fetchAddress(lat, lon); // Fetch and display the address
  });

  // Function to perform geocoding based on user-input address
  function geocodeAddress(address) {
    const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
      address
    )}`;

    fetch(url)
      .then((response) => response.json())
      .then((data) => {
        if (data && data[0]) {
          const lat = parseFloat(data[0].lat);
          const lon = parseFloat(data[0].lon);
          map.setView([lat, lon], 13); // Update map view to the found location
          marker.setLatLng([lat, lon]); // Move marker to the found location
        } else {
          alert("Location not found. Please enter a valid address.");
        }
      })
      .catch((error) => console.error("Error geocoding address:", error));
  }

  // Listen for changes in the address input field
  addressField.addEventListener("change", function () {
    const address = addressField.value;
    geocodeAddress(address); // Geocode and update map on address change
  });
});
