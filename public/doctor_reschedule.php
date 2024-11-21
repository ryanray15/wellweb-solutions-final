<?php
session_start();

// Check if the doctor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: /login.html');
    exit();
}

// Get the doctor ID and appointment ID from session and URL
$doctor_id = $_SESSION['user_id'];
$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    echo "Invalid appointment.";
    exit();
}

require_once '../config/database.php';
$db = include '../config/database.php';

// Fetch doctor's info
$query = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$query->bind_param("i", $doctor_id);
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
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
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

        .fc-event.red {
            background-color: red !important;
        }

        .fc-event.green {
            background-color: green !important;
        }

        .fc-event.blue {
            background-color: blue !important;
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

        #calendar {
            max-width: 900px;
            margin: 40px auto;
        }
    </style>
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="w-full mt-0 transparent-bg shadow-md p-1 fixed top-0 left-0 z-50">
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
        <div>
            <h1 class="text-center text-2xl font-bold mb-4 text-blue-600">Reschedule Appointment</h1>
        </div>
        <div class="flex-container">
            <!-- Calendar Display -->
            <div class="calendar-container flex items-center justify-center">
                <div id="calendar" class="mt-8 w-full"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);
            const doctorId = urlParams.get("doctor_id");
            const appointmentId = urlParams.get("appointment_id");
            const serviceId = urlParams.get("service_id"); // Get service_id from URL
            let consultationType;

            if (serviceId === "1") {
                consultationType = "online";
            } else if (serviceId === "2") {
                consultationType = "physical";
            } else {
                console.error("Unknown service_id:", serviceId);
                consultationType = null; // Handle unknown service_id gracefully
            }

            if (!doctorId || !appointmentId) {
                alert("Invalid URL parameters. Missing doctor or appointment information.");
                return;
            }

            const calendarEl = document.getElementById("calendar");
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "timeGridWeek",
                timeZone: "Asia/Manila",
                headerToolbar: {
                    left: "prev,next today",
                    center: "title",
                    right: "dayGridMonth,timeGridWeek,timeGridDay",
                },
                events: {
                    url: `/api/get_doctor_availability_reschedule.php`,
                    method: "GET",
                    extraParams: {
                        doctor_id: doctorId,
                        consultation_type: consultationType, // Use the mapped value
                    },
                    failure: function() {
                        alert("Failed to load availability events.");
                    },
                },
                eventClick: function(info) {
                    const selectedAvailabilityId = info.event.id;
                    const selectedDate = info.event.startStr.split("T")[0];
                    const selectedStartTime = info.event.startStr.split("T")[1];
                    const selectedEndTime = info.event.endStr.split("T")[1];

                    const confirmation = confirm(
                        `Do you want to reschedule this appointment to ${selectedDate} from ${selectedStartTime} to ${selectedEndTime}?`
                    );

                    if (confirmation) {
                        fetch("/api/reschedule_appointment_doctor.php", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/json",
                                },
                                body: JSON.stringify({
                                    appointment_id: appointmentId,
                                    availability_id: selectedAvailabilityId,
                                    date: selectedDate,
                                    start_time: selectedStartTime,
                                    end_time: selectedEndTime,
                                }),
                            })
                            .then((response) => response.json())
                            .then((data) => {
                                if (data.status) {
                                    alert("Appointment successfully rescheduled.");
                                    calendar.refetchEvents(); // Refresh the calendar
                                } else {
                                    alert("Failed to reschedule the appointment.");
                                }
                            })
                            .catch((error) => {
                                console.error("Error:", error);
                                alert("An error occurred. Please try again.");
                            });
                    }
                },
            });

            calendar.render();
        });
    </script>
</body>

</html>