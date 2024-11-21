<?php
session_start(); // Start the session
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.html'); // Redirect to login page if not logged in or not a doctor
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "uploads/documents/";  // Updated path

    // Check if the directory exists, if not create it
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["document"]["name"]);
    $uploadOk = 1;
    $documentType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is a valid document type
    $valid_types = array("pdf", "jpg", "jpeg", "png");
    if (!in_array($documentType, $valid_types)) {
        $uploadOk = 0;
        $error_message = "Sorry, only PDF, JPG, JPEG, & PNG files are allowed.";
    }

    // Check if upload is successful
    if ($uploadOk && move_uploaded_file($_FILES["document"]["tmp_name"], $target_file)) {
        // Update the database to mark documents as submitted
        $query = $db->prepare("INSERT INTO doctor_verifications (doctor_id, document_path, status, submitted_at) VALUES (?, ?, 'pending', NOW())");
        $query->bind_param("is", $user_id, $target_file);

        if ($query->execute()) {
            $success_message = "Document uploaded successfully.";
        } else {
            $error_message = "Database update failed: " . $query->error;
        }
    } else {
        $error_message = $error_message ?? "Sorry, there was an error uploading your file.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-image: url('img/bg_doctor.jpg');
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
    <nav class="w-full mt-0 transparent-bg shadow-md p-1 fixed top-0 left-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <a href="index.php"><span class="text-blue-500 text-2xl font-bold">WELL WEB SOLUTIONS</span></a>
            </div>
            <div class="relative">

                <button id="profileDropdown" class="text-gray-700 py-2 px-4 rounded-full hover:border-2 border-blue-400 hover:text-blue-500 transition duration-300">
                    <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span>

                </button>
                <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
                    <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
                    <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
                    <a href="onboarding.php" id="onboarding" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Stripe Connect</a>
                    <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto mt-28 px-6 py-8 bg-white rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-blue-600">Upload Documents</h2>
        <?php if (isset($success_message)) : ?>
            <p class="text-green-600 mb-4"><?php echo $success_message; ?></p>
        <?php elseif (isset($error_message)) : ?>
            <p class="text-red-600 mb-4"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="upload_documents.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="document" class="block text-gray-700">Upload ID/License/Certification</label>
                <input type="file" id="document" name="document" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-green-500" required>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200">Upload</button>
        </form>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>