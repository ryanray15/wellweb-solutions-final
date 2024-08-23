<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}

// Get user ID and role from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Fetch user information from the database
require_once '../config/database.php';
$db = include '../config/database.php';
$query = $db->prepare("SELECT first_name, middle_initial, last_name, email FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$userInfo = $query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cancel Appointment</title>
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
                    <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['middle_initial'] . ' ' . $userInfo['last_name']); ?></span> <!-- Display user's full name -->
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
            Cancel Appointment
        </h1>
        <form id="cancelForm" class="w-full">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="appointment_id">Select Appointment</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="appointment_id">
                    <!-- Example Options -->
                    <!-- <option value="1">Appointment with Dr. John Doe on 2024-08-10 at 10:00 AM</option>
                    <option value="2">Appointment with Dr. Jane Smith on 2024-08-11 at 11:00 AM</option> -->
                </select>
            </div>
            <button class="w-full bg-red-600 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200" type="submit">
                Cancel Appointment
            </button>
        </form>
    </div>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/cancel.js"></script>
</body>

</html>