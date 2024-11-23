<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.html');
    exit();
}

require_once '../config/database.php';
$db = include '../config/database.php';

// Get the logged-in user's ID and role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

try {
    $appointments = [];

    if ($user_role === 'patient') {
        // Fetch appointments for the patient
        $query = $db->prepare("
            SELECT a.appointment_id, a.date, a.start_time, a.end_time, a.status, 
                   CONCAT(u.first_name, ' ', u.last_name) AS doctor_name, s.name AS service_name
            FROM appointments a
            JOIN users u ON a.doctor_id = u.user_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.patient_id = ?
            AND a.status IN ('completed', 'no show', 'canceled', 'rescheduled')
            ORDER BY a.date DESC, a.start_time DESC
        ");
        $query->bind_param('i', $user_id);
    } elseif ($user_role === 'doctor') {
        // Fetch appointments for the doctor
        $query = $db->prepare("
            SELECT a.appointment_id, a.date, a.start_time, a.end_time, a.status, 
                   CONCAT(u.first_name, ' ', u.last_name) AS patient_name, s.name AS service_name
            FROM appointments a
            JOIN users u ON a.patient_id = u.user_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.doctor_id = ?
            AND a.status IN ('completed', 'no show', 'canceled')
            ORDER BY a.date DESC, a.start_time DESC
        ");
        $query->bind_param('i', $user_id);
    } else {
        // Redirect unauthorized users
        header('Location: unauthorized.html');
        exit();
    }

    $query->execute();
    $result = $query->get_result();

    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
} catch (Exception $e) {
    error_log("Error fetching appointment history: " . $e->getMessage());
    $appointments = [];
}

// Fetch user information from the database
require_once '../config/database.php';
$db = include '../config/database.php';
$query = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$userInfo = $query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment History</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <style>
        body {
            background-image: url('img/bg_doctor.jpg');
            /* Update with your image path */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        .transparent-bg {
            background-color: rgba(255, 255, 255, 0.7);
            /* White with 70% opacity */
            backdrop-filter: blur(10px);
            /* Optional: adds a blur effect to the background */
            border-radius: 0.5rem;
            /* Optional: adds rounded corners */
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
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="container mx-auto mt-4 transparent-bg shadow-md p-2">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <a href="index.php"><span class="text-blue-400 text-2xl font-bold ">WELL WEB SOLUTIONS</span></a>
            </div>
            <div>
                <div class="relative">
                    <button id="profileDropdown" class="text-gray-700 py-2 px-4 rounded-full hover:border-2 border-blue-400 hover:text-blue-500 transition duration-300 ">
                        <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span> <!-- Display user's name -->
                    </button>

                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                        <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                        <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                        <?php if ($user_role === 'doctor') : ?>
                            <a href="onboarding.php" id="onboarding" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Stripe Connect</a>
                        <?php endif; ?>
                        <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-10 p-6 max-w-4xl bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-6 text-center">Appointment History</h1>

        <?php if (empty($appointments)) : ?>
            <p class="text-gray-700 text-center">No past appointments found.</p>
        <?php else : ?>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="border border-gray-300 px-4 py-2 text-left">Date</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Time</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">
                            <?= $user_role === 'patient' ? 'Doctor' : 'Patient' ?>
                        </th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Service</th>
                        <th class="border border-gray-300 px-4 py-2 text-left">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appointment) : ?>
                        <tr class="hover:bg-gray-100">
                            <td class="border border-gray-300 px-4 py-2">
                                <?= htmlspecialchars($appointment['date']) ?>
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                <?php
                                // Convert start and end times to 12-hour format with AM/PM
                                $startTime = DateTime::createFromFormat('H:i:s', $appointment['start_time'])->format('h:i A');
                                $endTime = DateTime::createFromFormat('H:i:s', $appointment['end_time'])->format('h:i A');
                                echo htmlspecialchars($startTime . ' - ' . $endTime);
                                ?>
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                <?= htmlspecialchars(
                                    $user_role === 'patient' ? 'Dr. ' . $appointment['doctor_name'] : $appointment['patient_name']
                                ) ?>
                            </td>
                            <td class="border border-gray-300 px-4 py-2">
                                <?= htmlspecialchars($appointment['service_name']) ?>
                            </td>
                            <td class="border border-gray-300 px-4 py-2 capitalize">
                                <?= htmlspecialchars($appointment['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>