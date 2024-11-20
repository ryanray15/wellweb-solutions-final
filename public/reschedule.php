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
    <title>Reschedule Appointment</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('img/bg_doctor.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .transparent-bg {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 0.5rem;
        }

        .form-label {
            font-weight: 600;
            color: #4A5568;
        }

        .form-input {
            border: 1px solid #CBD5E0;
            border-radius: 0.375rem;
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: #48BB78;
            outline: none;
        }

        .flex-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .form-container {
            flex: 1;
            margin-right: 20px;
        }

        .calendar-container {
            flex: 1;
        }

        #appointments-container .appointment-slot {
            background-color: #4F46E5;
            color: white;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            display: block;
            width: 100%;
            text-align: center;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="w-full mt-0 transparent-bg shadow-md p-2 fixed top-0 left-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <span class="text-blue-500 text-2xl font-bold">WELL WEB SOLUTIONS</span>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-blue-600 focus:outline-none">
                    <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span>
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

    <div class="container mx-auto mt-28 mb-8 p-8 bg-white rounded-lg shadow-lg">
        <div class="flex-container">
            <!-- Doctor and Consultation Type Selection -->
            <div class="form-container">
                <!-- Choose Doctor -->
                <div id="choose-doctor-container" class="mb-6">
                    <label class="block form-label mb-2" for="doctor_id">Choose Doctor</label>
                    <select class="form-input" id="doctor_id">
                        <!-- Populate with options dynamically -->
                    </select>
                </div>

                <!-- Consultation Type -->
                <div class="mb-6">
                    <label class="block form-label mb-2" for="consultation_type">Consultation Type</label>
                    <select class="form-input" id="consultation_type">
                        <option value="online">Online Consultation</option>
                        <option value="physical">Physical Consultation</option>
                    </select>
                </div>

                <!-- Draggable Events for Appointments -->
                <div id="external-events" class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                    <h3 class="text-lg font-semibold text-gray-700">Appointments</h3>
                    <div id="appointments-container">
                        <!-- Populate with draggable appointments dynamically -->
                    </div>
                </div>
            </div>

            <!-- Calendar Display -->
            <div class="calendar-container flex items-center justify-center">
                <div id="calendar" class="mt-8 w-full"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/reschedule.js"></script>
</body>

</html>