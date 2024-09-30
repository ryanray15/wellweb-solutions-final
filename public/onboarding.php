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
  <script src="assets/js/common.js"></script>
</head>

<body>
  <!-- Navigation Bar -->
  <nav class="bg-green-600 p-4">
    <div class="container mx-auto flex justify-between items-center">
      <div class="flex items-center">
        <img src="img/icon.ico" alt="Icon" class="h-10 w-10 mr-4">
        <a href="/index.php" class="text-white text-2xl font-bold">Wellweb</a>
      </div>
      <div class="relative">
        <?php if ($user_role === 'doctor') : ?>
          <!-- Wallet Button -->
          <button id="walletButton" class="text-white focus:outline-none">
            <i class="fas fa-wallet fa-2x"></i>
          </button>

          <script>
            document.getElementById('walletButton').addEventListener('click', () => {
              // window.location.href = "/onboarding.php"; // Redirect to the onboarding page
            });
          </script>
        <?php endif; ?>

        <button id="profileDropdown" class="text-white focus:outline-none">
          <!-- <span class="mr-2"><?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?></span> -->
          <i class="fas fa-user-circle fa-2x"></i>
        </button>
        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 py-2 w-48 bg-white rounded-lg shadow-xl z-20">
          <a href="profile.php" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profile</a>
          <a href="#" id="logout" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Logout</a>
        </div>
      </div>
    </div>
  </nav>

  <div id="onboarding-container">
    <!-- Stripe onboarding component will be embedded here -->
  </div>
</body>

</html>