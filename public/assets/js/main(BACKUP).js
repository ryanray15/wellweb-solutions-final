// src/assets/js/main.js

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
                    // Redirect to dashboard or home page after successful login
                    window.location.href = "/dashboard.html"; // Assuming a dashboard page
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again."); // Alert in case of an error
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', () => {
    // Fetch doctors and populate the dropdown
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
        });

    // Fetch services and populate the dropdown
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
        });

    const scheduleForm = document.getElementById('scheduleForm');

    scheduleForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const patient_id = 1; // Replace with the actual patient ID, for example, from a logged-in user session
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
});

document.addEventListener('DOMContentLoaded', () => {
    const patient_id = 1; // Replace with the actual patient ID, for example, from a logged-in user session

    // Fetch appointments and populate the dropdown
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
        });

    const rescheduleForm = document.getElementById('rescheduleForm');

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
});

document.addEventListener('DOMContentLoaded', () => {
    const patient_id = 1; // Replace with the actual patient ID, for example, from a logged-in user session

    // Fetch appointments and populate the dropdown
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
        });

    const cancelForm = document.getElementById('cancelForm');

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
});
