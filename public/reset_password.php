<?php
session_start();

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html'); // Redirect to login page if not logged in
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<style>
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

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="w-full mt-0 transparent-bg shadow-md p-2 fixed top-0 left-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <a href="index.php"><span class="text-blue-500 text-2xl font-bold">WELL WEB SOLUTIONS</span></a>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-gray-700 py-2 px-4 rounded hover:border-2 border-blue-400 hover:text-blue-500 transition duration-300 ">

                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-28 max-w-md p-8 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-blue-600 mb-6 text-center">Reset Password</h1>

        <form id="resetPasswordForm">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="new_password">New Password</label>
                <input type="password" id="new_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full transition duration-200">Reset Password</button>
        </form>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/reset_password.js"></script>
</body>

</html>