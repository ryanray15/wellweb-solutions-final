<?php
session_start(); // Start the session

// Check if the user is logged in
$loggedIn = isset($_SESSION['user_id']);

// Safely access the user role if logged in
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null; // Check if 'role' is set

// Fetch user information if logged in
$userInfo = [];
if ($loggedIn) {
    $user_id = $_SESSION['user_id'];
    require_once '../config/database.php';
    $db = include '../config/database.php';
    $query = $db->prepare("SELECT first_name, middle_initial, last_name FROM users WHERE user_id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $userInfo = $query->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Wellweb</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

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
    </style>
</head>

<body class="bg-white">

    <!-- Navigation Bar -->
    <nav class="container mx-auto mt-4 transparent-bg shadow-md p-4">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <span class=" text-blue-500 text-2xl font-bold ">WELL WEB SOLUTIONS</span>
            </div>
            <div>
                <?php if ($loggedIn) : ?>
                    <!-- Show profile dropdown for logged-in users -->
                    <div class="relative">
                        <button id="profileDropdown" class="text-white focus:outline-none">
                            <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span> <!-- Display user's name -->
                            <i class="fas fa-user-circle fa-2x"></i>
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
                <?php else : ?>
                    <!-- Show login/register buttons for guests -->
                    <button onclick="window.location.href='login.html'" class=" text-gray-700 py-2 px-4 rounded hover:bg-blue-600 hover:text-white transition duration-300">Login</button>
                    <button onclick="window.location.href='register.html'" class=" text-gray-700 py-2 px-4 rounded hover:bg-blue-600 hover:text-white transition duration-300">Register</button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="content-container px-6 py-8">
        <div class="container mx-auto mt-10 p-8">
            <h1 class="text-4xl font-bold text-blue-500 mb-5">Welcome to Well Web Solutions</h1>
            <p class="text-lg text-gray-700 mb-8">Your convenient way to schedule medical appointments with ease.</p>

            <!-- Call to Action -->
            <div class="flex space-x-4">
                <a href="schedule.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition duration-300">Schedule Appointment</a>
                <a href="learn-more.html" class="bg-white text-blue-500 font-bold py-2 px-6 rounded-lg hover:bg-blue-600 hover:text-white transition duration-300 border-2 border-blue-500">Learn More</a>
            </div>
        </div>

        <!-- Additional homepage content -->
        <div class="container mx-auto mt-10 transparent-bg p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-calendar-check fa-3x  mb-4" style="color: #3b82f6"></i>
                    <h3 class="text-xl font-bold mb-2">Easy Scheduling</h3>
                    <p class="text-gray-700">Schedule appointments effortlessly with our user-friendly interface.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-stethoscope fa-3x mb-4" style="color: #3b82f6"></i>
                    <h3 class="text-xl font-bold mb-2">Qualified Doctors</h3>
                    <p class="text-gray-700">Access a network of highly qualified healthcare professionals.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-mobile-alt fa-3x  mb-4" style="color: #3b82f6"></i>
                    <h3 class="text-xl font-bold mb-2">Mobile Access</h3>
                    <p class="text-gray-700">Manage your appointments on the go with our mobile-friendly platform.</p>
                </div>
            </div>
        </div>
        <div class="container mx-auto mt-10 transparent-bg p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Feature 1 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-hospital-user fa-3x  mb-4" style="color: #3b82f6"></i>
                    <h3 class="text-xl font-bold mb-2">Locate & Book</h3>
                    <p class="text-gray-700">Access real-time hospital locations and easily book appointments through our system.</p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-user-nurse fa-3x mb-4" style="color: #3b82f6"></i>
                    <h3 class="text-xl font-bold mb-2">Join Now!</h3>
                    <p class="text-gray-700">Join our platform to connect with patients as a freelance doctor, offering your expertise and personalized care on-demand.</p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <i class="fas fa-home fa-3x  mb-4" style="color: #3b82f6"></i>
                    <h3 class="text-xl font-bold mb-2">Physical Consultation</h3>
                    <p class="text-gray-700">Experience the convenience of our Home Service</p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>