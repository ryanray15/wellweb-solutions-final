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
        "SELECT a.*, p.first_name, p.middle_initial, p.last_name 
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
    <style>
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
    </style>
</head>

<body class="bg-gray-100">
    <!-- Navigation Bar -->
    <nav class="bg-green-600 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/icon.ico" alt="Icon" class="h-10 w-10 mr-4">
                <a href="/index.php" class="text-white text-2xl font-bold">Wellweb</a>
            </div>
            <div class="relative w-1/3 mx-auto"> <!-- Adjust width and center the search bar -->
                <input type="text" id="doctorSearchBar" placeholder="Search for doctors..."
                    class="w-full p-2 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                <div id="searchResults" class="absolute bg-white w-full shadow-lg rounded-lg mt-2 hidden"></div>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-white focus:outline-none">
                    <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span>
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

                <!-- Doctor Dashboard -->
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
                                            <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['middle_initial'] . ' ' . $appointment['last_name']); ?>
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

                    <!-- New Section for Consultation Type and Duration -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="w-full mr-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="consultation_type">Consultation Type</label>
                            <select id="consultation_type" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                                <option value="online">Online Consultation</option>
                                <option value="physical">Physical Consultation</option>
                                <option value="both">Both</option>
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

                    <!-- New Section for Availability Controls -->
                    <div class="flex justify-between items-center mb-6">
                        <div class="w-full mr-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="availability_date">Choose Date</label>
                            <input type="date" id="availability_date" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                        </div>
                        <div class="w-full mr-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="start_time">Start Time</label>
                            <input type="time" id="start_time" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                        </div>
                        <div class="w-full ml-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="end_time">End Time</label>
                            <input type="time" id="end_time" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                        </div>
                    </div>

                    <div class="mb-6">
                        <div class="w-full mr-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="status">Availability Status</label>
                            <select id="status" class="shadow border rounded-lg w-fit py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                                <option value="Available">Available</option>
                                <option value="Not Available">Not Available</option>
                            </select>
                        </div>
                        <div class="w-full mt-4">
                            <button id="set_availability" class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition duration-200">Set Availability</button>
                        </div>
                    </div>

                    <!-- FullCalendar Display -->
                    <div id="calendar" class="mt-6"></div>
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

            <!-- Manage Specializations Section -->
            <div id="manageSpecializationsSection" class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-bold mb-4 text-green-700">Manage Specializations</h2>
                <form id="addSpecializationForm">
                    <input
                        type="text"
                        id="specializationName"
                        name="specializationName"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-green-500"
                        placeholder="Enter specialization name"
                        required />
                    <button
                        type="submit"
                        class="bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition duration-200">
                        Add Specialization
                    </button>
                </form>
                <h3 class="text-xl font-bold mt-8 mb-4 text-green-700">Existing Specializations</h3>
                <ul id="specializationList">
                    <!-- Specializations will be loaded here dynamically -->
                </ul>
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
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>

</html>