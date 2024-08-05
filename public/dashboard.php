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
$query = $db->prepare("SELECT * FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$userInfo = $query->get_result()->fetch_assoc();

// Function to fetch appointments for doctors
function fetchDoctorAppointments($db, $user_id)
{
    $query = $db->prepare(
        "SELECT a.*, p.name as patient_name FROM appointments a
         JOIN users p ON a.patient_id = p.user_id
         WHERE a.doctor_id = ?"
    );
    $query->bind_param("i", $user_id);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch doctor appointments if the user is a doctor
$appointments = [];
if ($user_role === 'doctor') {
    $appointments = fetchDoctorAppointments($db, $user_id);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <!-- Correct FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
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
                <span class="text-white mr-2"><?php echo htmlspecialchars($userInfo['name']); ?></span>
                <button id="profileDropdown" class="text-white focus:outline-none">
                    <i class="fas fa-user-circle fa-2x"></i>
                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-10">

        <!-- Doctor Dashboard -->
        <?php if ($user_role === 'doctor') : ?>
            <h1 class="text-3xl font-bold text-green-600 mb-8">Doctor Dashboard</h1>

            <!-- Display Appointments -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Your Appointments</h2>
                <?php if (count($appointments) > 0) : ?>
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="border-b border-gray-200 px-4 py-2">Patient Name</th>
                                <th class="border-b border-gray-200 px-4 py-2">Date</th>
                                <th class="border-b border-gray-200 px-4 py-2">Time</th>
                                <th class="border-b border-gray-200 px-4 py-2">Status</th>
                                <th class="border-b border-gray-200 px-4 py-2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment) : ?>
                                <tr>
                                    <td class="border-b border-gray-200 px-4 py-2">
                                        <?php echo htmlspecialchars($appointment['patient_name'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="border-b border-gray-200 px-4 py-2">
                                        <?php echo htmlspecialchars($appointment['date']); ?>
                                    </td>
                                    <td class="border-b border-gray-200 px-4 py-2">
                                        <?php echo htmlspecialchars($appointment['time']); ?>
                                    </td>
                                    <td class="border-b border-gray-200 px-4 py-2">
                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                    </td>
                                    <td class="border-b border-gray-200 px-4 py-2">
                                        <a href="accept_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded">Accept</a>
                                        <a href="reschedule_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="bg-yellow-600 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded">Reschedule</a>
                                        <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded">Cancel</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p class="text-gray-700">No appointments available.</p>
                <?php endif; ?>
            </div>

            <!-- Doctor Availability -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Set Your Availability</h2>
                <div id="calendar"></div>
            </div>
        <?php endif; ?>

        <!-- Patient Dashboard -->
        <?php if ($user_role === 'patient') : ?>
            <h1 class="text-3xl font-bold text-green-600 mb-8">Patient Dashboard</h1>

            <!-- Schedule Appointment Section -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Schedule Appointment</h2>
                <a href="schedule.php" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200">Schedule an Appointment</a>
            </div>

            <!-- Reschedule Appointment Section -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md" id="rescheduleSection">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Reschedule Appointment</h2>
                <p id="rescheduleMessage" class="text-gray-700 mb-3">No appointments scheduled.</p>
                <a href="reschedule.php" id="rescheduleButton" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200 hidden">Reschedule Appointment</a>
            </div>

            <!-- Cancel Appointment Section -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md" id="cancelSection">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Cancel Appointment</h2>
                <p id="cancelMessage" class="text-gray-700 mb-3">No appointments scheduled.</p>
                <a href="cancel.php" id="cancelButton" class="bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition duration-200 hidden">Cancel Appointment</a>
            </div>
        <?php endif; ?>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
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
                                // Clear client-side session data
                                sessionStorage.removeItem('user_id');
                                sessionStorage.removeItem('role');
                                // Redirect to index.php or login.html
                                window.location.href = '/index.php';
                            } else {
                                alert('Failed to log out. Please try again.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            }

            // Fetch appointments and update the dashboard
            const patient_id = <?php echo json_encode($user_id); ?>; // Get patient ID from PHP session

            // Fetch appointments for patients
            if ('<?php echo $user_role; ?>' === 'patient') {
                fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            document.getElementById('rescheduleMessage').textContent = 'You have scheduled appointments.';
                            document.getElementById('cancelMessage').textContent = 'You have scheduled appointments.';

                            document.getElementById('rescheduleButton').classList.remove('hidden');
                            document.getElementById('cancelButton').classList.remove('hidden');
                        } else {
                            // If no appointments, display schedule button
                            document.getElementById('rescheduleMessage').textContent = 'No appointments scheduled.';
                            document.getElementById('cancelMessage').textContent = 'No appointments scheduled.';
                        }
                    })
                    .catch(error => console.error('Error fetching appointments:', error));
            }

            // Initialize FullCalendar
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                selectable: true,
                events: [{
                    // Events can be dynamically loaded here
                    url: '/api/get_doctor_availability.php',
                    method: 'GET',
                    failure: function() {
                        alert('There was an error while fetching availability!');
                    }
                }],
                select: function(info) {
                    var selectedDate = info.startStr;

                    if (confirm('Toggle availability for ' + selectedDate + '?')) {
                        fetch('/api/set_doctor_availability.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `availability[]=${selectedDate}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                                if (data.status) {
                                    calendar.refetchEvents(); // Reload calendar events
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }
            });
            calendar.render();
        });
    </script>
</body>

</html>