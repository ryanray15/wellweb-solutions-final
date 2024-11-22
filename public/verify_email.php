<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    // Redirect to login if session is invalid
    header('Location: login.html');
    exit();
}

// Fetch the user's email for the current session
require_once '../config/database.php';
$db = include '../config/database.php';

$userId = $_SESSION['user_id'];
$email = '';

try {
    // Query to fetch the user's email
    $query = $db->prepare("SELECT email FROM users WHERE user_id = ?");
    $query->bind_param("i", $userId);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $email = $result->fetch_assoc()['email'];
    } else {
        // If the user doesn't exist, destroy the session and redirect to login
        session_destroy();
        header('Location: login.html');
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching email for user_id {$userId}: " . $e->getMessage());
    echo "An error occurred. Please try again later.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto mt-20 p-6 max-w-lg bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4 text-center">Email Verification</h1>
        <p class="text-gray-700 mb-4">
            A one-time password (OTP) is required to verify your email address.
        </p>
        <p class="font-semibold">Email:</p>
        <p class="mb-4 text-blue-500"><?php echo htmlspecialchars($email); ?></p>

        <div id="otp-section">
            <!-- Default state: Send OTP button -->
            <button id="sendOtp" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                Send OTP
            </button>
        </div>

        <div id="verify-section" class="hidden">
            <!-- OTP input and Verify button -->
            <input type="text" id="otpInput" class="w-full mb-4 p-2 border border-gray-300 rounded" placeholder="Enter OTP">
            <button id="verifyOtp" class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600">
                Verify
            </button>
        </div>
    </div>

    <script>
        document.getElementById('sendOtp').addEventListener('click', () => {
            // Disable the button to prevent multiple clicks
            const sendOtpButton = document.getElementById('sendOtp');
            sendOtpButton.disabled = true;

            axios.post('/api/send_otp.php', {
                    email: "<?php echo $email; ?>"
                })
                .then((response) => {
                    if (response.data.status === 200) {
                        alert('OTP sent to your email.');
                        // Show the verify section
                        document.getElementById('otp-section').classList.add('hidden');
                        document.getElementById('verify-section').classList.remove('hidden');
                    } else {
                        alert(response.data.message || 'Failed to send OTP.');
                        sendOtpButton.disabled = false;
                    }
                })
                .catch((error) => {
                    alert('An error occurred while sending the OTP.');
                    console.error(error);
                    sendOtpButton.disabled = false;
                });
        });

        document.getElementById('verifyOtp').addEventListener('click', () => {
            const otpInput = document.getElementById('otpInput').value;

            axios.post('/api/verify_otp.php', {
                    email: "<?php echo $email; ?>",
                    otp: otpInput
                })
                .then((response) => {
                    if (response.data.status === 200) {
                        alert('Email verified successfully.');
                        // Redirect to the dashboard
                        window.location.href = '/dashboard.php';
                    } else {
                        alert(response.data.message || 'Failed to verify OTP.');
                    }
                })
                .catch((error) => {
                    alert('An error occurred while verifying the OTP.');
                    console.error(error);
                });
        });
    </script>
</body>

</html>