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

// Check if user is a doctor and fetch verification status from doctor_verifications table
$documents_submitted = false;
$is_verified = false;
if ($user_role === 'doctor') {
    $query = $db->prepare("SELECT status FROM doctor_verifications WHERE doctor_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $verification = $query->get_result()->fetch_assoc();

    if ($verification) {
        $documents_submitted = true;
        $is_verified = ($verification['status'] === 'verified');
    }
}

// Function to fetch appointments for doctors
function fetchDoctorAppointments($db, $user_id)
{
    $query = $db->prepare(
        "SELECT a.*, p.name as patient_name 
         FROM appointments a
         JOIN users p ON a.patient_id = p.user_id
         WHERE a.doctor_id = ? AND a.status != 'canceled'"
    );
    $query->bind_param("i", $user_id);
    $query->execute();
    return $query->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch doctor appointments if the user is a doctor
$appointments = [];
if ($user_role === 'doctor' && $is_verified) {
    $appointments = fetchDoctorAppointments($db, $user_id);
}

// Initialize the $verifications variable
$verifications = [];

if ($user_role === 'admin') {
    // Fetch pending verifications from the database
    $query = $db->query("
        SELECT dv.id, u.name as doctor_name, dv.status, dv.document_path
        FROM doctor_verifications dv
        JOIN users u ON dv.doctor_id = u.user_id
        WHERE dv.status = 'pending'
    ");

    while ($row = $query->fetch_assoc()) {
        $verifications[] = $row;
    }
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
        <?php if ($user_role === 'doctor') : ?>
            <?php if (!$documents_submitted) : ?>
                <!-- Case 1: Documents not submitted -->
                <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                    <h1 class="text-3xl font-bold text-red-600">Restricted Access</h1>
                    <p class="mt-4 text-gray-700">Please <a href="upload_documents.php" class="text-green-600 hover:underline">submit your documents</a> for verification.</p>
                </div>
            <?php elseif ($documents_submitted && !$is_verified) : ?>
                <!-- Case 2: Documents submitted, but not yet verified -->
                <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                    <h1 class="text-3xl font-bold text-red-600">Restricted Access</h1>
                    <p class="mt-4 text-gray-700">Your account is currently pending verification. You will be notified once your account has been verified.</p>
                </div>
            <?php elseif ($is_verified) : ?>
                <!-- Case 3: Verified doctor -->
                <h1 class="text-3xl font-bold text-green-600 mb-8">Doctor Dashboard</h1>
                <!-- Include full dashboard functionalities for doctors here -->

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
                                            <button onclick="handleAppointmentAction(<?php echo $appointment['appointment_id']; ?>, 'accept')" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded">Accept</button>
                                            <button onclick="handleAppointmentAction(<?php echo $appointment['appointment_id']; ?>, 'reschedule')" class="bg-yellow-600 hover:bg-yellow-600 text-white font-bold py-1 px-3 rounded">Reschedule</button>
                                            <button onclick="handleAppointmentAction(<?php echo $appointment['appointment_id']; ?>, 'cancel')" class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded">Cancel</button>
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
        <?php endif; ?>

        <!-- Admin Dashboard -->
        <?php if ($user_role === 'admin') : ?>
            <h1 class="text-3xl font-bold text-green-600 mb-8">Admin Dashboard</h1>

            <!-- Quick Stats Section -->
            <div class="row">
                <div class="col-lg-4">
                    <div class="card bg-white p-6 mb-8 rounded-lg shadow-md">
                        <h5 class="card-title text-xl font-bold text-green-700 mb-2">Total Patients</h5>
                        <p class="card-text text-gray-700" id="totalPatients">Loading...</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card bg-white p-6 mb-8 rounded-lg shadow-md">
                        <h5 class="card-title text-xl font-bold text-green-700 mb-2">Total Doctors</h5>
                        <p class="card-text text-gray-700" id="totalDoctors">Loading...</p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card bg-white p-6 mb-8 rounded-lg shadow-md">
                        <h5 class="card-title text-xl font-bold text-green-700 mb-2">Pending Verifications</h5>
                        <p class="card-text text-gray-700" id="pendingVerifications">Loading...</p>
                    </div>
                </div>
            </div>

            <!-- Manage Users Section -->
            <div id="manageUsersSection" class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Manage Users</h2>
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="border-b border-gray-200 px-4 py-2">ID</th>
                            <th class="border-b border-gray-200 px-4 py-2">Name</th>
                            <th class="border-b border-gray-200 px-4 py-2">Email</th>
                            <th class="border-b border-gray-200 px-4 py-2">Role</th>
                            <th class="border-b border-gray-200 px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <!-- User rows will be dynamically loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Doctor Verification Section -->
            <div id="doctorVerificationSection" class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Doctor Verification</h2>
                <table class="w-full text-left">
                    <thead>
                        <tr>
                            <th class="border-b border-gray-200 px-4 py-2">ID</th>
                            <th class="border-b border-gray-200 px-4 py-2">Doctor Name</th>
                            <th class="border-b border-gray-200 px-4 py-2">Status</th>
                            <th class="border-b border-gray-200 px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="verificationTableBody">
                        <?php if (!empty($verifications)) : ?>
                            <?php foreach ($verifications as $verification) : ?>
                                <tr>
                                    <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($verification['id']); ?></td>
                                    <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($verification['doctor_name']); ?></td>
                                    <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($verification['status']); ?></td>
                                    <td class="border-b border-gray-200 px-4 py-2">
                                        <?php if (!empty($verification['document_path'])) : ?>
                                            <a href="<?php echo htmlspecialchars($verification['document_path']); ?>"
                                                target="_blank"
                                                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded">
                                                View Document
                                            </a>
                                        <?php else : ?>
                                            <span class="text-gray-500">No document uploaded</span>
                                        <?php endif; ?>
                                        <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded" onclick="verifyDoctor(<?php echo $verification['id']; ?>)">Verify</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray-600">No pending verifications found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

            // Handle Appointment Actions using AJAX
            window.handleAppointmentAction = function(appointmentId, action) {
                let endpoint;
                let requestData = {
                    appointment_id: appointmentId
                };

                if (action === 'accept') {
                    endpoint = '/api/doctor_accept_appointment.php';
                } else if (action === 'reschedule') {
                    let newDate = prompt('Enter the new date (YYYY-MM-DD):');
                    let newTime = prompt('Enter the new time (HH:MM:SS):');
                    if (!newDate || !newTime) {
                        alert('Invalid input. Please enter both date and time.');
                        return;
                    }
                    requestData.new_date = newDate;
                    requestData.new_time = newTime;
                    endpoint = '/api/doctor_reschedule_appointment.php';
                } else if (action === 'cancel') {
                    endpoint = '/api/doctor_cancel_appointment.php';
                }

                console.log("Sending request to:", endpoint, "with data:", requestData); // Debugging log

                fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(requestData)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log("Response received:", data); // Debugging log
                        alert(data.message);
                        if (data.status) {
                            // Optionally refresh the page or refetch appointments
                            window.location.reload();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            };

            // Initialize FullCalendar
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
                    url: '/api/get_doctor_availability.php?doctor_id=<?php echo $user_id; ?>', // Endpoint to fetch availability
                    method: 'GET',
                    failure: function() {
                        alert('There was an error while fetching availability!');
                    }
                },
                select: function(info) {
                    handleSelectEvent(info);
                },
                eventClick: function(info) {
                    handleEventClick(info);
                }
            });

            calendar.render();

            // Handle the selection of time/date range
            function handleSelectEvent(info) {
                let status = prompt("Enter 'Available' or 'Not Available'");
                if (status) {
                    // Determine if the selection is a day range or time range
                    if (info.allDay || info.view.type === 'dayGridMonth') {
                        // Handle full day range
                        fetch('/api/set_doctor_availability_day_range.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `start_date=${info.startStr}&end_date=${info.endStr}&status=${status}&allDay=1`
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                                if (data.status) {
                                    calendar.refetchEvents();
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    } else {
                        // Handle specific time range
                        fetch('/api/set_doctor_availability_time_range.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `date=${info.startStr.split('T')[0]}&start_time=${info.startStr.split('T')[1]}&end_time=${info.endStr.split('T')[1]}&status=${status}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                alert(data.message);
                                if (data.status) {
                                    calendar.refetchEvents();
                                }
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }
            }

            // Handle click on an existing event
            function handleEventClick(info) {
                if (confirm('Do you want to delete this schedule?')) {
                    fetch('/api/delete_doctor_availability.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: info.event.id
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            alert(data.message);
                            if (data.status) {
                                info.event.remove(); // Remove event from calendar view
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
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
        });
    </script>
</body>

</html>