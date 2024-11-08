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
            /* Gray-700 */
        }

        .form-input {
            border: 1px solid #CBD5E0;
            /* Gray-300 */
            border-radius: 0.375rem;
            /* Rounded */
            padding: 0.5rem 0.75rem;
            width: 100%;
            transition: border-color 0.2s;
        }

        .form-input:focus {
            border-color: #48BB78;
            /* Green-500 */
            outline: none;
        }

        .btn-primary {
            background-color: #48BB78;
            /* Green-500 */
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }

        .btn-primary:hover {
            background-color: #F56565;
            /* Red-500 */
        }

        .flex-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .form-container {
            flex: 1;
            margin-right: 20px;
            /* Space between form and calendar */
        }

        .calendar-container {
            flex: 1;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="container mx-auto mt-10 mb-8 transparent-bg p-4 shadow-md">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <span class="text-blue-500 text-2xl font-bold ml-2">WELL WEB SOLUTIONS</span>
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

    <div class="container mx-auto mb-8 p-8 bg-white rounded-lg shadow-lg">
        <div class="flex-container">
            <div class="form-container">
                <form id="rescheduleForm" class="w-full mb-8">
                    <div class="mb-6">
                        <label class="block form-label mb-2" for="appointment_id">Select Appointment</label>
                        <select class="form-input" id="appointment_id">
                            <!-- Populate this with options using JavaScript -->
                        </select>
                    </div>
                    <div class="flex justify-between mb-6">
                        <div class="w-full mr-2">
                            <label class="block form-label mb-2" for="date">New Date</label>
                            <input class="form-input" id="date" type="date" />
                        </div>
                        <div class="w-full ml-2">
                            <label class="block form-label mb-2" for="time">New Time</label>
                            <input class="form-input" id="time" type="time" />
                        </div>
                    </div>
                    <button class="btn-primary w-full" type="submit">Reschedule</button>
                </form>
            </div>
            <div class="calendar-container flex items-center justify-center">

                <div id="calendar" class="mt-8"></div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="assets/js/utils.js"></script>
        <script src="assets/js/common.js"></script>
        <script src="assets/js/reschedule.js"></script>
    </div>
</body>

</html>