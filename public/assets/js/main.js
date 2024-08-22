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

// Fetch Doctors based on specialization
function fetchDoctors(specializationId) {
  fetch(`/api/get_doctors.php?specialization_id=${specializationId}`)
    .then((response) => response.json())
    .then((data) => {
      const doctorsContainer = document.getElementById("doctorsContainer");
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

// Fetch Services
function fetchServices() {
  fetch("/api/get_services.php")
    .then((response) => response.json())
    .then((data) => {
      const serviceSelect = document.getElementById("service_id");
      serviceSelect.innerHTML = data
        .map(
          (service) => `<option value="${service.id}">${service.name}</option>`
        )
        .join("");
    })
    .catch((error) => console.error("Error fetching services:", error));
}

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
  // Check if user is logged in right after DOMContentLoaded
  checkUserSession().then((sessionData) => {
    if (sessionData.status && sessionData.user_id) {
      const patient_id = sessionData.user_id;

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
  });

  // Load specializations and services for the new schedule functionality
  fetchSpecializations();
  fetchServices();

  // Set up event listeners for specialization and doctor selection
  const specializationSelect = document.getElementById("specialization_id");
  specializationSelect.addEventListener("change", function () {
    fetchDoctors(this.value);
  });

  // Hide the grid container and show the appointment scheduler after doctor selection
  const doctorGridContainer = document.getElementById("doctorGridContainer");
  const appointmentScheduler = document.getElementById("appointmentScheduler");
  doctorGridContainer.classList.remove("hidden");
  appointmentScheduler.classList.add("hidden");
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
