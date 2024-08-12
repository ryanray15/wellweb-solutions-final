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
$query = $db->prepare("SELECT name, email FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$userInfo = $query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule Appointment</title>
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
                    <span class="mr-2"><?php echo htmlspecialchars($userInfo['name']); ?></span> <!-- Display user's name -->
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
            Schedule Appointment
        </h1>
        <form id="scheduleForm" class="w-full">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="doctor_id">Select Doctor</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="doctor_id">
                    <!-- Populate this with options using JavaScript -->
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="service_id">Select Service</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="service_id">
                    <!-- Populate this with options using JavaScript -->
                </select>
            </div>
            <div class="flex justify-between mb-6">
                <div class="w-full mr-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="date">Choose Date</label>
                    <input class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="date" type="date" />
                </div>
                <div class="w-full ml-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="time">Choose Time</label>
                    <input class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="time" type="time" />
                </div>
            </div>
            <button class="w-full bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200" type="submit">
                Schedule Appointment
            </button>
        </form>
        <div id="calendar"></div> <!-- Add Calendar to Show Availability -->
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

            // Populate doctors and services dropdown
            // fetchDoctors();
            // fetchServices();

            // Event Listener for Doctor Selection
            const doctorSelect = document.getElementById('doctor_id');
            doctorSelect.addEventListener('change', loadDoctorAvailability);

            // Fetch Doctors
            function fetchDoctors() {
                fetch('/api/get_doctors.php')
                    .then(response => response.json())
                    .then(data => {
                        const doctorSelect = document.getElementById('doctor_id');
                        doctorSelect.innerHTML = data.map(doctor => `<option value="${doctor.id}">${doctor.name}</option>`).join('');
                    })
                    .catch(error => console.error('Error fetching doctors:', error));
            }

            // Fetch Services
            function fetchServices() {
                fetch('/api/get_services.php')
                    .then(response => response.json())
                    .then(data => {
                        const serviceSelect = document.getElementById('service_id');
                        serviceSelect.innerHTML = data.map(service => `<option value="${service.id}">${service.name}</option>`).join('');
                    })
                    .catch(error => console.error('Error fetching services:', error));
            }

            // Load Doctor Availability
            function loadDoctorAvailability() {
                const doctorId = doctorSelect.value;
                fetch(`/api/get_doctor_availability.php?doctor_id=${doctorId}`)
                    .then(response => response.json())
                    .then(events => {
                        // Render Calendar with Availability
                        renderCalendar(events);
                    })
                    .catch(error => console.error('Error fetching doctor availability:', error));
            }

            // Initialize and Render Calendar
            function renderCalendar(events) {
                const calendarEl = document.getElementById('calendar');
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
                    events: events,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    }
                });
                calendar.render();
            }

            // Disable Unavailable Slots
            function disableUnavailableSlots(events) {
                const dateInput = document.getElementById('date');
                const timeInput = document.getElementById('time');

                const unavailableDates = events.filter(event => event.title === 'Not Available').map(event => event.start.split('T')[0]);
                const unavailableTimes = events.filter(event => event.title === 'Not Available' && event.start.includes('T')).map(event => event.start.split('T')[1]);

                dateInput.addEventListener('change', function() {
                    const selectedDate = this.value;
                    if (unavailableDates.includes(selectedDate)) {
                        alert('This date is unavailable. Please choose another date.');
                        this.value = '';
                    }
                });

                timeInput.addEventListener('change', function() {
                    const selectedTime = this.value;
                    if (unavailableTimes.includes(selectedTime)) {
                        alert('This time slot is unavailable. Please choose another time.');
                        this.value = '';
                    }
                });
            }
        });
    </script>
</body>

</html>