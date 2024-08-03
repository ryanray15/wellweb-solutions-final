<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cancel Appointment</title>
    <link href="assets/css/tailwind.css" rel="stylesheet" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto mt-10 max-w-2xl p-8 bg-white rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold text-green-600 mb-8 text-center">
            Cancel Appointment
        </h1>
        <form id="cancelForm" class="w-full">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="appointment_id">Select Appointment</label>
                <select class="shadow border rounded-lg w-full py-2 px-3 text-gray-700 focus:outline-none focus:border-green-500" id="appointment_id">
                    <!-- Example Options -->
                    <!-- <option value="1">Appointment with Dr. John Doe on 2024-08-10 at 10:00 AM</option>
                    <option value="2">Appointment with Dr. Jane Smith on 2024-08-11 at 11:00 AM</option> -->
                </select>
            </div>
            <button class="w-full bg-red-600 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200" type="submit">
                Cancel Appointment
            </button>
        </form>
    </div>
    <script src="assets/js/main.js"></script>
</body>

</html>