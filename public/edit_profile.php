<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}

$user_role = $_SESSION['role'];

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
if ($user_role === 'doctor') {
    $specQuery = $db->prepare("SELECT specialization_id FROM doctor_specializations WHERE doctor_id = ?");
    $specQuery->bind_param("i", $user_id);
    $specQuery->execute();
    $result = $specQuery->get_result();
    while ($row = $result->fetch_assoc()) {
        $specializations[] = $row['specialization_id'];
    }
}

// Fetch consultation rate for the doctor
$consultationRate = null;
if ($user_role === 'doctor') {
    $rateQuery = $db->prepare("SELECT consultation_rate FROM doctor_rates WHERE doctor_id = ?");
    $rateQuery->bind_param("i", $user_id);
    $rateQuery->execute();
    $rateResult = $rateQuery->get_result()->fetch_assoc();
    $consultationRate = $rateResult ? $rateResult['consultation_rate'] / 100 : null; // Convert back from cents
}

// Fetch clinic hours for the doctor
$clinicHours = [
    'clinic_open_time' => null,
    'clinic_close_time' => null,
];
if ($user_role === 'doctor') {
    $hoursQuery = $db->prepare("SELECT clinic_open_time, clinic_close_time FROM doctor_clinic_hours WHERE doctor_id = ?");
    $hoursQuery->bind_param("i", $user_id);
    $hoursQuery->execute();
    $hoursResult = $hoursQuery->get_result()->fetch_assoc();
    if ($hoursResult) {
        $clinicHours['clinic_open_time'] = $hoursResult['clinic_open_time'];
        $clinicHours['clinic_close_time'] = $hoursResult['clinic_close_time'];
    }
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $first_name = $_POST['first_name'] ?? '';
    $middle_initial = $_POST['middle_initial'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $contact_number = $_POST['contact_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $new_specializations = $_POST['specialization_id'] ?? [];

    // Update basic user information
    $updateQuery = $db->prepare("
        UPDATE users
        SET first_name = ?, middle_initial = ?, last_name = ?, email = ?, 
            contact_number = ?, address = ?, gender = ?
        WHERE user_id = ?
    ");
    $updateQuery->bind_param(
        "sssssssi",
        $first_name,
        $middle_initial,
        $last_name,
        $email,
        $contact_number,
        $address,
        $gender,
        $user_id
    );

    if ($updateQuery->execute()) {
        // Update specializations for doctors
        if ($user_role === 'doctor') {
            // Update consultation rate
            $consultation_rate = $_POST['consultation_rate'] ? floatval($_POST['consultation_rate']) * 100 : null; // Convert to cents
            $rateQuery = $db->prepare("
                INSERT INTO doctor_rates (doctor_id, consultation_rate)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE consultation_rate = ?
            ");
            $rateQuery->bind_param("iii", $user_id, $consultation_rate, $consultation_rate);
            $rateQuery->execute();

            // Update clinic hours
            $clinic_open_time = $_POST['clinic_open_time'] ?? null;
            $clinic_close_time = $_POST['clinic_close_time'] ?? null;
            $hoursQuery = $db->prepare("
                INSERT INTO doctor_clinic_hours (doctor_id, clinic_open_time, clinic_close_time)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE clinic_open_time = ?, clinic_close_time = ?
            ");
            $hoursQuery->bind_param("issss", $user_id, $clinic_open_time, $clinic_close_time, $clinic_open_time, $clinic_close_time);
            $hoursQuery->execute();

            // Update specializations
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
        // Redirect to refresh data
        header("Location: edit_profile.php");
        exit();
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <style>
        body {
            background-image: url('img/bg_doctor.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            opacity: 0.9;
        }

        .form-container {
            background-color: white;
            padding: 3rem;
            border-radius: 1rem;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            /* Two columns layout */
            gap: 2rem;
            /* Space between columns */
        }

        .full-width {
            grid-column: span 2;
            /* Elements spanning full width */
        }

        .input-field {
            width: 100%;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border-radius: 0.375rem;
            border: 1px solid #d1d5db;
            /* Default Tailwind border color */
            transition: border-color 0.2s ease;
        }

        .input-field:focus {
            border-color: #3182ce;
            outline: none;
        }

        .label-text {
            font-size: 1rem;
            font-weight: bold;
        }

        .form-element {
            margin-bottom: 2rem;
            /* Add space between form fields */
        }

        .form-button {
            background-color: #48BB78;
            color: white;
            font-weight: bold;
            padding: 0.75rem;
            border-radius: 0.375rem;
            transition: background-color 0.2s;
        }

        .form-button:hover {
            background-color: #38A169;
        }

        .navbar {
            background-color: #2D3748;
            /* Dark background for navbar */
            padding: 1rem;
            color: white;
        }

        .navbar a {
            color: white;
            margin-right: 1rem;
            text-decoration: none;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .transparent-bg {
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);

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

<body class="bg-gray-100 flex flex-col">
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

    <div class="container mx-auto mt-28 flex-grow">
        <div class="form-container">
            <h1 class="text-3xl font-bold mb-8 text-center text-blue-600">Edit Profile</h1>

            <!-- Display message if available -->
            <?php if (isset($message)) : ?>
                <div class="mb-6 text-center text-green-700 font-semibold">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Profile Edit Form -->
            <form method="POST" action="">
                <div class="form-grid">
                    <!-- Left Side: Profile Form -->
                    <div class="left-column">
                        <div class="form-element">
                            <label for="first_name" class="label-text text-gray-700">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="input-field" value="<?php echo htmlspecialchars($userInfo['first_name']); ?>" required />
                        </div>
                        <div class="form-element">
                            <label for="middle_initial" class="label-text text-gray-700">Middle Initial</label>
                            <input type="text" id="middle_initial" name="middle_initial" class="input-field" value="<?php echo htmlspecialchars($userInfo['middle_initial']); ?>" maxlength="1" />
                        </div>
                        <div class="form-element">
                            <label for="last_name" class="label-text text-gray-700">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="input-field" value="<?php echo htmlspecialchars($userInfo['last_name']); ?>" required />
                        </div>
                        <div class="form-element">
                            <label for="contact_number" class="label-text text-gray-700">Contact Number</label>
                            <input type="text" id="contact_number" name="contact_number" class="input-field" value="<?php echo htmlspecialchars($userInfo['contact_number']); ?>" required />
                        </div>
                        <div class="form-element">
                            <label for="email" class="label-text text-gray-700">Email</label>
                            <input type="email" id="email" name="email" class="input-field" value="<?php echo htmlspecialchars($userInfo['email']); ?>" required />
                        </div>
                        <div class="form-element">
                            <label for="gender" class="label-text text-gray-700">Gender</label>
                            <select id="gender" name="gender" class="input-field" required>
                                <option value="Male" <?php echo $userInfo['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $userInfo['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $userInfo['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Side: Address to Gender -->
                    <div class="right-column">
                        <div class="form-element">
                            <label for="address" class="label-text text-gray-700">Address</label>
                            <input type="text" id="address" name="address" class="input-field" value="<?php echo htmlspecialchars($userInfo['address']); ?>" required />
                        </div>
                        <div id="map" style="height: 300px; margin-top: 10px"></div>
                        <?php if ($user_role === 'doctor') : ?>
                            <!-- Consultation Rate Field -->
                            <div class="form-element">
                                <label for="consultation_rate" class="label-text text-gray-700">Consultation Rate</label>
                                <input type="number" id="consultation_rate" name="consultation_rate" class="input-field"
                                    value="<?php echo htmlspecialchars($consultationRate); ?>" required />
                            </div>

                            <!-- Clinic Hours Fields -->
                            <div class="form-element">
                                <label for="clinic_open_time" class="label-text text-gray-700">Clinic Opening Time</label>
                                <input type="time" id="clinic_open_time" name="clinic_open_time" class="input-field"
                                    value="<?php echo htmlspecialchars($clinicHours['clinic_open_time']); ?>" required />
                            </div>
                            <div class="form-element">
                                <label for="clinic_close_time" class="label-text text-gray-700">Clinic Closing Time</label>
                                <input type="time" id="clinic_close_time" name="clinic_close_time" class="input-field"
                                    value="<?php echo htmlspecialchars($clinicHours['clinic_close_time']); ?>" required />
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user_role === 'doctor') : ?>
                    <div class="form-element">
                        <label for="specialization_id" class="label-text text-gray-700">Specializations</label>
                        <select multiple id="specialization_id" name="specialization_id[]" class="input-field">
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

                <button type="submit" class="form-button w-full">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
    <script src="assets/js/register_address.js"></script>
</body>

</html>