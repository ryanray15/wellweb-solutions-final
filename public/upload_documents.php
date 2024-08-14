<?php
session_start(); // Start the session
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Restrict access to logged-in users only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: login.php'); // Redirect to login page if not logged in or not a doctor
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
                <span class="text-white mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span>
                <button id="profileDropdown" class="text-white focus:outline-none">
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

    <div class="container mx-auto mt-10 px-6 py-8 bg-white rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-green-600">Upload Documents</h2>
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
            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-red-600 transition duration-200">Upload</button>
        </form>
    </div>

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
        });
    </script>
</body>

</html>