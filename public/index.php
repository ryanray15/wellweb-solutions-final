<?php
session_start(); // Start the session to access session variables
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-white">
     <!-- Test Tailwind CSS
    <div class="bg-red-500 p-4 text-green-400">
        This is a test div to verify Tailwind CSS is working.
    </div> -->
    <nav class="bg-green-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/icon.ico" alt="Icon" class="h-10 w-10 mr-4">
                <span class="text-white text-2xl font-bold">Wellweb</span>
            </div>
            <div class="space-x-4">
                <button onclick="window.location.href='login.html'" class="bg-white text-green-600 py-2 px-4 rounded hover:bg-red-600 hover:text-white transition duration-300">Login</button>
                <button onclick="window.location.href='register.html'" class="bg-white text-green-600 py-2 px-4 rounded hover:bg-red-600 hover:text-white transition duration-300">Register</button>
            </div>
        </div>
    </nav>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const profileDropdown = document.getElementById('profileDropdown');
            const dropdownMenu = document.getElementById('dropdownMenu');

            if (profileDropdown) {
                profileDropdown.addEventListener('click', () => {
                    dropdownMenu.classList.toggle('hidden');
                });
            }

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
                                sessionStorage.removeItem('user_id');
                                sessionStorage.removeItem('role');
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