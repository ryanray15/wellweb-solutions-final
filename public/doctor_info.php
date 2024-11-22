<?php
session_start(); // Start the session
require_once '../config/database.php';

$db = include '../config/database.php';

$doctor_id = $_GET['doctor_id'] ?? null;
$user_role = $_SESSION['role'];

if ($doctor_id) {
    // Updated SQL Query to fetch all specializations
    $query = "SELECT users.user_id, CONCAT(users.first_name, ' ', users.middle_initial, ' ', users.last_name) as name, GROUP_CONCAT(specializations.name SEPARATOR ', ') as specializations, address 
              FROM users 
              JOIN doctor_specializations ON users.user_id = doctor_specializations.doctor_id 
              JOIN specializations ON doctor_specializations.specialization_id = specializations.id
              WHERE users.user_id = ?
              GROUP BY users.user_id"; // Grouping by user_id to concatenate specializations

    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    if ($doctor) {
?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Doctor Profile - Wellweb</title>
            <link href="assets/css/tailwind.css" rel="stylesheet">
            <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
            <style>
                #map {
                    z-index: 1;
                }

                .transparent-bg {
                    background-color: rgba(255, 255, 255, 0.7);
                    /* White with 70% opacity */
                    backdrop-filter: blur(10px);
                    /* Optional: adds a blur effect to the background */

                }

                /* Notification Dropdown Styles */
                #dropdownMenu {
                    max-width: 300px;
                    /* Set a max-width that fits your design */
                    white-space: normal;
                    /* Allows text to wrap */
                    word-wrap: break-word;
                    /* Ensures long words break and wrap to the next line */
                    padding: 10px;
                    /* Add some padding for a better look */
                }

                #dropdownMenu a {
                    padding: 8px 12px;
                    /* Adjust padding inside each notification */
                    display: block;
                    color: #333;
                    text-decoration: none;
                    border-bottom: 1px solid #ddd;
                    /* Optional: Add a border between notifications */
                }

                #dropdownMenu a:last-child {
                    border-bottom: none;
                    /* Remove border from the last notification */
                }

                #dropdownMenu a:hover {
                    background-color: #f0f0f0;
                    /* Highlight the notification on hover */
                }
            </style>
        </head>

        <body class="bg-gray-100">

            <!-- Navigation Bar -->
            <nav class="w-full mt-0 transparent-bg shadow-md p-1 fixed top-0 left-0 z-50">
                <div class="container mx-auto flex justify-between items-center">
                    <div class="flex items-center">
                        <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                        <a href="index.php"><span class="text-blue-400 text-2xl font-bold">WELL WEB SOLUTIONS</span></a>
                    </div>
                    <div class="relative">
                        <button id="profileDropdown" class="text-white focus:outline-none">
                            <i class="fas fa-user-circle fa-2x"></i>
                        </button>
                        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                            <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                            <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                            <?php if ($user_role === 'doctor' || $user_role === 'patient') : ?>
                                <a href="appointment_history.php" id="appointment_history" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Appointment History</a>
                            <?php endif; ?>
                            <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- Main Content -->
            <div class="container mx-auto mt-28 px-6 py-8">
                <h1 class="text-4xl font-bold text-blue-600 mb-8"><?php echo htmlspecialchars($doctor['name']); ?></h1>

                <!-- Doctor Information Section -->
                <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                    <h2 class="text-2xl font-bold mb-4 text-blue-700">Doctor Information</h2>
                    <p class="text-gray-700 mb-3"><strong>Specializations:</strong> <?php echo htmlspecialchars($doctor['specializations']); ?></p>
                    <p class="text-gray-700 mb-3"><strong>Clinic Address:</strong> <?php echo htmlspecialchars($doctor['address']); ?></p>
                </div>

                <!-- OpenStreetMap -->
                <div id="map" class="rounded-lg shadow-md" style="height: 600px; width: 100%;"></div>
            </div>

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('DOM fully loaded and parsed');

                    // Initialize the map with a neutral view, e.g., centered on the Philippines
                    var map = L.map('map').setView([12.8797, 121.7740], 6); // Centered on the Philippines
                    console.log('Map initialized');

                    // Add OSM tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 18,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).addTo(map);
                    console.log('Tile layer added to the map');

                    // Geocode the address to get the coordinates
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=<?php echo urlencode($doctor['address']); ?>`)
                        .then(response => response.json())
                        .then(data => {
                            console.log('Geocoding data:', data);

                            if (data.length > 0) {
                                var lat = data[0].lat; // Using the first result from the geocoding data
                                var lon = data[0].lon;
                                map.setView([lat, lon], 15);

                                L.marker([lat, lon]).addTo(map)
                                    .bindPopup('<?php echo htmlspecialchars($doctor['address']); ?>')
                                    .openPopup();
                                console.log('Marker added to the map');
                            } else {
                                alert('Address not found.');
                                console.log('No geocoding results found');
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching coordinates:', error);
                            alert('Failed to retrieve the coordinates.');
                        });
                });
            </script>
            <script src="assets/js/utils.js"></script>
            <script src="assets/js/common.js"></script>
        </body>

        </html>
<?php
    } else {
        echo "Doctor not found.";
    }
} else {
    echo "No doctor selected.";
}
?>