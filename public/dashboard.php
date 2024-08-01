<?php
session_start(); // Start the session

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Get user ID and role from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

    <!-- Navigation Bar -->
    <nav class="bg-green-600">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/icon.ico" alt="Icon" class="h-10 w-10 mr-4">
                <a href="/index.php" class="text-white text-2xl font-bold">Wellweb</a>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-white focus:outline-none">
                    <i class="fas fa-user-circle fa-2x"></i>
                </button>
                <div id="dropdownMenu"
                    class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="settings.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Settings</a>
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-10">
        <h1 class="text-3xl font-bold text-green-600 mb-8">Dashboard</h1>

        <!-- Schedule Appointment Section -->
        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4 text-green-700">Schedule Appointment</h2>
            <a href="schedule.html" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200">Schedule an Appointment</a>
        </div>

        <!-- Reschedule Appointment Section -->
        <div class="mb-8 p-6 bg-white rounded-lg shadow-md" id="rescheduleSection">
            <h2 class="text-2xl font-bold mb-4 text-green-700">Reschedule Appointment</h2>
            <p id="rescheduleMessage" class="text-gray-700 mb-3">No appointments scheduled.</p>
            <a href="reschedule.html" id="rescheduleButton" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200 hidden">Reschedule Appointment</a>
        </div>

        <!-- Cancel Appointment Section -->
        <div class="mb-8 p-6 bg-white rounded-lg shadow-md" id="cancelSection">
            <h2 class="text-2xl font-bold mb-4 text-green-700">Cancel Appointment</h2>
            <p id="cancelMessage" class="text-gray-700 mb-3">No appointments scheduled.</p>
            <a href="cancel.html" id="cancelButton" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200 hidden">Cancel Appointment</a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Profile Dropdown
            const profileDropdown = document.getElementById('profileDropdown');
            const dropdownMenu = document.getElementById('dropdownMenu');

            profileDropdown.addEventListener('click', () => {
                dropdownMenu.classList.toggle('hidden');
            });

            // Logout functionality
            const logoutButton = document.getElementById('logout');
            if (logoutButton) {
                logoutButton.addEventListener('click', () => {
                    fetch('/api/logout.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        }
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status) {
                                // Clear client-side session data
                                sessionStorage.removeItem('user_id');
                                sessionStorage.removeItem('role');
                                // Redirect to index.php or login.html
                                window.location.href = '/index.php';
                            } else {
                                alert('Failed to log out. Please try again.');
                            }
                        })
                    .catch(error => console.error('Error:', error));
                });
            }
            
            // Fetch appointments and update the dashboard
            const patient_id = <?php echo json_encode($user_id); ?>; // Get patient ID from PHP session

            // Fetch appointments
            fetch(`http://doctor-appointment.local/api/get_appointments.php?patient_id=${patient_id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        document.getElementById('rescheduleMessage').textContent = 'You have scheduled appointments.';
                        document.getElementById('cancelMessage').textContent = 'You have scheduled appointments.';

                        document.getElementById('rescheduleButton').classList.remove('hidden');
                        document.getElementById('cancelButton').classList.remove('hidden');
                    } else {
                        // If no appointments, display schedule button
                        document.getElementById('rescheduleMessage').textContent = 'No appointments scheduled.';
                        document.getElementById('cancelMessage').textContent = 'No appointments scheduled.';
                    }
                })
            .catch(error => console.error('Error fetching appointments:', error));
        });
    </script>
</body>
</html>
