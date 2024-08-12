// Function to check if the user is logged in
function checkUserSession() {
  return fetch("/api/get_session.php")
    .then((response) => response.json())
    .then((data) => {
      if (!data.status || !data.user_id) {
        // Redirect to login page if user is not logged in
        // window.location.href = "/login.html"; // Ensure this line is uncommented
      }
      return data;
    })
    .catch((error) => {
      console.error("Error fetching session data:", error);
      // Optionally handle error, possibly redirect
      return { status: false };
    });
}

document.addEventListener("DOMContentLoaded", function () {
  // Check if user is logged in right after DOMContentLoaded
  checkUserSession().then((sessionData) => {
    if (sessionData.status && sessionData.user_id) {
      // User is logged in, proceed with fetching data
      const patient_id = sessionData.user_id;

      // Function to fetch and populate doctors dropdown
      function fetchDoctors() {
        fetch("/api/get_doctors.php")
          .then((response) => response.json())
          .then((data) => {
            const doctorSelect = document.getElementById("doctor_id");
            data.forEach((doctor) => {
              const option = document.createElement("option");
              option.value = doctor.user_id;
              option.text = doctor.name;
              doctorSelect.appendChild(option);
            });
          })
          .catch((error) => console.error("Error fetching doctors:", error));
      }

      // Function to fetch and populate services dropdown
      function fetchServices() {
        fetch("/api/get_services.php")
          .then((response) => response.json())
          .then((data) => {
            const serviceSelect = document.getElementById("service_id");
            data.forEach((service) => {
              const option = document.createElement("option");
              option.value = service.service_id;
              option.text = service.name;
              serviceSelect.appendChild(option);
            });
          })
          .catch((error) => console.error("Error fetching services:", error));
      }

      // Function to fetch and populate appointments dropdown
      function fetchAppointments() {
        fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
          .then((response) => response.json())
          .then((data) => {
            const appointmentSelect = document.getElementById("appointment_id");
            appointmentSelect.innerHTML = ""; // Clear previous options

            data.forEach((appointment) => {
              const option = document.createElement("option");
              option.value = appointment.appointment_id;
              option.text = `Appointment with Dr. ${appointment.doctor_name} on ${appointment.date} at ${appointment.time}`;
              appointmentSelect.appendChild(option);
            });

            // Trigger change event to load the calendar for the selected appointment
            appointmentSelect.dispatchEvent(new Event("change"));
          })
          .catch((error) =>
            console.error("Error fetching appointments:", error)
          );
      }

      // Initialize dropdowns if the elements are present
      if (document.getElementById("doctor_id")) fetchDoctors();
      if (document.getElementById("service_id")) fetchServices();
      if (document.getElementById("appointment_id")) fetchAppointments();

      // Handle Schedule Form submission
      const scheduleForm = document.getElementById("scheduleForm");
      if (scheduleForm) {
        scheduleForm.addEventListener("submit", async (event) => {
          event.preventDefault();

          const doctor_id = document.getElementById("doctor_id").value;
          const service_id = document.getElementById("service_id").value;
          const date = document.getElementById("date").value;
          const time = document.getElementById("time").value;

          const response = await fetch("/api/schedule_appointment.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              patient_id,
              doctor_id,
              service_id,
              date,
              time,
            }),
          });

          const result = await response.json();

          if (result.status) {
            alert("Appointment scheduled successfully!");
          } else {
            alert("Failed to schedule appointment: " + result.message);
          }
        });
      }

      // Handle Reschedule Form submission
      const rescheduleForm = document.getElementById("rescheduleForm");
      if (rescheduleForm) {
        rescheduleForm.addEventListener("submit", async (event) => {
          event.preventDefault();

          const appointment_id =
            document.getElementById("appointment_id").value;
          const date = document.getElementById("date").value;
          const time = document.getElementById("time").value;

          const response = await fetch("/api/reschedule_appointment.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              appointment_id,
              date,
              time,
            }),
          });

          const result = await response.json();

          if (result.status) {
            alert("Appointment rescheduled successfully!");
          } else {
            alert("Failed to reschedule appointment: " + result.message);
          }
        });
      }

      // Handle Cancel Form submission
      const cancelForm = document.getElementById("cancelForm");
      if (cancelForm) {
        cancelForm.addEventListener("submit", async (event) => {
          event.preventDefault();

          const appointment_id =
            document.getElementById("appointment_id").value;

          const response = await fetch("/api/cancel_appointment.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({
              appointment_id,
            }),
          });

          const result = await response.json();

          if (result.status) {
            alert("Appointment canceled successfully!");
          } else {
            alert("Failed to cancel appointment: " + result.message);
          }
        });
      }

      // If user is an admin, load dashboard data
      if (sessionData.role === "admin") {
        loadAdminDashboard();
      }
    }
  });

  // Load admin dashboard data
  function loadAdminDashboard() {
    fetch("/api/get_dashboard_stats.php")
      .then((response) => response.json())
      .then((data) => {
        document.getElementById("totalPatients").textContent =
          data.totalPatients || "0";
        document.getElementById("totalDoctors").textContent =
          data.totalDoctors || "0";
        document.getElementById("pendingVerifications").textContent =
          data.pendingVerifications || "0";

        loadUsersTable();
        loadVerificationTable();
      })
      .catch((error) => console.error("Error loading dashboard stats:", error));
  }

  // Function to load users table
  function loadUsersTable() {
    fetch("/api/admin_get_users.php")
      .then((response) => response.json())
      .then((data) => {
        const usersTableBody = document.getElementById("usersTableBody");
        usersTableBody.innerHTML = data
          .map(
            (user) =>
              `<tr>
                  <td class="border-b border-gray-200 px-4 py-2">${user.user_id}</td>
                  <td class="border-b border-gray-200 px-4 py-2">${user.name}</td>
                  <td class="border-b border-gray-200 px-4 py-2">${user.email}</td>
                  <td class="border-b border-gray-200 px-4 py-2">${user.role}</td>
                  <td class="border-b border-gray-200 px-4 py-2">
                    <button class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded" onclick="deleteUser(${user.user_id})">Delete</button>
                  </td>
              </tr>`
          )
          .join("");
      })
      .catch((error) => console.error("Error fetching users:", error));
  }

  // Function to load verification table
  function loadVerificationTable() {
    fetch("/api/get_pending_verifications.php")
      .then((response) => response.json())
      .then((data) => {
        const verificationTableBody = document.getElementById(
          "verificationTableBody"
        );
        verificationTableBody.innerHTML = data
          .map(
            (verification) =>
              `<tr>
                  <td class="border-b border-gray-200 px-4 py-2">${verification.user_id}</td>
                  <td class="border-b border-gray-200 px-4 py-2">${verification.doctor_name}</td>
                  <td class="border-b border-gray-200 px-4 py-2">${verification.status}</td>
                  <td class="border-b border-gray-200 px-4 py-2">
                    <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded" onclick="verifyDoctor(${verification.user_id})">Verify</button>
                  </td>
              </tr>`
          )
          .join("");
      })
      .catch((error) =>
        console.error("Error fetching pending verifications:", error)
      );
  }

  // Function to delete user
  function deleteUser(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
      fetch(`/api/admin_delete_user.php?user_id=${userId}`, {
        method: "DELETE",
      })
        .then((response) => response.json())
        .then((data) => {
          alert(data.message);
          if (data.status) {
            loadUsersTable(); // Reload table after deletion
          }
        })
        .catch((error) => console.error("Error deleting user:", error));
    }
  }

  // Function to verify doctor
  function verifyDoctor(userId) {
    if (confirm("Are you sure you want to verify this doctor?")) {
      fetch(`/api/verify_doctors.php?user_id=${userId}`, {
        method: "POST",
      })
        .then((response) => response.json())
        .then((data) => {
          alert(data.message);
          if (data.status) {
            loadVerificationTable(); // Reload table after verification
          }
        })
        .catch((error) => console.error("Error verifying doctor:", error));
    }
  }

  // Register Form Handling
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
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
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((result) => {
          alert(result.message); // Ensure the alert is shown
          if (result.status) {
            window.location.href = "/login.html";
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again."); // Alert in case of an error
        });
    });
  }

  // Login Form Handling
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const formData = new FormData(loginForm);
      const data = {
        email: formData.get("email"),
        password: formData.get("password"),
      };

      fetch("/api/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((result) => {
          alert(result.message); // Ensure the alert is shown
          if (result.status) {
            // Store user data in session
            sessionStorage.setItem("user_id", result.user_id);
            sessionStorage.setItem("role", result.role);

            // Redirect to dashboard or home page after successful login
            window.location.href = "/dashboard.php"; // Assuming a dashboard page
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again."); // Alert in case of an error
        });
    });
  }

  // Logic for dashboard interactions
  const patient_id = sessionStorage.getItem("user_id"); // Get patient ID from session storage

  // Fetch appointments and update the dashboard
  if (patient_id) {
    fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
      .then((response) => response.json())
      .then((data) => {
        if (data.length > 0) {
          document.getElementById("rescheduleMessage").textContent =
            "You have scheduled appointments.";
          document.getElementById("cancelMessage").textContent =
            "You have scheduled appointments.";

          document
            .getElementById("rescheduleButton")
            .classList.remove("hidden");
          document.getElementById("cancelButton").classList.remove("hidden");
        } else {
          document.getElementById("rescheduleMessage").textContent =
            "No appointments scheduled.";
          document.getElementById("cancelMessage").textContent =
            "No appointments scheduled.";
        }
      })
      .catch((error) => console.error("Error fetching appointments:", error));
  }

  // Logout functionality
  const logoutButton = document.getElementById("logout");
  if (logoutButton) {
    logoutButton.addEventListener("click", () => {
      fetch("/api/logout.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.status) {
            // Clear client-side session data
            sessionStorage.removeItem("user_id");
            sessionStorage.removeItem("role");
            // Redirect to index.php or login.html
            window.location.href = "/index.php";
          } else {
            alert("Failed to log out. Please try again.");
          }
        })
        .catch((error) => console.error("Error:", error));
    });
  }
});
