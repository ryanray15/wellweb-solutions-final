<?php
session_start();

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login page if not logged in
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-10 max-w-md p-8 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-green-600 mb-6 text-center">Reset Password</h1>

        <form id="resetPasswordForm">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">New Password</label>
                <input type="password" id="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <button type="submit" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded w-full transition duration-200">Reset Password</button>
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

            // Handle Reset Password Form Submission
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            resetPasswordForm.addEventListener('submit', function(event) {
                event.preventDefault();

                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (newPassword !== confirmPassword) {
                    alert("Passwords do not match!");
                    return;
                }

                // Fetch the password update endpoint
                fetch('/api/reset_password.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            user_id: <?php echo json_encode($user_id); ?>,
                            new_password: newPassword
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            alert('Password reset successfully!');
                            window.location.href = '/dashboard.php'; // Redirect to dashboard on success
                        } else {
                            alert('Password reset failed: ' + data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });
        });
    </script>
</body>

</html>