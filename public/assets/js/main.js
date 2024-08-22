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
      return { status: false };
    });
}

// Function to verify or reject doctor
function verifyDoctor(doctorId, action) {
  if (confirm(`Are you sure you want to ${action} this doctor?`)) {
    fetch("/api/verify_doctors.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        action: action,
        doctor_id: doctorId,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.message);
        if (data.status) {
          loadVerificationTable(); // Reload table after verification or rejection
        }
      })
      .catch((error) => console.error(`Error ${action} doctor:`, error));
  }
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
                        <td class="border-b border-gray-200 px-4 py-2">${verification.verification_id}</td>
                        <td class="border-b border-gray-200 px-4 py-2">${verification.doctor_name}</td>
                        <td class="border-b border-gray-200 px-4 py-2">${verification.status}</td>
                        <td class="border-b border-gray-200 px-4 py-2">
                            <a href="${verification.document_path}" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded">View Document</a>
                            <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded" onclick="verifyDoctor(${verification.doctor_id}, 'approve')">Verify</button>
                            <button class="bg-red-500 hover:bg-red-600 text-white font-bold py-1 px-3 rounded" onclick="verifyDoctor(${verification.doctor_id}, 'reject')">Reject</button>
                        </td>
                    </tr>`
        )
        .join("");
    })
    .catch((error) =>
      console.error("Error fetching pending verifications:", error)
    );
}

// Fetch Specializations
function fetchSpecializations() {
  fetch("/api/get_specializations.php")
    .then((response) => response.json())
    .then((data) => {
      const specializationSelect = document.getElementById("specialization_id");
      specializationSelect.innerHTML = data
        .map((spec) => `<option value="${spec.id}">${spec.name}</option>`)
        .join("");
    })
    .catch((error) => console.error("Error fetching specializations:", error));
}

// // Fetch Services
// function fetchServices() {
//   fetch("/api/get_services.php")
//     .then((response) => response.json())
//     .then((data) => {
//       const serviceSelect = document.getElementById("service_id");
//       serviceSelect.innerHTML = data
//         .map(
//           (service) => `<option value="${service.id}">${service.name}</option>`
//         )
//         .join("");
//     })
//     .catch((error) => console.error("Error fetching services:", error));
// }

// Function to fetch and populate doctors dropdown (original function)
function fetchDoctorsDropdown() {
  fetch("/api/get_doctors.php")
    .then((response) => response.json())
    .then((data) => {
      const doctorSelect = document.getElementById("doctor_id");
      data.forEach((doctor) => {
        const option = document.createElement("option");
        option.value = doctor.user_id;
        option.text = `Dr. ${doctor.name}`; // Updated to use first_name and last_name
        doctorSelect.appendChild(option);
      });
    })
    .catch((error) => console.error("Error fetching doctors:", error));
}

// Attach click handlers to doctor cards
function attachDoctorClickHandlers() {
  const doctorCards = document.querySelectorAll(".doctor-card");
  doctorCards.forEach((card) => {
    card.addEventListener("click", function () {
      const doctorId = this.getAttribute("data-doctor-id");
      showScheduler(doctorId);
    });
  });
}

// Show Calendar and Appointment Scheduler
function showScheduler(doctorId) {
  const doctorGridContainer = document.getElementById("doctorGridContainer");
  const appointmentScheduler = document.getElementById("appointmentScheduler");
  doctorGridContainer.classList.add("hidden");
  appointmentScheduler.classList.remove("hidden");
  loadCalendar(doctorId);
}

// Load Calendar
function loadCalendar(doctorId) {
  fetch(`/api/get_doctor_availability.php?doctor_id=${doctorId}`)
    .then((response) => response.json())
    .then((events) => {
      const calendarEl = document.getElementById("calendar");
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "timeGridWeek",
        events: events,
        headerToolbar: {
          left: "prev,next today",
          center: "title",
          right: "dayGridMonth,timeGridWeek,timeGridDay",
        },
        eventColor: "green",
        eventTextColor: "white",
      });
      calendar.render();
      disableUnavailableSlots(events);
    })
    .catch((error) => console.error("Error loading calendar:", error));
}

function disableUnavailableSlots(events) {
  const dateInput = document.getElementById("date");
  const timeInput = document.getElementById("time");
  const unavailableDays = events
    .filter((event) => event.allDay && event.title === "Not Available")
    .map((event) => event.start.split("T")[0]);
  const unavailableTimes = events
    .filter((event) => !event.allDay && event.title === "Not Available")
    .map((event) => ({
      date: event.start.split("T")[0],
      start: event.start.split("T")[1],
      end: event.end.split("T")[1],
    }));

  dateInput.addEventListener("change", function () {
    const selectedDate = this.value;
    if (unavailableDays.includes(selectedDate)) {
      alert(
        "This date is unavailable for any appointments. Please choose another date."
      );
      this.value = "";
    }
  });

  timeInput.addEventListener("change", function () {
    const selectedDate = dateInput.value;
    const selectedTime = this.value;

    const isUnavailable = unavailableTimes.some(
      (unavailable) =>
        unavailable.date === selectedDate &&
        unavailable.start <= selectedTime &&
        unavailable.end > selectedTime
    );

    if (isUnavailable) {
      alert("This time slot is unavailable. Please choose another time.");
      this.value = "";
    }
  });
}

document.addEventListener("DOMContentLoaded", function () {
  // Register Form Handling
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const formData = new FormData(registerForm);
      const data = {
        first_name: formData.get("first_name"),
        middle_initial: formData.get("middle_initial"),
        last_name: formData.get("last_name"),
        email: formData.get("email"),
        password: formData.get("password"),
        role: formData.get("role"),
        contact_number: formData.get("contact_number"),
        address: formData.get("address"),
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

  // Define function to load doctor dashboard
  function loadDoctorDashboard(user_id) {
    // Load doctor dashboard functionalities here
  }

  // Define function to load patient dashboard
  function loadPatientDashboard(user_id) {
    // Load patient dashboard functionalities here
  }

  // Check if user is logged in right after DOMContentLoaded
  checkUserSession().then((sessionData) => {
    if (sessionData.status && sessionData.user_id) {
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
              option.text = `Dr. ${doctor.name}`; // Updated to use first_name and last_name
              doctorSelect.appendChild(option);
            });
          })
          .catch((error) => console.error("Error fetching doctors:", error));
      }

      // Fetch Doctors based on specialization
      function fetchDoctors(specializationId) {
        fetch(`/api/get_doctors.php?specialization_id=${specializationId}`)
          .then((response) => response.json())
          .then((data) => {
            const doctorsContainer =
              document.getElementById("doctorsContainer");
            doctorsContainer.innerHTML = data
              .map(
                (doctor) => `
                <div class="doctor-card p-4 border rounded-lg hover:bg-gray-100 cursor-pointer" data-doctor-id="${
                  doctor.user_id
                }">
                    <img src="${
                      doctor.image || "/path/to/default-icon.png"
                    }" alt="Doctor Image" class="w-16 h-16 rounded-full mx-auto">
                    <h3 class="text-center text-lg font-bold mt-2">${
                      doctor.name
                    }</h3>
                    <p class="text-center text-gray-600">${
                      doctor.specialization
                    }</p>
                    <p class="text-center text-gray-600">${doctor.address}</p>
                </div>
            `
              )
              .join("");
            attachDoctorClickHandlers();
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
              option.text = `Appointment with Dr. ${appointment.doctor_name} on ${appointment.date} at ${appointment.time}`; // Updated to use first_name and last_name
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
      if (document.getElementById("doctor_id")) fetchDoctorsDropdown();
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

    if (sessionData.status && sessionData.user_id) {
      // User is logged in, proceed with fetching data
      const user_id = sessionData.user_id;
      const user_role = sessionData.role;

      // Handle restricted access for doctors
      if (user_role === "doctor") {
        fetch(`/api/check_verification_status.php?user_id=${user_id}`)
          .then((response) => response.json())
          .then((data) => {
            if (!data.is_verified) {
              if (!data.documents_submitted) {
                document.querySelector(".dashboard-content").innerHTML = `
                                    <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                                        <h1 class="text-3xl font-bold text-red-600">Restricted Access</h1>
                                        <p class="mt-4 text-gray-700">Please <a href="upload_documents.php" class="text-green-600 hover:underline">submit your documents</a> for verification.</p>
                                    </div>
                                `;
              } else {
                document.querySelector(".dashboard-content").innerHTML = `
                                    <div class="bg-white p-8 rounded-lg shadow-lg text-center">
                                        <h1 class="text-3xl font-bold text-red-600">Restricted Access</h1>
                                        <p class="mt-4 text-gray-700">Your account is currently pending verification. You will be notified once your account has been verified.</p>
                                    </div>
                                `;
              }
            } else {
              // Load doctor functionalities if verified
              loadDoctorDashboard(user_id);
            }
          })
          .catch((error) =>
            console.error("Error checking verification status:", error)
          );
      }

      // Load admin dashboard if user is admin
      if (user_role === "admin") {
        loadAdminDashboard();
      }

      // Load patient dashboard if user is patient
      if (user_role === "patient") {
        loadPatientDashboard(user_id);
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

  // Load specializations and services for the new schedule functionality
  fetchSpecializations();
  fetchServices();

  // Handle Role Selection and Specialization Handling in Registration Form
  const roleSelect = document.getElementById("role");
  const specializationContainer = document.getElementById(
    "specialization-container"
  );
  const specializationSelect = document.getElementById("specialization");
  const addressLabel = document.getElementById("addressLabel");

  if (roleSelect) {
    function fetchSpecializationsForRegistration() {
      fetch("/api/get_specializations.php")
        .then((response) => response.json())
        .then((data) => {
          specializationSelect.innerHTML = ""; // Clear previous options
          if (data.length > 0) {
            data.forEach((spec) => {
              const option = document.createElement("option");
              option.value = spec.id;
              option.text = spec.name;
              specializationSelect.appendChild(option);
            });
          } else {
            specializationSelect.innerHTML =
              "<option value=''>No specializations available</option>";
          }
        })
        .catch((error) =>
          console.error("Error fetching specializations:", error)
        );
    }

    roleSelect.addEventListener("change", function () {
      if (this.value === "doctor") {
        specializationContainer.classList.remove("hidden");
        fetchSpecializationsForRegistration();
        addressLabel.textContent = "Clinic Address:";
      } else {
        specializationContainer.classList.add("hidden");
        specializationSelect.innerHTML = ""; // Clear the specializations if not a doctor
        addressLabel.textContent = "Address:";
      }
    });

    // Initial check to set up the form if the role is pre-selected (e.g., from back navigation)
    if (roleSelect.value === "doctor") {
      specializationContainer.classList.remove("hidden");
      fetchSpecializationsForRegistration();
    }
  }

  // Hide the grid container and show the appointment scheduler after doctor selection
  const doctorGridContainer = document.getElementById("doctorGridContainer");
  const appointmentScheduler = document.getElementById("appointmentScheduler");
  doctorGridContainer.classList.remove("hidden");
  appointmentScheduler.classList.add("hidden");
});

// Profile Dropdown
const profileDropdown = document.getElementById("profileDropdown");
const dropdownMenu = document.getElementById("dropdownMenu");

profileDropdown.addEventListener("click", () => {
  dropdownMenu.classList.toggle("hidden");
});

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
          sessionStorage.removeItem("user_id");
          sessionStorage.removeItem("role");
          window.location.href = "/index.php";
        } else {
          alert("Failed to log out. Please try again.");
        }
      })
      .catch((error) => console.error("Error:", error));
  });
}

// Logic for dashboard interactions
const patient_id = sessionStorage.getItem("user_id");
if (patient_id) {
  fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
    .then((response) => response.json())
    .then((data) => {
      if (data.length > 0) {
        document.getElementById("rescheduleMessage").textContent =
          "You have scheduled appointments.";
        document.getElementById("cancelMessage").textContent =
          "You have scheduled appointments.";
        document.getElementById("rescheduleButton").classList.remove("hidden");
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
