<?php
session_start();

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messaging - Wellweb</title>
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        #messages {
            height: 400px;
            overflow-y: scroll;
        }

        .message-sent {
            text-align: right;
        }

        .message-received {
            text-align: left;
        }

        .message-container {
            padding: 10px;
            border-radius: 10px;
            margin: 5px;
        }

        .message-sent .message-container {
            background-color: #3b82f6;
            color: white;
        }

        .message-received .message-container {
            background-color: #f3f4f6;
            color: black;
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
    <nav class="w-full transparent-bg shadow-md p-1 fixed top-0 left-0 z-50">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <img src="img/wellwebsolutions-logo.png" alt="Icon" class="h-10 w-auto sm:h-10 md:h-14">
                <a href="index.php"><span class="text-blue-400 text-2xl font-bold">WELL WEB SOLUTIONS</span></a>
            </div>
            <div class="relative">
                <button id="profileDropdown" class="text-blue-400 focus:outline-none">
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

    <!-- Main Content -->
    <div class="container mx-auto mt-28 px-6 py-8">
        <div class="grid grid-cols-3 gap-4">
            <?php if ($user_role === 'patient') : ?>
                <!-- User Dropdown -->
                <div class="col-span-1 bg-white p-4 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold mb-4 text-blue-700">Select Doctor</h2>
                    <select id="userDropdown" class="w-full border rounded-lg p-2">
                        <option value="">Select a doctor...</option>
                    </select>
                </div>
            <?php endif; ?>
            <?php if ($user_role === 'doctor') : ?>
                <!-- User Dropdown -->
                <div class="col-span-1 bg-white p-4 rounded-lg shadow-lg">
                    <h2 class="text-xl font-bold mb-4 text-blue-700">Select Patient</h2>
                    <select id="userDropdown" class="w-full border rounded-lg p-2">
                        <option value="">Select a patient...</option>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Messaging Section -->
            <div class="col-span-2 bg-white p-4 rounded-lg shadow-lg">
                <h2 class="text-xl font-bold mb-4 text-blue-700">Messages</h2>
                <div id="messages" class="border p-4 rounded-lg">
                    <!-- Messages will appear here -->
                </div>
                <div class="mt-4 flex">
                    <input id="messageInput" type="text" placeholder="Type your message..." class="flex-grow p-2 border rounded-lg">
                    <button id="sendMessage" class="ml-2 bg-blue-500 text-white px-4 py-2 rounded-lg">Send</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const currentUserId = <?php echo $_SESSION['user_id'] ?? 'null'; ?>;
        let selectedUserId = null;
        let socket;

        document.addEventListener("DOMContentLoaded", () => {
            connectWebSocket();
            fetchUsers();

            // Load on page refresh
            selectedUserId = sessionStorage.getItem("selectedUserId") || null;

            if (selectedUserId) {
                fetchMessages(selectedUserId); // Fetch messages for the selected user on page load
            }

            document.getElementById("sendMessage").addEventListener("click", sendMessage);

            document.getElementById("userDropdown").addEventListener("change", (event) => {
                selectedUserId = event.target.value;
                sessionStorage.setItem("selectedUserId", selectedUserId);

                if (selectedUserId) {
                    fetchMessages(selectedUserId); // Fetch messages when a new user is selected
                }
            });
        });

        function connectWebSocket() {
            socket = new WebSocket("ws://localhost:8080");

            socket.onopen = () => {
                console.log("WebSocket connection established.");
                socket.send(
                    JSON.stringify({
                        type: "init",
                        userId: currentUserId,
                    })
                );
            };

            socket.onmessage = (event) => {
                console.log("WebSocket raw event received:", event);

                const data = JSON.parse(event.data); // Parse the WebSocket message
                console.log("WebSocket parsed message received:", data);

                if (data.type === "message") {
                    console.log("Processing incoming message:", data);

                    // Determine if the message is relevant to the current conversation
                    const isRelevantMessage =
                        (String(data.senderId) === String(selectedUserId) && String(data.receiverId) === String(currentUserId)) ||
                        (String(data.senderId) === String(currentUserId) && String(data.receiverId) === String(selectedUserId));

                    if (!isRelevantMessage) {
                        console.log(
                            "Message not relevant to the selected conversation. Ignored.", {
                                senderId: data.senderId,
                                receiverId: data.receiverId,
                                currentUserId,
                                selectedUserId,
                            }
                        );
                    } else {
                        const type = data.senderId === currentUserId ? "message-sent" : "message-received";
                        appendMessage(data.content, type);
                    }
                }
            };

            socket.onclose = () => {
                console.log("WebSocket connection closed. Attempting to reconnect...");
                setTimeout(connectWebSocket, 1000); // Reconnect after 1 second
            };

            socket.onerror = (error) => {
                console.error("WebSocket error:", error);
            };
        }

        function fetchUsers() {
            <?php if ($user_role === 'patient') : ?>
                fetch("/api/get_users.php")
                    .then((response) => response.json())
                    .then((data) => {
                        const userDropdown = document.getElementById("userDropdown");
                        userDropdown.innerHTML = '<option value="">Select a doctor...</option>';

                        data.users.forEach((user) => {
                            const option = document.createElement("option");
                            option.value = user.user_id;
                            option.textContent = user.name;
                            userDropdown.appendChild(option);
                        });
                    })
                    .catch((error) => console.error("Error fetching users:", error));
            <?php endif; ?>
            <?php if ($user_role === 'doctor') : ?>
                fetch("/api/get_users_for_doctor.php")
                    .then((response) => response.json())
                    .then((data) => {
                        const userDropdown = document.getElementById("userDropdown");
                        userDropdown.innerHTML = '<option value="">Select a patient...</option>';

                        data.users.forEach((user) => {
                            const option = document.createElement("option");
                            option.value = user.user_id;
                            option.textContent = user.name;
                            userDropdown.appendChild(option);
                        });
                    })
                    .catch((error) => console.error("Error fetching users:", error));
            <?php endif; ?>
        }

        function fetchMessages(userId) {
            fetch(`/api/get_messages.php?with_user_id=${userId}`)
                .then((response) => response.json())
                .then((data) => {
                    const messagesDiv = document.getElementById("messages");
                    messagesDiv.innerHTML = "";

                    data.messages.forEach((message) => {
                        const type = message.sender_id === currentUserId ? "message-sent" : "message-received";
                        appendMessage(message.content, type);
                    });

                    messagesDiv.scrollTop = messagesDiv.scrollHeight;
                })
                .catch((error) => console.error("Error fetching messages:", error));
        }

        function sendMessage() {
            const messageInput = document.getElementById("messageInput");
            const content = messageInput.value;

            if (!content || !selectedUserId) {
                alert("Please select a user and type a message.");
                return;
            }

            const messageData = {
                type: "message",
                senderId: currentUserId,
                receiverId: selectedUserId,
                content: content,
            };

            console.log("Sending message:", messageData);
            socket.send(JSON.stringify(messageData));

            appendMessage(content, "message-sent");
            messageInput.value = "";
        }

        function appendMessage(content, type) {
            console.log("Appending message to DOM:", content, "Type:", type);

            const messagesDiv = document.getElementById("messages");

            const messageDiv = document.createElement("div");
            messageDiv.classList.add(type);

            const messageContent = document.createElement("div");
            messageContent.classList.add("message-container");
            messageContent.textContent = content;

            messageDiv.appendChild(messageContent);
            messagesDiv.appendChild(messageDiv);

            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        }
    </script>
    <script src="assets/js/utils.js"></script>
    <script src="assets/js/common.js"></script>
</body>

</html>