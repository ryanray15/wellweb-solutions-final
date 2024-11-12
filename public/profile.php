<?php
session_start(); // Start the session

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html'); // Redirect to login page if not logged in
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
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f4f6;
            /* Light gray background */
        }

        .transparent-bg {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);

        }

        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: bold;
            color: #3b82f6;
        }

        .profile-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .profile-info div {
            flex: 1;
            margin-right: 1rem;
        }

        .profile-info div:last-child {
            margin-right: 0;
        }

        .btn {
            background-color: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.2s;
        }

        .btn:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }

        .icon {
            margin-right: 0.5rem;
            color: #3b82f6;
        }

        .label {
            font-weight: bold;
            color: #4b5563;
            /* Darker gray for labels */
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <nav class="w-full mt-0 transparent-bg shadow-md p-2 fixed top-0 left-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <span class="text-blue-500 text-2xl font-bold">WELL WEB SOLUTIONS</span>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-blue-600 focus:outline-none">
                    <i class="fas fa-user-circle fa-2x"></i>
                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                    <?php if ($user_role === 'doctor') : ?>
                        <a href="onboarding.php" id="onboarding" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Stripe Connect</a>
                    <?php endif; ?>
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-24 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-8">Profile</h1>

        <!-- User Profile Section -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">User Information</h2>
            </div>
            <div class="profile-info">
                <div>
                    <p class="label"><i class="fas fa-user icon"></i> First Name:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($userInfo['first_name']); ?></p>
                    <p class="label"><i class="fas fa-user icon"></i> Middle Initial:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($userInfo['middle_initial']); ?></p>
                    <p class="label"><i class="fas fa-user icon"></i> Last Name:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($userInfo['last_name']); ?></p>
                    <p class="label"><i class="fas fa-envelope icon"></i> Email:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($userInfo['email']); ?></p>
                </div>
                <div>
                    <p class="label"><i class="fas fa-phone icon"></i> Contact Number:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($userInfo['contact_number']); ?></p>
                    <p class="label"><i class="fas fa-map-marker-alt icon"></i> Address:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars($userInfo['address']); ?></p>
                    <p class="label"><i class="fas fa-venus-mars icon"></i> Gender:</p>
                    <p class="text-gray-700 mb-3"><?php echo htmlspecialchars(ucfirst($userInfo['gender'])); ?></p>
                </div>
            </div>

            <?php if ($user_role === 'doctor' && !empty($specializations)) : ?>
                <p class="label"><i class="fas fa-stethoscope icon"></i> Specializations:</p>
                <p class="text-gray-700 mb-3"><?php echo implode(', ', $specializations); ?></p>
            <?php endif; ?>

            <div class="flex space-x-4 mt-4">
                <a href="edit_profile.php" class="btn">Edit Profile</a>
                <a href="reset_password.php" class="btn">Reset Password</a>
            </div>
        </div>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>