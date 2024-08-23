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

// Fetch user information from the database
require_once '../config/database.php';
$db = include '../config/database.php';
$query = $db->prepare("SELECT first_name, middle_initial, last_name, email, contact_number, address, gender FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$userInfo = $query->get_result()->fetch_assoc();

// Fetch specializations if the user is a doctor
$specializations = [];
if ($user_role === 'doctor') {
    $specQuery = $db->prepare("SELECT s.name FROM doctor_specializations ds JOIN specializations s ON ds.specialization_id = s.id WHERE ds.doctor_id = ?");
    $specQuery->bind_param("i", $user_id);
    $specQuery->execute();
    $result = $specQuery->get_result();
    while ($row = $result->fetch_assoc()) {
        $specializations[] = $row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Wellweb</title>
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
    <div class="container mx-auto mt-10 px-6 py-8">
        <h1 class="text-4xl font-bold text-green-600 mb-8">Profile</h1>

        <!-- User Profile Section -->
        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
            <h2 class="text-2xl font-bold mb-4 text-green-700">User Information</h2>
            <p class="text-gray-700 mb-3"><strong>First Name:</strong> <?php echo htmlspecialchars($userInfo['first_name']); ?></p>
            <p class="text-gray-700 mb-3"><strong>Middle Initial:</strong> <?php echo htmlspecialchars($userInfo['middle_initial']); ?></p>
            <p class="text-gray-700 mb-3"><strong>Last Name:</strong> <?php echo htmlspecialchars($userInfo['last_name']); ?></p>
            <p class="text-gray-700 mb-3"><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></p>
            <p class="text-gray-700 mb-3"><strong>Contact Number:</strong> <?php echo htmlspecialchars($userInfo['contact_number']); ?></p>
            <p class="text-gray-700 mb-3"><strong>Address:</strong> <?php echo htmlspecialchars($userInfo['address']); ?></p>
            <p class="text-gray-700 mb-3"><strong>Gender:</strong> <?php echo htmlspecialchars(ucfirst($userInfo['gender'])); ?></p> <!-- Display gender -->

            <?php if ($user_role === 'doctor' && !empty($specializations)) : ?>
                <p class="text-gray-700 mb-3"><strong>Specializations:</strong> <?php echo implode(', ', $specializations); ?></p>
            <?php endif; ?>

            <a href="edit_profile.php" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200">Edit Profile</a>
            <a href="reset_password.php" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200">Reset Password</a>
        </div>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>