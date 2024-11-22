<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.html');
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Chat Room</title>
    <!-- Include Tailwind CSS -->
    <link href="assets/css/tailwind.css" rel="stylesheet">
    <!-- Include VideoSDK JS library -->
    <script src="https://sdk.videosdk.live/js-sdk/0.0.82/videosdk.js"></script>
    <!-- Include config.js for TOKEN -->
    <script src="assets/js/config.js"></script> <!-- Adjust the path accordingly -->
</head>

<body class="bg-gray-900 text-white flex items-center justify-center min-h-screen">

    <div class="container mx-auto p-4 text-center">
        <h1 class="text-3xl font-bold mb-8">Welcome to Your Video Consultation</h1>

        <!-- Loading Screen for Joining -->
        <div id="join-screen" class="text-center">
            <p class="text-lg">Joining the meeting...</p>
        </div>

        <!-- Main Video Chat Area -->
        <div id="grid-screen" class="hidden">
            <!-- Meeting ID Heading -->
            <h3 id="meetingIdHeading" class="text-lg font-semibold mb-4"></h3>

            <!-- Video Container -->
            <div id="videoContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4 mb-6">
                <!-- Video frames will be dynamically appended here -->
            </div>

            <!-- Control Buttons -->
            <div class="flex justify-center space-x-4 mt-4">
                <button id="toggleMicBtn" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded font-semibold">
                    Toggle Mic
                </button>
                <button id="toggleWebCamBtn" class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded font-semibold">
                    Toggle Camera
                </button>
                <button id="leaveBtn" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded font-semibold">
                    Leave Room
                </button>
            </div>
        </div>
    </div>

    <script>
        let meeting = null;
        let isMicOn = true;
        let isWebCamOn = true;
        let doctorId = null; // Define doctorId globally
        let patientId = null; // Define patientId globally
        const videoContainer = document.getElementById("videoContainer");

        document.addEventListener("DOMContentLoaded", () => {
            const urlParams = new URLSearchParams(window.location.search);
            const meetingId = urlParams.get('meeting_id');
            const appointmentId = urlParams.get('appointment_id');

            if (meetingId && appointmentId) {
                fetch(`/api/get_appointment_details.php?appointment_id=${appointmentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Extract doctor_id and patient_id from the response
                            const {
                                doctor_id,
                                patient_id
                            } = data;

                            // Assign them to global variables
                            doctorId = doctor_id;
                            patientId = patient_id;

                            initializeMeeting(meetingId);
                            logVideoCall(meetingId, 'start', doctor_id, patient_id); // Log start of the call
                        } else {
                            console.error("Failed to retrieve appointment details:", data.message);
                        }
                    });
            } else {
                alert("Meeting ID or Appointment ID is missing.");
            }
        });

        function logVideoCall(meetingId, action, doctorId, patientId) {
            fetch('api/log_video_call.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        meeting_id: meetingId,
                        action: action,
                        doctor_id: doctorId,
                        patient_id: patientId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        console.log(`Video call ${action} recorded successfully.`);
                    } else {
                        console.error(`Failed to record video call ${action}: ${data.message}`);
                    }
                })
                .catch(error => console.error('Error logging video call:', error));
        }

        function initializeMeeting(meetingId) {
            if (typeof TOKEN === 'undefined' || !TOKEN) {
                alert("VideoSDK token is missing. Please check your config.");
                return;
            }

            window.VideoSDK.config(TOKEN);

            meeting = window.VideoSDK.initMeeting({
                meetingId: meetingId,
                name: "<?php echo htmlspecialchars($userInfo['first_name'] . ' ' . $userInfo['last_name']); ?>", // Dynamically embed the PHP variables
                micEnabled: true,
                webcamEnabled: true,
            });

            meeting.join();

            setupMeetingEvents(meeting);

            document.getElementById("meetingIdHeading").textContent = `Meeting ID: ${meetingId}`;
        }

        function setupMeetingEvents(meeting) {
            meeting.on("meeting-joined", () => {
                document.getElementById("join-screen").classList.add("hidden");
                document.getElementById("grid-screen").classList.remove("hidden");
                createLocalParticipant();
            });

            meeting.on("meeting-left", () => {
                const urlParams = new URLSearchParams(window.location.search);
                const meetingId = urlParams.get('meeting_id');
                logVideoCall(meetingId, 'end', doctorId, patientId); // Log end of the call
                videoContainer.innerHTML = "";
            });

            meeting.on("participant-joined", (participant) => {
                let videoElement = createVideoElement(participant.id, participant.displayName);
                let audioElement = createAudioElement(participant.id);

                participant.on("stream-enabled", (stream) => {
                    setTrack(stream, audioElement, participant, false);
                });
                videoContainer.appendChild(videoElement);
                videoContainer.appendChild(audioElement);
            });

            meeting.on("participant-left", (participant) => {
                let vElement = document.getElementById(`f-${participant.id}`);
                if (vElement) vElement.remove();

                let aElement = document.getElementById(`a-${participant.id}`);
                if (aElement) aElement.remove();
            });

            meeting.localParticipant.on("stream-enabled", (stream) => {
                setTrack(stream, null, meeting.localParticipant, true);
            });

            document.getElementById("toggleMicBtn").addEventListener("click", () => {
                if (isMicOn) {
                    meeting.muteMic();
                } else {
                    meeting.unmuteMic();
                }
                isMicOn = !isMicOn;
            });

            document.getElementById("toggleWebCamBtn").addEventListener("click", () => {
                if (isWebCamOn) {
                    meeting.disableWebcam();
                    document.getElementById(`f-${meeting.localParticipant.id}`).style.display = "none";
                } else {
                    meeting.enableWebcam();
                    document.getElementById(`f-${meeting.localParticipant.id}`).style.display = "inline";
                }
                isWebCamOn = !isWebCamOn;
            });

            document.getElementById("leaveBtn").addEventListener("click", () => {
                const urlParams = new URLSearchParams(window.location.search);
                const meetingId = urlParams.get('meeting_id');
                logVideoCall(meetingId, 'end', doctorId, patientId); // Log end when leaving manually
                meeting.leave();
                window.location.href = "/dashboard.php";
            });
        }

        function createVideoElement(pId, name) {
            let videoFrame = document.createElement("div");
            videoFrame.setAttribute("id", `f-${pId}`);
            videoFrame.classList.add("bg-gray-800", "rounded-lg", "p-4", "shadow");

            let videoElement = document.createElement("video");
            videoElement.classList.add("video-frame", "rounded-lg", "w-full", "h-full", "object-cover");
            videoElement.setAttribute("id", `v-${pId}`);
            videoElement.setAttribute("playsinline", true);
            videoElement.setAttribute("autoplay", true);
            videoFrame.appendChild(videoElement);

            let displayName = document.createElement("div");
            displayName.innerHTML = `Name: ${name}`;
            displayName.classList.add("mt-2", "text-sm", "font-semibold");
            videoFrame.appendChild(displayName);

            return videoFrame;
        }

        function createAudioElement(pId) {
            let audioElement = document.createElement("audio");
            audioElement.setAttribute("autoplay", "false");
            audioElement.setAttribute("playsinline", "true");
            audioElement.setAttribute("controls", "false");
            audioElement.setAttribute("id", `a-${pId}`);
            audioElement.style.display = "none";
            return audioElement;
        }

        function createLocalParticipant() {
            let localParticipant = createVideoElement(meeting.localParticipant.id, meeting.localParticipant.displayName);
            videoContainer.appendChild(localParticipant);
        }

        function setTrack(stream, audioElement, participant, isLocal) {
            if (stream.kind === "video") {
                isWebCamOn = true;
                const mediaStream = new MediaStream();
                mediaStream.addTrack(stream.track);
                let videoElm = document.getElementById(`v-${participant.id}`);
                videoElm.srcObject = mediaStream;
                videoElm.play().catch((error) => console.error("videoElm.play() failed", error));
            }
            if (stream.kind === "audio") {
                if (isLocal) {
                    isMicOn = true;
                } else {
                    const mediaStream = new MediaStream();
                    mediaStream.addTrack(stream.track);
                    audioElement.srcObject = mediaStream;
                    audioElement.play().catch((error) => console.error("audioElem.play() failed", error));
                }
            }
        }
    </script>
</body>

</html>