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
                    <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span> <!-- Display user's name -->
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
            Reschedule Appointment
        </h1>
        <form id="rescheduleForm" class="w-full mb-8">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="appointment_id">Select Appointment</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="appointment_id">
                    <!-- Populate this with options using JavaScript -->
                </select>
            </div>
            <div class="flex justify-between mb-6">
                <div class="w-full mr-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date">New Date</label>
                    <input class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="date" type="date" />
                </div>
                <div class="w-full ml-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="time">New Time</label>
                    <input class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="time" type="time" />
                </div>
            </div>
            <button class="w-full bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200" type="submit">
                Reschedule
            </button>
        </form>

        <!-- Calendar to Show Availability -->
        <div id="calendar" class="mt-8"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const profileDropdown = document.getElementById('profileDropdown');
            const dropdownMenu = document.getElementById('dropdownMenu');

            profileDropdown.addEventListener('click', () => {
                dropdownMenu.classList.toggle('hidden');
            });

            const appointmentSelect = document.getElementById('appointment_id');
            let selectedDoctorId = null;

            // Fetch appointments and populate the dropdown
            function fetchAppointments() {
                fetch('/api/get_appointments.php?patient_id=<?php echo $user_id; ?>')
                    .then(response => response.json())
                    .then(data => {
                        appointmentSelect.innerHTML = data.map(appointment =>
                            `<option value="${appointment.appointment_id}" data-doctor-id="${appointment.doctor_id}">
                                Appointment with Dr. ${appointment.doctor_name} on ${appointment.date} at ${appointment.time}
                            </option>`
                        ).join('');

                        // Trigger change event to load the calendar for the selected appointment
                        appointmentSelect.dispatchEvent(new Event('change'));
                    })
                    .catch(error => console.error('Error fetching appointments:', error));
            }

            // Handle appointment selection change
            appointmentSelect.addEventListener('change', function() {
                const selectedOption = appointmentSelect.options[appointmentSelect.selectedIndex];
                selectedDoctorId = selectedOption.getAttribute('data-doctor-id');

                if (selectedDoctorId) {
                    // Fetch and display the calendar with doctor's availability
                    fetchDoctorAvailability(selectedDoctorId);
                } else {
                    console.error('Doctor ID not found.');
                }
            });

            // Fetch and display doctor's availability
            function fetchDoctorAvailability(doctorId) {
                if (!doctorId) {
                    console.error('Doctor ID is null or undefined.');
                    return;
                }

                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    selectable: true,
                    timeZone: 'Asia/Manila', // Use local time zone
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: {
                        url: `/api/get_doctor_availability.php`,
                        method: 'GET',
                        extraParams: {
                            doctor_id: doctorId // Pass the correct doctor_id here
                        },
                        failure: function() {
                            alert('There was an error while fetching availability!');
                        }
                    }
                });

                calendar.render();
            }

            // Fetch initial data
            fetchAppointments();
        });
    </script>
</body>

</html>