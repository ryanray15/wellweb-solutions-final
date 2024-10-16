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
      } else {
        loadPatientDashboard(sessionData.user_id);

        // Add search functionality only for patients
        document
          .getElementById("doctorSearchBar")
          .addEventListener("input", function () {
            const query = this.value;

            if (query.length > 2) {
              // Start searching after typing 3 characters
              fetch(
                `/api/search_doctors.php?query=${encodeURIComponent(query)}`
              )
                .then((response) => response.json())
                .then((data) => {
                  const searchResults =
                    document.getElementById("searchResults");
                  searchResults.innerHTML = ""; // Clear previous results
                  if (data.length > 0) {
                    searchResults.classList.remove("hidden");
                    data.forEach((doctor) => {
                      const resultItem = document.createElement("div");
                      resultItem.className =
                        "flex items-center p-3 cursor-pointer hover:bg-gray-200";

                      // Adding image if available, or a placeholder if not
                      const profileImage = document.createElement("img");
                      profileImage.src =
                        doctor.image || "/path/to/default-image.png"; // Adjust the path accordingly
                      profileImage.alt = "Doctor Image";
                      profileImage.className = "w-8 h-8 rounded-full mr-3"; // Small rounded image with margin-right

                      const name = document.createElement("span");
                      name.textContent = `${doctor.name}`;

                      resultItem.appendChild(profileImage);
                      resultItem.appendChild(name);

                      resultItem.addEventListener("click", () => {
                        window.location.href = `/doctor_info.php?doctor_id=${doctor.user_id}`;
                      });
                      searchResults.appendChild(resultItem);
                    });
                  } else {
                    searchResults.classList.add("hidden");
                  }
                });
            } else {
              document.getElementById("searchResults").classList.add("hidden");
            }
          });
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
  // Initialize and load the calendar for displaying availability
  loadDoctorCalendar(doctorId);

  // Ensure button and container elements exist
  const addTimeRangeBtn = document.getElementById("add_time_range"); // Add Time Range button
  const setAvailabilityBtn = document.getElementById("set_availability"); // Set Availability button
  const timeRangesContainer = document.getElementById("time-ranges"); // The container to hold time ranges
  let timeRanges = []; // Array to store time ranges

  if (!addTimeRangeBtn || !setAvailabilityBtn || !timeRangesContainer) {
    console.error(
      "Missing required DOM elements. Ensure the buttons and container exist."
    );
    return;
  }

  // Function to add a time range
  addTimeRangeBtn.addEventListener("click", function (event) {
    event.preventDefault();
    console.log("Add Time Range button clicked");

    const startTime = document.getElementById("start_time").value;
    const endTime = document.getElementById("end_time").value;

    if (startTime && endTime) {
      console.log("Time range added:", startTime, endTime);

      // Add time range to the array
      timeRanges.push({ start_time: startTime, end_time: endTime });

      // Add the selected time range to the UI container
      const timeRangeDiv = document.createElement("div");
      timeRangeDiv.classList.add("time-range");
      timeRangeDiv.innerHTML = `
        <span>Start: ${startTime} - End: ${endTime}</span>
        <button class="remove-time-range">Remove</button>
      `;
      timeRangesContainer.appendChild(timeRangeDiv);

      // Clear the input fields
      document.getElementById("start_time").value = "";
      document.getElementById("end_time").value = "";
    } else {
      alert("Please select both start and end time.");
    }
  });

  // Function to remove a time range
  timeRangesContainer.addEventListener("click", function (event) {
    if (event.target.classList.contains("remove-time-range")) {
      console.log("Remove Time Range button clicked");

      const timeRangeDiv = event.target.parentElement;
      const timeRangeText = timeRangeDiv.querySelector("span").innerText;
      const [startText, endText] = timeRangeText.split(" - ");
      const startTime = startText.split("Start: ")[1];
      const endTime = endText.split("End: ")[1];

      // Remove the time range from the array
      timeRanges = timeRanges.filter(
        (range) => range.start_time !== startTime || range.end_time !== endTime
      );

      // Remove the div from the UI
      timeRangeDiv.remove();
    }
  });

  // Submit availability with all time ranges
  setAvailabilityBtn.addEventListener("click", function () {
    console.log("Set Availability button clicked");

    const consultationType = document.getElementById("consultation_type").value;
    const consultationDuration = document.getElementById(
      "consultation_duration"
    ).value;
    const availabilityDate = document.getElementById("availability_date").value;
    const status = document.getElementById("status").value;

    console.log("Collected time ranges:", timeRanges);

    // Prepare the data to be sent
    const availabilityData = {
      doctor_id: doctorId, // Use the passed doctorId
      consultation_type: consultationType,
      consultation_duration: consultationDuration,
      date: availabilityDate,
      time_ranges: timeRanges, // Send the array of time ranges
      status: status,
    };

    // Send the request
    fetch("/api/set_doctor_availability.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(availabilityData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status) {
          alert("Availability set successfully");
          loadDoctorCalendar(doctorId); // Refresh the calendar
        } else {
          alert("Failed to set availability");
        }
      })
      .catch((error) => console.error("Error:", error));
  });
}

// Function to load admin dashboard data
function loadPatientDashboard(patient_id) {
  // Logic for dashboard interactions

  fetchAppointments(patient_id);
  loadNotifications(patient_id);

  setInterval(function () {
    fetchUpcomingAppointments(patient_id);
  }, 5 * 60 * 1000); // Check every 5 minutes

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
}

// Function to load notifications
function loadNotifications(patient_id) {
  fetch(`/api/get_notifications.php?patient_id=${patient_id}`)
    .then((response) => response.json())
    .then((data) => {
      const notificationList = document.getElementById("notificationList");
      notificationList.innerHTML = ""; // Clear previous notifications

      data.forEach((notification) => {
        const listItem = document.createElement("li");
        listItem.className = "px-4 py-2 hover:bg-gray-200";
        listItem.textContent = notification.message;
        notificationList.appendChild(listItem);
      });
    })
    .catch((error) => console.error("Error fetching notifications:", error));
}

// Function to fetch upcoming appointments for reminders
function fetchUpcomingAppointments(patient_id) {
  fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
    .then((response) => response.json())
    .then((appointments) => {
      const now = new Date();

      appointments.forEach((appointment) => {
        const appointmentDate = new Date(
          `${appointment.date}T${appointment.time}`
        );
        const timeDiff = (appointmentDate - now) / (1000 * 60); // Difference in minutes

        if (appointment.service_id == 1 && timeDiff <= 30 && timeDiff > 0) {
          // Trigger a reminder notification 30 minutes before an online consultation
          showNotification(
            `Reminder: Your online consultation with Dr. ${appointment.doctor_name} starts in 30 minutes.`
          );
        }
      });
    })
    .catch((error) =>
      console.error("Error fetching upcoming appointments:", error)
    );
}

// Utility function to show notifications
function showNotification(message) {
  const notificationMenu = document.getElementById("notificationMenu");
  const notificationList = document.getElementById("notificationList");

  const listItem = document.createElement("li");
  listItem.className = "px-4 py-2 hover:bg-gray-200";
  listItem.textContent = message;

  notificationList.appendChild(listItem);
  notificationMenu.classList.remove("hidden");
}

// Toggle notification menu visibility
document
  .getElementById("notificationDropdown")
  .addEventListener("click", () => {
    const notificationMenu = document.getElementById("notificationMenu");
    notificationMenu.classList.toggle("hidden");
  });

// // Load notifications on page load
// document.addEventListener("DOMContentLoaded", function () {
//   checkUserSession().then((sessionData) => {
//     if (
//       sessionData.status &&
//       sessionData.user_id &&
//       sessionData.role === "patient"
//     ) {
//       loadNotifications(sessionData.user_id);
//     }
//   });
// });

function fetchAppointments(patient_id) {
  fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
    .then((response) => response.json())
    .then((data) => {
      const appointmentsTable = document.getElementById("appointmentsTable");
      appointmentsTable.innerHTML = ""; // Clear previous appointments

      data.forEach((appointment) => {
        const row = document.createElement("tr");
        const doctorCell = document.createElement("td");
        doctorCell.className = "border px-4 py-2";
        doctorCell.textContent = "Dr. " + appointment.doctor_name;

        const dateCell = document.createElement("td");
        dateCell.className = "border px-4 py-2";
        dateCell.textContent = appointment.date;

        const timeCell = document.createElement("td");
        timeCell.className = "border px-4 py-2";
        timeCell.textContent = appointment.time;

        const dueInCell = document.createElement("td");
        dueInCell.className = "border px-4 py-2";
        const daysDue = calculateDaysDue(appointment.date);
        dueInCell.textContent = `${daysDue} days`;

        if (daysDue <= 3) {
          dueInCell.classList.add("text-red-500", "font-bold");
        }

        const actionsCell = document.createElement("td");
        actionsCell.className = "border px-4 py-2";
        const actionButton = document.createElement("button");

        if (appointment.service_id == 1) {
          // Online Consultation
          actionButton.textContent = "Join Room";
          actionButton.className =
            "bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded";
          actionButton.disabled = new Date() < new Date(appointment.date);
          // Add logic to join online room here...
        } else {
          // Physical Consultation
          actionButton.textContent = "Locate Clinic";
          actionButton.className =
            "bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded";
          actionButton.addEventListener("click", () => {
            window.location.href = `/doctor_info.php?doctor_id=${appointment.doctor_id}`;
          });
        }

        actionsCell.appendChild(actionButton);
        row.appendChild(doctorCell);
        row.appendChild(dateCell);
        row.appendChild(timeCell);
        row.appendChild(dueInCell);
        row.appendChild(actionsCell);
        appointmentsTable.appendChild(row);
      });
    })
    .catch((error) => console.error("Error fetching appointments:", error));
}

function calculateDaysDue(appointmentDate) {
  const currentDate = new Date();
  const targetDate = new Date(appointmentDate);
  const timeDiff = targetDate.getTime() - currentDate.getTime();
  return Math.ceil(timeDiff / (1000 * 3600 * 24));
}

// Function to initialize and load the FullCalendar component for doctors
function loadDoctorCalendar(doctorId) {
  const calendarEl = document.getElementById("calendar");

  if (calendarEl) {
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "timeGridWeek",
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
      eventClick: function (info) {
        handleEventClick(info, doctorId, calendar); // Handle event click logic if required
      },
      // Optional: you can adjust the slot duration, view options, etc.
      slotMinTime: "08:00:00", // Assuming the doctor's schedule starts at 8 AM
      slotMaxTime: "18:00:00", // Assuming the doctor's schedule ends at 6 PM
      eventColor: "green", // Default event color (overridden by individual event colors)
      eventTextColor: "white", // Default text color
    });

    calendar.render();
  } else {
    console.error("Calendar element not found");
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

function convertTo24Hour(time) {
  const [timePart, modifier] = time.split(" ");
  let [hours, minutes] = timePart.split(":");

  // Ensure hours is treated as a string for padStart
  hours = hours.toString();

  if (hours === "12") {
    hours = "00";
  }
  if (modifier === "PM" && hours !== "12") {
    hours = (parseInt(hours, 10) + 12).toString();
  }

  return `${hours.padStart(2, "0")}:${minutes}`;
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
