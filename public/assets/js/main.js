document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(registerForm);
            const data = {
                name: formData.get("name"),
                email: formData.get("email"),
                password: formData.get("password"),
                role: formData.get("role"),
            };

            fetch("/api/register.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message); // Ensure the alert is shown
                if (result.status) {
                    window.location.href = "/login.html";
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again."); // Alert in case of an error
            });
        });
    }

    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(loginForm);
            const data = {
                email: formData.get("email"),
                password: formData.get("password"),
            };

            fetch("/api/login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message); // Ensure the alert is shown
                if (result.status) {
                    // Store user data in session
                    sessionStorage.setItem('user_id', result.user_id);
                    sessionStorage.setItem('role', result.role);

                    // Redirect to dashboard or home page after successful login
                    window.location.href = "/dashboard.php"; // Assuming a dashboard page
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again."); // Alert in case of an error
            });
        });
    }

    // Check for user session
    function checkUserSession() {
        return fetch('/api/get_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.user_id) {
                    // Redirect to login if not logged in
                    // window.location.href = "/login.html"; // TODO: Problematic line of code
                }
                return data;
            })
            .catch(error => console.error('Error fetching session data:', error));
    }

    // Appointment Scheduling Scripts
    checkUserSession().then(sessionData => {
        if (sessionData.user_id) {
            const patient_id = sessionData.user_id;

            // Function to fetch and populate doctors dropdown
            function fetchDoctors() {
                fetch('http://doctor-appointment.local/api/get_doctors.php')
                    .then(response => response.json())
                    .then(data => {
                        const doctorSelect = document.getElementById('doctor_id');
                        data.forEach(doctor => {
                            const option = document.createElement('option');
                            option.value = doctor.user_id;
                            option.text = doctor.name;
                            doctorSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching doctors:', error));
            }

            // Function to fetch and populate services dropdown
            function fetchServices() {
                fetch('http://doctor-appointment.local/api/get_services.php')
                    .then(response => response.json())
                    .then(data => {
                        const serviceSelect = document.getElementById('service_id');
                        data.forEach(service => {
                            const option = document.createElement('option');
                            option.value = service.service_id;
                            option.text = service.name;
                            serviceSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching services:', error));
            }

            // Function to fetch and populate appointments dropdown
            function fetchAppointments() {
                fetch(`http://doctor-appointment.local/api/get_appointments.php?patient_id=${patient_id}`)
                    .then(response => response.json())
                    .then(data => {
                        const appointmentSelect = document.getElementById('appointment_id');
                        data.forEach(appointment => {
                            const option = document.createElement('option');
                            option.value = appointment.appointment_id;
                            option.text = `Appointment on ${appointment.date} at ${appointment.time}`;
                            appointmentSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error fetching appointments:', error));
            }

            // Initialize dropdowns if the elements are present
            if (document.getElementById('doctor_id')) fetchDoctors();
            if (document.getElementById('service_id')) fetchServices();
            if (document.getElementById('appointment_id')) fetchAppointments();

            // Handle Schedule Form submission
            const scheduleForm = document.getElementById('scheduleForm');
            if (scheduleForm) {
                scheduleForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const doctor_id = document.getElementById('doctor_id').value;
                    const service_id = document.getElementById('service_id').value;
                    const date = document.getElementById('date').value;
                    const time = document.getElementById('time').value;

                    const response = await fetch('http://doctor-appointment.local/api/schedule_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            patient_id,
                            doctor_id,
                            service_id,
                            date,
                            time
                        })
                    });

                    const result = await response.json();

                    if (result.status) {
                        alert('Appointment scheduled successfully!');
                    } else {
                        alert('Failed to schedule appointment: ' + result.message);
                    }
                });
            }

            // Handle Reschedule Form submission
            const rescheduleForm = document.getElementById('rescheduleForm');
            if (rescheduleForm) {
                rescheduleForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const appointment_id = document.getElementById('appointment_id').value;
                    const date = document.getElementById('date').value;
                    const time = document.getElementById('time').value;

                    const response = await fetch('http://doctor-appointment.local/api/reschedule_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            appointment_id,
                            date,
                            time
                        })
                    });

                    const result = await response.json();

                    if (result.status) {
                        alert('Appointment rescheduled successfully!');
                    } else {
                        alert('Failed to reschedule appointment: ' + result.message);
                    }
                });
            }

            // Handle Cancel Form submission
            const cancelForm = document.getElementById('cancelForm');
            if (cancelForm) {
                cancelForm.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const appointment_id = document.getElementById('appointment_id').value;

                    const response = await fetch('http://doctor-appointment.local/api/cancel_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            appointment_id
                        })
                    });

                    const result = await response.json();

                    if (result.status) {
                        alert('Appointment canceled successfully!');
                    } else {
                        alert('Failed to cancel appointment: ' + result.message);
                    }
                });
            }
        }
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

    // Logic for dashboard interactions
    const patient_id = sessionStorage.getItem('user_id'); // Get patient ID from session storage

    // Fetch appointments and update the dashboard
    if (patient_id) {
        fetch(`http://doctor-appointment.local/api/get_appointments.php?patient_id=${patient_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    document.getElementById('rescheduleMessage').textContent = 'You have scheduled appointments.';
                    document.getElementById('cancelMessage').textContent = 'You have scheduled appointments.';
                } else {
                    document.getElementById('rescheduleMessage').textContent = 'No appointments scheduled.';
                    document.getElementById('cancelMessage').textContent = 'No appointments scheduled.';
                }
            })
            .catch(error => console.error('Error fetching appointments:', error));
    }
});
