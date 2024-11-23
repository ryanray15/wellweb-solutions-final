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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Stripe Onboarding</title>
  <link href="assets/css/tailwind.css" rel="stylesheet" />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    rel="stylesheet" />
  <script type="module" src="dist/bundle.js"></script>
  <script src="assets/js/utils.js"></script>
  <style>
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
      background-color: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);

    }
  </style>
</head>

<body>
  <!-- Navigation Bar -->
  <nav class="w-full mt-0 transparent-bg shadow-md p-1 fixed top-0 left-0 z-50">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex items-center">
        <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
        <a href="index.php"><span class="text-blue-400 text-2xl font-bold">WELL WEB SOLUTIONS</span></a>
      </div>
      <div class="relative">
        <button id="profileDropdown" class="text-gray-700 py-2 px-4 rounded-full hover:border-2 border-blue-400 hover:text-blue-500 transition duration-300 ">
          <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span>

        </button>
        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
          <a href="dashboard.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Dashboard</a>
          <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
          <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div id="onboarding-container" class="mt-10">
    <!-- Stripe onboarding component will be embedded here -->
  </div>
</body>

</html>

<script>
  // Profile Dropdown
  const profileDropdown = document.getElementById("profileDropdown");
  const dropdownMenu = document.getElementById("dropdownMenu");

  profileDropdown.addEventListener("click", () => {
    dropdownMenu.classList.toggle("hidden");
  });

  // Logout functionality
  const logoutButton = document.getElementById("logout");
  if (logoutButton) {
    logoutButton.addEventListener("click", () => {
      fetch("/api/logout.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
        })
        .then((response) => response.json())
        .then((data) => {
          if (data.status) {
            sessionStorage.removeItem("user_id");
            sessionStorage.removeItem("role");
            window.location.href = "/index.php";
          } else {
            alert("Failed to log out. Please try again.");
          }
        })
        .catch((error) => console.error("Error:", error));
    });
  }
</script>