<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}

// Include database connection
require_once '../config/database.php';
$db = include '../config/database.php';

// Fetch user information
$user_id = $_SESSION['user_id'];
$query = $db->prepare("SELECT first_name, middle_initial, last_name, email, contact_number, address, gender FROM users WHERE user_id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$userInfo = $query->get_result()->fetch_assoc();

// Fetch specializations if the user is a doctor
$specializations = [];
if ($_SESSION['role'] === 'doctor') {
    $specQuery = $db->prepare("SELECT specialization_id FROM doctor_specializations WHERE doctor_id = ?");
    $specQuery->bind_param("i", $user_id);
    $specQuery->execute();
    $result = $specQuery->get_result();
    while ($row = $result->fetch_assoc()) {
        $specializations[] = $row['specialization_id'];
    }
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_initial = $_POST['middle_initial'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $gender = $_POST['gender'] ?? ''; // Handle gender input
    $new_specializations = $_POST['specialization_id'] ?? [];

    // Prepare and bind update statement
    $updateQuery = $db->prepare("UPDATE users SET first_name = ?, middle_initial = ?, last_name = ?, email = ?, contact_number = ?, address = ?, gender = ? WHERE user_id = ?");
    $updateQuery->bind_param("sssssssi", $first_name, $middle_initial, $last_name, $email, $contact_number, $address, $gender, $user_id);

    if ($updateQuery->execute()) {
        // Update specializations if user is a doctor
        if ($_SESSION['role'] === 'doctor') {
            $deleteQuery = $db->prepare("DELETE FROM doctor_specializations WHERE doctor_id = ?");
            $deleteQuery->bind_param("i", $user_id);
            $deleteQuery->execute();

            $insertQuery = $db->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (?, ?)");
            foreach ($new_specializations as $spec_id) {
                $insertQuery->bind_param("ii", $user_id, $spec_id);
                $insertQuery->execute();
            }
        }

        $message = "Profile updated successfully!";
        // Refresh user info after update
        $query->execute();
        $userInfo = $query->get_result()->fetch_assoc();
    } else {
        $message = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Profile</title>
    <link href="assets/css/tailwind.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet" />
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
    <div class="container mx-auto mt-10 max-w-xl p-8 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-green-600 mb-8 text-center">Edit Profile</h1>

        <!-- Display message if available -->
        <?php if (isset($message)) : ?>
            <div class="mb-6 text-center text-green-700 font-semibold">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Profile Edit Form -->
        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" value="<?php echo htmlspecialchars($userInfo['first_name']); ?>" required />
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="middle_initial">Middle Initial</label>
                <input type="text" id="middle_initial" name="middle_initial" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" value="<?php echo htmlspecialchars($userInfo['middle_initial']); ?>" required />
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" value="<?php echo htmlspecialchars($userInfo['last_name']); ?>" required />
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input type="email" id="email" name="email" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" value="<?php echo htmlspecialchars($userInfo['email']); ?>" required />
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">Contact Number</label>
                <input type="text" id="contact_number" name="contact_number" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" value="<?php echo htmlspecialchars($userInfo['contact_number']); ?>" required />
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                <input type="text" id="address" name="address" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" value="<?php echo htmlspecialchars($userInfo['address']); ?>" required />
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="gender">Gender</label>
                <select id="gender" name="gender" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" required>
                    <option value="male" <?php echo $userInfo['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo $userInfo['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo $userInfo['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <?php if ($_SESSION['role'] === 'doctor') : ?>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="specialization_id">Specializations</label>
                    <select multiple id="specialization_id" name="specialization_id[]" class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500">
                        <?php
                        $specQuery = $db->query("SELECT id, name FROM specializations");
                        while ($spec = $specQuery->fetch_assoc()) {
                            $selected = in_array($spec['id'], $specializations) ? 'selected' : '';
                            echo "<option value='" . $spec['id'] . "' $selected>" . htmlspecialchars($spec['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="w-full bg-green-600 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                Update Profile
            </button>
        </form>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>