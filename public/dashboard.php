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

// // Function to fetch appointments for doctors
// function fetchDoctorAppointments($db, $user_id)
// {
//     $query = $db->prepare(
//         "SELECT a.*, p.first_name, p.middle_initial, p.last_name 
//          FROM appointments a
//          JOIN users p ON a.patient_id = p.user_id
//          WHERE a.doctor_id = ? AND a.status != 'canceled'"
//     );
//     $query->bind_param("i", $user_id);
//     $query->execute();
//     return $query->get_result()->fetch_all(MYSQLI_ASSOC);
// }

// // Fetch doctor appointments if the user is a doctor
// $appointments = [];
// if ($user_role === 'doctor' && $is_verified) {
//     $appointments = fetchDoctorAppointments($db, $user_id);
// }

// Initialize the $verifications variable
$verifications = [];

if ($user_role === 'admin') {
    // Fetch pending verifications from the database
    $query = $db->query("
        SELECT dv.id, u.first_name, u.middle_initial, u.last_name, dv.status, dv.document_path
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
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <style>
        body {
            background-image: url('img/bg_doctor.jpg');
            /* Update with your image path */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        #searchResults {
            top: 100%;
            /* Position just below the search bar */
            left: 0;
            z-index: 10;
            max-height: 300px;
            overflow-y: auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 350px;
            /* Increased width */
        }

        #doctorSearchBar {
            width: 350px;
            /* Match width with search results for consistency */
            padding: 10px;
            border-radius: 8px;
        }

        #searchResults div {
            padding: 12px;
            cursor: pointer;
            display: flex;
            /* Flexbox for aligning image and text */
            align-items: center;
            transition: background-color 0.2s ease;
        }

        #searchResults div:hover {
            background-color: #f1f1f1;
        }

        #searchResults img {
            width: 40px;
            /* Image size */
            height: 40px;
            border-radius: 50%;
            /* Circular image */
            margin-right: 10px;
            /* Space between image and text */
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

        .transparent-bg {
            background-color: rgba(255, 255, 255, 0.7);
            /* White with 70% opacity */
            backdrop-filter: blur(10px);
            /* Optional: adds a blur effect to the background */

        }

        /* Hide all tab content by default */
        .tab-content .tab-pane {
            display: none;
        }

        /* Show active tab content */
        .tab-content .tab-pane.active {
            display: block;
        }

        /* Style for active tab link */
        .tab-link.active {
            border-bottom: 2px solid #3b82f6;
            color: #3b82f6;
        }

        #calendar {
            /* min-height: 800px; */
            /* Adjust height as needed */
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
            <?php if ($user_role === 'patient') : ?>
                <div class="relative w-1/3 mx-auto"> <!-- Adjust width and center the search bar -->
                    <input type="text" id="doctorSearchBar" placeholder="Search for doctors..."
                        class="w-full p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <div id="searchResults" class="absolute bg-white w-full shadow-lg rounded-lg mt-2 hidden"></div>
                </div>
            <?php endif; ?>
            <?php if ($user_role === 'patient') : ?>
                <div class="relative">
                    <button id="notificationDropdown" class="text-blue-500 focus:outline-none">
                        <i class="fas fa-bell fa-2x mr-4"></i>
                    </button>
                    <div id="notificationMenu" class="hidden absolute right-0 mt-2 py-2 w-64 bg-white rounded-lg shadow-xl z-20">
                        <ul id="notificationList">
                            <!-- Notifications will be dynamically loaded here -->
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            <div class="relative">
                <?php if ($user_role === 'doctor') : ?>
                    <!-- Wallet Button -->
                    <button id="walletButton" class="text-blue-500 mr-3 focus:outline-none">
                        <i class="fas fa-wallet fa-2x"></i>
                    </button>

                    <!-- TODO -->
                    <!-- <script>
                        document.getElementById('walletButton').addEventListener('click', () => {
                            window.location.href = "/stripe-express.php"; 
                        });
                    </script> -->
                <?php endif; ?>

                <button id="profileDropdown" class="text-blue-500 focus:outline-none">
                    <!-- <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span> -->
                    <i class="fas fa-user-circle fa-2x"></i>
                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                    <?php if ($user_role === 'doctor') : ?>
                        <a href="onboarding.php" id="onboarding" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Stripe Connect</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'doctor' && $documents_submitted && !$is_verified) : ?>
                        <a href="upload_documents.php" id="upload" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Upload Documents</a>
                    <?php endif; ?>

                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto mt-28">
        <?php if ($user_role === 'doctor') : ?>
            <div id="embedded-onboarding-container"></div>
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
                    <p class="mt-4 text-gray-700">Your account is currently pending verification. Or, you can re-upload your documents <a href="upload_documents.php" id="upload" class="text-blue-500 hover:bg-gray-100">here</a>.</p>
                </div>
            <?php elseif ($is_verified) : ?>
                <!-- Case 3: Verified doctor -->
                <h1 class="text-3xl font-bold text-blue-600 mb-8">Doctor Dashboard</h1>
                <!-- Include full dashboard functionalities for doctors here -->

                <!-- Doctor Dashboard -->
                <div class="mb-8">


                    <!-- Tab Navigation -->
                    <ul class="flex border-b mb-6">
                        <li class="mr-1">
                            <button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4 rounded-t-lg focus:outline-none" data-tab="availability">Your Appointments</button>
                        </li>
                        <li>
                            <button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4 rounded-t-lg focus:outline-none" data-tab="appointments">Set Your Availability</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Your Appointments Section -->
                        <div class="tab-pane hidden" id="availability">
                            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                                <h2 class="text-2xl font-bold mb-4 text-blue-500">Your Appointments</h2>
                                <div class="flex items-center mb-4">
                                    <label for="doctorAppointmentType" class="mr-2 font-semibold">Filter by Type:</label>
                                    <select id="doctorAppointmentType" class="px-2 py-1 border rounded">
                                        <option value="all">All</option>
                                        <option value="online">Online Consultation</option>
                                        <option value="physical">Physical Consultation</option>
                                    </select>
                                </div>
                                <table class="min-w-full bg-white">
                                    <thead class="bg-gray-200">
                                        <tr>
                                            <th class="w-1/4 px-4 py-2">Patient Name</th>
                                            <th class="w-1/4 px-4 py-2">Date</th>
                                            <th class="w-1/4 px-4 py-2">Time</th>
                                            <th class="w-1/4 px-4 py-2">Status</th>
                                            <th class="w-1/4 px-4 py-2">Due in</th>
                                            <th class="w-1/4 px-4 py-2">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="doctorAppointmentsTable">
                                        <!-- Appointments will be populated here by JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Set Your Availability Section -->
                        <div class="tab-pane" id="appointments">
                            <div class="p-6 bg-white rounded-lg shadow-md">
                                <h2 class="text-2xl font-bold mb-4 text-blue-600">Set Your Availability</h2>

                                <div class="flex justify-between items-center mb-6">
                                    <div class="w-full mr-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="consultation_type">Consultation Type</label>
                                        <select id="consultation_type" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                                            <option value="online">Online Consultation</option>
                                            <option value="physical">Physical Consultation</option>
                                        </select>
                                    </div>
                                    <div class="w-full ml-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2" for="consultation_duration">Consultation Duration</label>
                                        <select id="consultation_duration" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                                            <option value="30">30 Minutes</option>
                                            <option value="60">1 Hour</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Draggable Events for setting availability -->
                                <div class="mb-4">
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Drag the Event to Set Availability</h3>
                                    <p class="text-sm text-gray-500 mb-2">Drag this item to the calendar below to set your availability</p>
                                    <div id="external-events" class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                        <div id="availability-event"
                                            class="fc-event bg-blue-500 text-white font-semibold px-4 py-2 rounded-lg shadow-lg cursor-pointer transform transition-transform duration-200 hover:scale-105 hover:shadow-xl">
                                            <span id="availability-text">Set Availability</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- FullCalendar Display -->
                                <div id="calendar" class="mt-6 w-full p-4 bg-gray-100 rounded-lg shadow-md"></div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            <?php endif; ?>

            <!-- Admin Dashboard -->
            <?php if ($user_role === 'admin') : ?>
                <h1 class="text-3xl font-bold text-blue-600 mb-8">Admin Dashboard</h1>

                <!-- Quick Stats Section -->
                <div class="flex flex-wrap -mx-4 mb-8">
                    <div class="col-lg-4 px-4">
                        <div class="card bg-white p-6 rounded-lg shadow-md">
                            <h5 class="card-title text-xl font-bold text-blue-700 mb-2">Total Patients</h5>
                            <p class="card-text font-extrabold text-3xl text-gray-700" id="totalPatients">Loading...</p>
                        </div>
                    </div>
                    <div class="col-lg-4 px-4">
                        <div class="card bg-white p-6 rounded-lg shadow-md">
                            <h5 class="card-title text-xl font-bold text-blue-700 mb-2">Total Doctors</h5>
                            <p class="card-text font-extrabold text-3xl text-gray-700" id="totalDoctors">Loading...</p>
                        </div>
                    </div>
                    <div class="col-lg-4 px-4">
                        <div class="card bg-white p-6 rounded-lg shadow-md">
                            <h5 class="card-title text-xl font-bold text-blue-700 mb-2">Pending Verifications</h5>
                            <p class="card-text font-extrabold text-3xl text-gray-700" id="pendingVerifications">Loading...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <div class="mb-8">
                    <ul class="flex space-x-4 border-b">
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="specializations">Manage Specializations</button></li>
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="users">Manage Users</button></li>
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="verification">Doctor Verification</button></li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="tab-content mb-8">
                    <!-- Manage Specializations Section -->
                    <div class="tab-pane" id="specializations">
                        <div class="p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-4 text-blue-700">Manage Specializations</h2>
                            <form id="addSpecializationForm">
                                <input
                                    type="text"
                                    id="specializationName"
                                    name="specializationName"
                                    class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500"
                                    placeholder="Enter specialization name"
                                    required />
                                <button
                                    type="submit"
                                    class="bg-blue-600 mt-8 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                                    Add Specialization
                                </button>
                            </form>
                            <h3 class="text-xl font-bold mt-8 mb-4 text-blue-700">Existing Specializations</h3>
                            <ul id="specializationList">
                                <!-- Specializations will be loaded here dynamically -->
                            </ul>
                        </div>
                    </div>

                    <!-- Manage Users Section -->
                    <div class="tab-pane hidden" id="users">
                        <div class="p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-4 text-blue-700">Manage Users</h2>
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
                    </div>

                    <!-- Doctor Verification Section -->
                    <div class="tab-pane hidden" id="verification">
                        <div class="p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-4 text-blue-700">Doctor Verification</h2>
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
                                                <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($verification['first_name'] . ' ' . $verification['middle_initial'] . ' ' . $verification['last_name']); ?></td>
                                                <td class="border-b border-gray-200 px-4 py-2"><?php echo htmlspecialchars($verification['status']); ?></td>
                                                <td class="border-b border-gray-200 px-4 py-2">
                                                    <?php if (!empty($verification['document_path'])) : ?>
                                                        <a href="<?php echo htmlspecialchars($verification['document_path']); ?>" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded">
                                                            View Document
                                                        </a>
                                                    <?php else : ?>
                                                        <span class="text-gray-500">No document uploaded</span>
                                                    <?php endif; ?>
                                                    <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded" onclick="verifyDoctor(<?php echo $verification['doctor_id']; ?>, 'approve')">Verify</button>
                                                    <button class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded" onclick="verifyDoctor(<?php echo $verification['doctor_id']; ?>, 'reject')">Reject</button>
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
                    </div>
                </div>
            <?php endif; ?>

            <!-- Patient Dashboard -->
            <?php if ($user_role === 'patient') : ?>

                <h1 class="text-3xl font-bold text-blue-600 mb-8">Patient Dashboard</h1>

                <!-- Tab Navigation -->
                <div class="mb-6">
                    <ul class="flex space-x-4">
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="appointments">Your Appointments</button></li>
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="schedule">Schedule Appointment</button></li>
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="reschedule">Reschedule Appointment</button></li>
                        <li><button class="tab-link text-blue-500 hover:text-gray-500 font-bold py-2 px-4" data-tab="cancel">Canceled Appointment</button></li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Your Appointments Section -->
                    <div class="tab-pane" id="appointments">
                        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-4 text-blue-500">Your Appointments</h2>
                            <div class="flex items-center">
                                <label for="patientAppointmentType" class="mr-2 mb-2 font-semibold">Filter by Type:</label>
                                <select id="patientAppointmentType" class="px-2 mb-2 py-1 border rounded">
                                    <option value="all">All</option>
                                    <option value="online">Online Consultation</option>
                                    <option value="physical">Physical Consultation</option>
                                </select>
                            </div>
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-200">
                                    <tr>
                                        <th class="w-1/4 px-4 py-2">Doctor Name</th>
                                        <th class="w-1/4 px-4 py-2">Date</th>
                                        <th class="w-1/4 px-4 py-2">Time</th>
                                        <th class="w-1/4 px-4 py-2">Due in</th>
                                        <th class="w-1/4 px-4 py-2">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="appointmentsTable">
                                    <!-- Appointments will be populated here by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Schedule Appointment Section -->
                    <div class="tab-pane hidden" id="schedule">
                        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-8 text-blue-500">Schedule Appointment</h2>
                            <a href="schedule.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">Schedule an Appointment</a>
                        </div>
                    </div>

                    <!-- Reschedule Appointment Section -->
                    <div class="tab-pane hidden" id="reschedule">
                        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-8 text-blue-500">Reschedule Appointment</h2>
                            <!-- <p id="rescheduleMessage" class="text-gray-700 mb-6">No appointments scheduled.</p> -->
                            <a href="reschedule.php" id="rescheduleButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">Reschedule Appointment</a>
                        </div>
                    </div>

                    <!-- Canceled Appointment Section -->
                    <div class="tab-pane hidden" id="cancel">
                        <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                            <h2 class="text-2xl font-bold mb-4 text-blue-500">Canceled Appointment</h2>
                            <p id="cancelMessage" class="text-gray-700 mb-3">Request a refund here.</p>
                            <a href="canceled.php" id="cancelButton" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">View</a>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

                </div>

                <script>
                    const user_id = <?php echo json_encode($user_id); ?>;
                    const user_role = <?php echo json_encode($user_role); ?>; // Assuming $user_role is defined as either 'patient' or 'doctor'
                </script>
                <script src="assets/js/utils.js"></script>
                <script src="assets/js/common.js"></script>
                <script src="assets/js/dashboard.js"></script>
                <!-- for tabs -->
                <script src="assets/js/tabswitch.js"></script>

                <!-- Include jQuery first -->
                <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
                <script type="text/javascript" src="assets/js/timepicker.js"></script>
                <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
                <?php if ($user_role === 'doctor') : ?>
                    <script type="module" src="dist/bundle.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
                <?php endif; ?>
                <!-- Footer -->
                <footer class="footer mt-10 p-4 bg-gray-100 flex justify-end">
                    <div class="text-right">
                        <p class="text-gray-600">© <?php echo date("Y"); ?> WELL WEB SOLUTIONS. All rights reserved.</p>
                        <p class="text-gray-600 mb-8">Contact us: <a href="mailto:support@wellwebsolutions.com" class="text-blue-500">support@wellwebsolutions.com</a></p>
                    </div>
                </footer>
</body>

</html>