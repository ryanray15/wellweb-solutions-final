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
    <title>Schedule Appointment</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script async src="https://js.stripe.com/v3/buy-button.js"></script> <!-- Add Stripe script -->
    <style>
        .doctor-card {
            transition: border-color 0.2s;
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
            <div class="relative">
                <button id="profileDropdown" class="text-white focus:outline-none">
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

    <div class="container mx-auto mt-10 max-w-2xl p-8 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-green-600 mb-8 text-center">
            Schedule Appointment
        </h1>
        <form id="scheduleForm" class="w-full">
            <!-- Step 1: Select Service -->
            <div class="step" id="step-1">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="service_id">Select Service</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="service_id">
                    <!-- Populate this with options using JavaScript -->
                </select>
            </div>

            <!-- Step 2: Select Specialization -->
            <div class="step" id="step-2" style="display:none;">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="specialization_id">Select Specialization</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="specialization_id">
                    <!-- Populate this with options using JavaScript -->
                </select>
            </div>

            <!-- Step 3: Select Doctor -->
            <div class="step" id="step-3" style="display:none;">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="doctor_id">Select Doctor</label>
                <div id="doctorGridContainer">
                    <div id="doctorsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Doctor grid will be populated dynamically -->
                    </div>
                </div>
            </div>

            <!-- Step 4: Schedule Appointment -->
            <div class="step" id="step-4" style="display:none;">
                <div id="appointmentScheduler">
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
                </div>

                <!-- Stripe Buy Button -->
                <div class="mt-4 text-center">
                    <stripe-buy-button
                        buy-button-id="buy_btn_1Q249D08GrFUpp2bleRtLFYw"
                        publishable-key="pk_test_51Q0mWz08GrFUpp2bxZpZ55e16ClgZ5jBudZW6buIyuzozAvD3OpRNb2eRHBcZJjpEtUvPjEeW3QsQj4QFlnZE58H00hT5LUq36">
                    </stripe-buy-button>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-8">
                <button type="button" id="prevBtn" class="bg-red-600 text-white py-2 px-4 rounded-lg" onclick="nextPrev(-1)" style="display:none;">Back</button>
                <button type="button" id="nextBtn" class="bg-green-600 text-white py-2 px-4 rounded-lg" onclick="nextPrev(1)">Next</button>
            </div>
        </form>
        <div id="calendar" class="mt-8"></div> <!-- Calendar for availability -->
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/schedule.js"></script>
    <script src="assets/js/multistep.js"></script> <!-- Add this for multi-step functionality -->
</body>

</html>