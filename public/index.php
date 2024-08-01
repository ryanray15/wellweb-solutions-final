<?php
session_start(); // Start the session

// Check if the user is logged in
$loggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Wellweb</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-white">
    <nav class="bg-green-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/icon.ico" alt="Icon" class="h-10 w-10 mr-4">
                <span class="text-white text-2xl font-bold">Wellweb</span>
            </div>
            <div>
                <?php if ($loggedIn): ?>
                    <!-- Show profile dropdown for logged-in users -->
                    <div class="relative">
                        <button id="profileDropdown" class="text-white focus:outline-none">
                            <i class="fas fa-user-circle fa-2x"></i>
                        </button>
                        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                            <a href="dashboard.html" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                            <a href="settings.html" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Settings</a>
                            <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Show login/register buttons for guests -->
                    <button onclick="window.location.href='login.html'" class="bg-white text-green-600 py-2 px-4 rounded hover:bg-red-600 hover:text-white transition duration-300">Login</button>
                    <button onclick="window.location.href='register.html'" class="bg-white text-green-600 py-2 px-4 rounded hover:bg-red-600 hover:text-white transition duration-300">Register</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-10">
        <h1 class="text-3xl font-bold mb-5">Welcome to the Doctor Appointment System</h1>
        <p>Your convenient way to schedule medical appointments with ease.</p>
        <!-- Additional homepage content -->
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
