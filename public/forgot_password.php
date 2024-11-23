<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/tailwind.css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body class="bg-gray-100">
    <div class="container mx-auto mt-20 p-6 max-w-lg bg-white rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4 text-center">Reset Your Password</h1>
        <p class="text-gray-700 mb-4">
            Please enter your email address to receive a one-time password (OTP) for password reset.
        </p>

        <div id="email-section">
            <!-- Email input and Send OTP button -->
            <input type="email" id="emailInput" class="w-full mb-4 p-2 border border-gray-300 rounded" placeholder="Enter your email">
            <button id="sendOtp" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                Send OTP
            </button>
        </div>

        <div id="verify-section" class="hidden">
            <!-- OTP input, New Password input, and Reset Password button -->
            <input type="text" id="otpInput" class="w-full mb-4 p-2 border border-gray-300 rounded" placeholder="Enter OTP">
            <input type="password" id="newPassword" class="w-full mb-4 p-2 border border-gray-300 rounded hidden" placeholder="Enter new password">
            <button id="resetPassword" class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600">
                Verify OTP
            </button>
        </div>
    </div>

    <script>
        document.getElementById('sendOtp').addEventListener('click', () => {
            const emailInput = document.getElementById('emailInput').value.trim();

            if (!emailInput) {
                alert("Please enter a valid email address.");
                return;
            }

            // Disable the button to prevent multiple clicks
            const sendOtpButton = document.getElementById('sendOtp');
            sendOtpButton.disabled = true;

            axios.post('/api/send_otp.php', {
                    email: emailInput
                })
                .then((response) => {
                    if (response.data.status === 200) {
                        alert('OTP sent to your email.');
                        // Show the verify section
                        document.getElementById('email-section').classList.add('hidden');
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

        document.getElementById('resetPassword').addEventListener('click', () => {
            const emailInput = document.getElementById('emailInput').value.trim();
            const otpInputField = document.getElementById('otpInput');
            const otpInput = otpInputField ? otpInputField.value.trim() : null;
            const newPasswordInput = document.getElementById('newPassword');

            if (newPasswordInput.classList.contains('hidden')) {
                if (!otpInput) {
                    alert("Please enter the OTP.");
                    return;
                }

                axios.post('/api/verify_otp.php', {
                        email: emailInput,
                        otp: otpInput
                    })
                    .then((response) => {
                        if (response.data.status === 200) {
                            alert("OTP verified successfully. Please set your new password.");
                            otpInputField.remove(); // Remove OTP field
                            newPasswordInput.classList.remove('hidden'); // Show new password input
                            document.getElementById('resetPassword').textContent = 'Set New Password';
                        } else {
                            alert(response.data.message || 'Invalid or expired OTP.');
                        }
                    })
                    .catch((error) => {
                        alert('An error occurred while verifying the OTP.');
                        console.error(error);
                    });
            } else {
                const newPassword = newPasswordInput.value.trim();

                if (!newPassword) {
                    alert("Please enter your new password.");
                    return;
                }

                axios.post('/api/forgot_password.php', {
                        email: emailInput,
                        new_password: newPassword
                    })
                    .then((response) => {
                        if (response.data.status === 200) {
                            alert("Password reset successful! Redirecting to login...");
                            window.location.href = '/login.html';
                        } else {
                            alert(response.data.message || 'Failed to reset password.');
                        }
                    })
                    .catch((error) => {
                        alert('An error occurred while resetting the password.');
                        console.error(error);
                    });
            }
        });
    </script>
</body>

</html>