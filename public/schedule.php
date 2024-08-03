<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule Appointment</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-green-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/icon.ico" alt="Icon" class="h-10 w-10 mr-4">
                <a href="/index.php" class="text-white text-2xl font-bold">Wellweb</a>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-white focus:outline-none">
                    <i class="fas fa-user-circle fa-2x"></i>
                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                    <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mx-auto mt-10 max-w-2xl p-8 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-green-600 mb-8 text-center">
            Schedule Appointment
        </h1>
        <form id="scheduleForm" class="w-full">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="doctor_id">Select Doctor</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="doctor_id">
                    <!-- Example Options -->
                    <!-- <option value="1">Dr. John Doe</option>
                    <option value="2">Dr. Jane Smith</option> -->
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="service_id">Select Service</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="service_id">
                    <!-- Example Options -->
                    <!-- <option value="1">General Check-up</option>
                    <option value="2">Pediatric Consultation</option> -->
                </select>
            </div>
            <div class="flex justify-between mb-6">
                <div class="w-full mr-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date">Choose Date</label>
                    <input class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="date" type="date" />
                </div>
                <div class="w-full ml-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="time">Choose Time</label>
                    <input class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="time" type="time" />
                </div>
            </div>
            <button class="w-full bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200" type="submit">
                Schedule Appointment
            </button>
        </form>
    </div>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
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
        });
    </script>
</body>

</html>