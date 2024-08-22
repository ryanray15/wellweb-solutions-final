// Load the dashboard when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Check user role and load the corresponding dashboard
  checkUserSession().then((sessionData) => {
    if (sessionData.status && sessionData.user_id) {
      if (sessionData.role === "admin") {
        loadAdminDashboard();
        loadSpecializations();

        const addSpecializationForm = document.getElementById(
          "addSpecializationForm"
        );
        if (addSpecializationForm) {
          addSpecializationForm.addEventListener(
            "submit",
            handleAddSpecialization
          );
        }
      } else if (sessionData.role === "doctor") {
        loadDoctorDashboard(sessionData.user_id);
      }
    }
  });
});

// Function to load admin dashboard data
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

// Function to load doctor dashboard data
function loadDoctorDashboard(doctorId) {
  // Fetch and display appointments
  fetch(`/api/get_doctor_appointments.php?doctor_id=${doctorId}`)
    .then((response) => response.json())
    .then((appointments) => {
      const appointmentContainer = document.getElementById(
        "appointmentContainer"
      );
      if (appointments.length > 0) {
        // Render appointment details
        appointmentContainer.innerHTML = appointments
          .map(
            (app) => `
          <div class="appointment-item">
            <p>${app.patient_name} - ${app.date} ${app.time}</p>
          </div>
        `
          )
          .join("");
      } else {
        appointmentContainer.innerHTML = "<p>No appointments available.</p>";
      }
    })
    .catch((error) => console.error("Error fetching appointments:", error));

  // Initialize and load the calendar for setting availability
  loadDoctorCalendar(doctorId);
}

// Function to initialize and load the FullCalendar component for doctors
function loadDoctorCalendar(doctorId) {
  const calendarEl = document.getElementById("calendar");

  if (calendarEl) {
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "timeGridWeek",
      selectable: true,
      timeZone: "Asia/Manila", // Adjust to your local timezone
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
      },
      events: {
        url: `/api/get_doctor_availability.php?doctor_id=${doctorId}`, // Adjust API endpoint as needed
        method: "GET",
        failure: function () {
          alert("There was an error while fetching availability!");
        },
      },
      select: function (info) {
        handleSelectEvent(info, doctorId, calendar);
      },
      eventClick: function (info) {
        handleEventClick(info, doctorId, calendar);
      },
    });

    calendar.render();
  } else {
    console.error("Calendar element not found");
  }
}

// Handle the selection of time/date range in the calendar
function handleSelectEvent(info, doctorId, calendar) {
  let status = prompt("Enter 'Available' or 'Not Available'");
  if (status) {
    // Check if the selection is a full day range or specific time range
    const isAllDay = info.allDay || info.view.type === "dayGridMonth";
    const body = isAllDay
      ? `start_date=${info.startStr}&end_date=${info.endStr}&status=${status}&allDay=1`
      : `date=${info.startStr.split("T")[0]}&start_time=${
          info.startStr.split("T")[1]
        }&end_time=${info.endStr.split("T")[1]}&status=${status}`;

    const url = isAllDay
      ? "/api/set_doctor_availability_day_range.php"
      : "/api/set_doctor_availability_time_range.php";

    fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: body,
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.message);
        if (data.status) {
          // Dynamically refetch and update the calendar with new events
          calendar.refetchEvents();
        }
      })
      .catch((error) => console.error("Error updating availability:", error));
  }
}

// Handle clicks on existing calendar events (e.g., to delete availability)
function handleEventClick(info, doctorId, calendar) {
  if (confirm("Do you want to delete this schedule?")) {
    fetch("/api/delete_doctor_availability.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: info.event.id,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.message);
        if (data.status) {
          info.event.remove(); // Remove event from calendar view
        }
      })
      .catch((error) => console.error("Error deleting event:", error));
  }
}

// Function to load specializations and update the UI
function loadSpecializations() {
  fetch("/api/get_specializations.php")
    .then((response) => response.json())
    .then((data) => {
      const specializationList = document.getElementById("specializationList");
      specializationList.innerHTML = ""; // Clear the list

      if (data.length > 0) {
        data.forEach((spec) => {
          const listItem = document.createElement("li");
          listItem.textContent = spec.name;
          listItem.classList.add("mb-2", "flex", "justify-between");

          const deleteButton = document.createElement("button");
          deleteButton.textContent = "Delete";
          deleteButton.classList.add(
            "bg-red-500",
            "hover:bg-red-600",
            "text-white",
            "font-bold",
            "py-1",
            "px-3",
            "rounded"
          );
          deleteButton.addEventListener("click", function () {
            deleteSpecialization(spec.id);
          });

          listItem.appendChild(deleteButton);
          specializationList.appendChild(listItem);
        });
      } else {
        specializationList.textContent = "No specializations found.";
      }
    })
    .catch((error) => console.error("Error fetching specializations:", error));
}

// Function to handle adding a new specialization
function handleAddSpecialization(event) {
  event.preventDefault();

  const specializationName =
    document.getElementById("specializationName").value;

  fetch("/api/admin_manage_specializations.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `name=${encodeURIComponent(specializationName)}`,
  })
    .then((response) => response.json())
    .then((data) => {
      alert(data.message);
      if (data.status) {
        loadSpecializations(); // Reload the list of specializations
        document.getElementById("specializationName").value = ""; // Clear the input field
      }
    })
    .catch((error) => console.error("Error adding specialization:", error));
}

// Function to delete a specialization
function deleteSpecialization(id) {
  if (confirm("Are you sure you want to delete this specialization?")) {
    fetch("/api/admin_manage_specializations.php", {
      method: "DELETE",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },
      body: `id=${encodeURIComponent(id)}`,
    })
      .then((response) => response.json())
      .then((data) => {
        alert(data.message);
        if (data.status) {
          loadSpecializations();
        }
      })
      .catch((error) => console.error("Error deleting specialization:", error));
  }
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
