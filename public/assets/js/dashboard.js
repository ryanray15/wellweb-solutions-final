// Load the dashboard when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  // Check user role and load the corresponding dashboard
  checkUserSession().then((sessionData) => {
    if (sessionData.status && sessionData.user_id) {
      // Set a global variable for user_id and user_role if needed
      const user_id = sessionData.user_id;
      const user_role = sessionData.role;

      if (user_role === "admin") {
        // Admin-specific setup
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
      } else if (user_role === "doctor") {
        // Doctor-specific setup
        loadDoctorDashboard(user_id);

        const doctorTypeDropdown = document.getElementById(
          "doctorAppointmentType"
        );
        if (doctorTypeDropdown) {
          console.log("Doctor type dropdown found. Adding event listener."); // Debugging line
          doctorTypeDropdown.addEventListener("change", () => {
            console.log("Doctor type changed."); // Debugging line
            fetchDoctorAppointments(user_id);
          });
        } else {
          console.log("Doctor type dropdown not found."); // Debugging line
        }
        // Initialize WebSocket for doctor
        const socket = new WebSocket("ws://localhost:8080"); // Ensure this matches your WebSocket server address

        // Handle WebSocket connection events
        socket.onopen = () => {
          console.log("Connected to WebSocket server");
        };

        // WebSocket message handler for both doctor and patient roles
        socket.onmessage = (event) => {
          const message = JSON.parse(event.data);

          if (message.type === "expired_availabilities") {
            console.log("Received expired_availabilities message:", message);
            reloadDashboardCalendar();
          } else if (message.type === "check_call_completion") {
            console.log("Received appointment completion update:", message);

            // Replace 'userRole' and 'userId' with the actual role and ID variables in your script
            updateAppointments(user_role, user_id); // Call function to update appointments based on role
          }
        };

        socket.onerror = (error) => {
          console.error("WebSocket Error:", error);
        };

        socket.onclose = () => {
          console.log("Disconnected from WebSocket server");
        };
      } else if (user_role === "patient") {
        // Patient-specific setup
        loadPatientDashboard(user_id);

        const patientTypeDropdown = document.getElementById(
          "patientAppointmentType"
        );
        if (patientTypeDropdown) {
          console.log("Patient type dropdown found. Adding event listener."); // Debugging line
          patientTypeDropdown.addEventListener("change", () => {
            console.log("Patient type changed."); // Debugging line
            fetchAppointments(user_id);
          });
        }

        // Add search functionality only for patients
        const doctorSearchBar = document.getElementById("doctorSearchBar");
        if (doctorSearchBar) {
          doctorSearchBar.addEventListener("input", function () {
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
        // Initialize WebSocket for doctor
        const socket = new WebSocket("ws://localhost:8080"); // Ensure this matches your WebSocket server address

        // Handle WebSocket connection events
        socket.onopen = () => {
          console.log("Connected to WebSocket server");
        };

        // WebSocket message handler for both doctor and patient roles
        socket.onmessage = (event) => {
          const message = JSON.parse(event.data);

          if (message.type === "expired_availabilities") {
            console.log("Received expired_availabilities message:", message);
            reloadDashboardCalendar();
          } else if (message.type === "check_call_completion") {
            console.log("Received appointment completion update:", message);

            // Replace 'userRole' and 'userId' with the actual role and ID variables in your script
            updateAppointments(user_role, user_id); // Call function to update appointments based on role
          }
        };

        socket.onerror = (error) => {
          console.error("WebSocket Error:", error);
        };

        socket.onclose = () => {
          console.log("Disconnected from WebSocket server");
        };
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
  fetchDoctorAppointments(doctorId);

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
    const consultationDuration = parseInt(
      document.getElementById("consultation_duration").value,
      10
    );

    if (startTime && endTime) {
      // Check if the time range exceeds the consultation duration
      const startDate = new Date(`01/01/2020 ${startTime}`);
      const endDate = new Date(`01/01/2020 ${endTime}`);
      const timeDifferenceInMinutes = (endDate - startDate) / (1000 * 60); // Convert to minutes

      if (timeDifferenceInMinutes > consultationDuration) {
        alert(
          `Time range exceeds your set consultation duration of ${consultationDuration} minutes. Please select a shorter time range.`
        );
        return;
      }

      console.log("Time range added:", startTime, endTime);

      // Add time range to the array
      timeRanges.push({
        start_time: convertTo24Hour(startTime), // Convert to 24-hour format
        end_time: convertTo24Hour(endTime), // Convert to 24-hour format
      });

      // Add the selected time range to the UI container
      const timeRangeDiv = document.createElement("div");
      timeRangeDiv.classList.add("time-range");
      timeRangeDiv.innerHTML = `
        <span>Start: ${startTime} - End: ${endTime}</span>
        <button class="remove-time-range text-red-500">Remove</button>
      `;
      timeRangesContainer.appendChild(timeRangeDiv);

      // Clear the input fields
      document.getElementById("start_time").value = "";
      document.getElementById("end_time").value = "";
    } else {
      alert("Please select both start and end time.");
    }
  });

  // Function to remove a time range //TODO!!!
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

        // Clear the array and reset UI after setting availability
        timeRanges = [];
        timeRangesContainer.innerHTML = ""; // Clear the container UI
      })
      .catch((error) => console.error("Error:", error));
  });

  // Time conversion function (convert to 24-hour format)
  function convertTo24Hour(time) {
    const [timePart, modifier] = time.split(" ");
    let [hours, minutes] = timePart.split(":");

    // Ensure hours is a string before using padStart
    hours = hours.toString();

    if (hours === "12") {
      hours = "00";
    }
    if (modifier === "PM" && hours !== "12") {
      hours = (parseInt(hours, 10) + 12).toString(); // Convert back to string after adding 12
    }

    return `${hours.padStart(2, "0")}:${minutes}`;
  }
}

// Function to load admin dashboard data
function loadPatientDashboard(patient_id) {
  // Logic for dashboard interactions

  fetchAppointments(patient_id);
  loadNotifications(patient_id);

  setInterval(function () {
    fetchUpcomingAppointments(patient_id);
  }, 5 * 60 * 1000); // Check every 5 minutes

  // // Fetch appointments and update the dashboard
  // if (patient_id) {
  //   fetch(`/api/get_appointments.php?patient_id=${patient_id}`)
  //     .then((response) => response.json())
  //     .then((data) => {
  //       if (data.length > 0) {
  //         document.getElementById("rescheduleMessage").textContent =
  //           "You have scheduled appointments.";
  //         // document.getElementById("cancelMessage").textContent =
  //         //   "You have scheduled appointments.";

  //         document
  //           .getElementById("rescheduleButton")
  //           .classList.remove("hidden");
  //         // document.getElementById("cancelButton").classList.remove("hidden");
  //       } else {
  //         document.getElementById("rescheduleMessage").textContent =
  //           "No appointments scheduled.";
  //         // document.getElementById("cancelMessage").textContent =
  //         //   "No appointments scheduled.";
  //       }
  //     })
  //     .catch((error) => console.error("Error fetching appointments:", error));
  // }
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

function fetchAppointments(patient_id) {
  // Get the selected type from the dropdown
  const type = document.getElementById("patientAppointmentType").value;

  // Fetch appointments with selected type
  fetch(`/api/get_appointments.php?patient_id=${patient_id}&type=${type}`)
    .then((response) => response.json())
    .then((data) => {
      const appointmentsTable = document.getElementById("appointmentsTable");
      appointmentsTable.innerHTML = ""; // Clear previous appointments

      data.forEach((appointment) => {
        const row = document.createElement("tr");

        // Doctor's Name Cell
        const doctorCell = document.createElement("td");
        doctorCell.className = "border px-4 py-2";
        doctorCell.textContent = "Dr. " + appointment.doctor_name;

        // Appointment Date Cell
        const dateCell = document.createElement("td");
        dateCell.className = "border px-4 py-2";
        dateCell.textContent = appointment.date;

        // Appointment Time Cell
        const timeCell = document.createElement("td");
        timeCell.className = "border px-4 py-2";
        timeCell.textContent =
          appointment.start_time + " - " + appointment.end_time;

        // Due In Cell
        const dueInCell = document.createElement("td");
        dueInCell.className = "border px-4 py-2";
        const daysDue = calculateDaysDue(appointment.date);
        let isOverdue = false;

        if (daysDue < 0) {
          dueInCell.textContent = "Overdue";
          dueInCell.classList.add("text-red-500", "font-bold");
          isOverdue = true;
        } else {
          dueInCell.textContent = `${daysDue} days`;
          if (daysDue <= 3) {
            dueInCell.classList.add("text-red-500", "font-bold");
          }
        }

        // Actions Cell for Join Room / Locate Clinic or Reschedule / Cancel
        const actionsCell = document.createElement("td");
        actionsCell.className =
          "border px-4 py-2 flex justify-center items-center space-x-2";

        if (isOverdue) {
          // Show Reschedule and Cancel buttons directly for overdue appointments
          const rescheduleButton = document.createElement("button");
          rescheduleButton.textContent = "Reschedule";
          rescheduleButton.className = "text-blue-600 font-bold py-1 px-2 mr-2";
          rescheduleButton.addEventListener("click", () => {
            window.location.href = `/reschedule.php?appointment_id=${appointment.appointment_id}`;
          });

          const cancelButton = document.createElement("button");
          cancelButton.textContent = "Cancel";
          cancelButton.className = "text-red-500 font-bold py-1 px-2";
          cancelButton.addEventListener("click", () =>
            handleCancel(appointment.appointment_id, patient_id)
          );

          actionsCell.appendChild(rescheduleButton);
          actionsCell.appendChild(cancelButton);
        } else {
          // Add Join Room / Locate Clinic button and kebab menu
          const actionButton = document.createElement("button");

          if (appointment.service_id == 1) {
            // Online Consultation
            actionButton.textContent = "Join Room";
            actionButton.className = "font-bold py-1 px-3 rounded text-white";

            const appointmentDate = new Date(appointment.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            appointmentDate.setHours(0, 0, 0, 0);

            if (today.getTime() === appointmentDate.getTime()) {
              actionButton.disabled = false;
              actionButton.classList.add("bg-blue-500", "hover:bg-blue-600");
            } else {
              actionButton.disabled = true;
              actionButton.classList.add("bg-gray-400", "cursor-not-allowed");
              actionButton.title =
                "You can only join on the day of the appointment";
            }

            actionButton.addEventListener("click", () => {
              fetch(
                `/api/get_meeting_id.php?appointment_id=${appointment.appointment_id}&user_id=${patient_id}`
              )
                .then((response) => response.json())
                .then((data) => {
                  if (data.meeting_id) {
                    // Corrected URL with both meeting_id and appointment_id parameters
                    window.location.href = `/conference_room.php?meeting_id=${data.meeting_id}&appointment_id=${appointment.appointment_id}`;
                  } else {
                    alert("Meeting ID not found or unauthorized.");
                  }
                })
                .catch((error) =>
                  console.error("Error fetching meeting ID:", error)
                );
            });
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

          // Kebab Menu
          const kebabMenuContainer = document.createElement("div");
          kebabMenuContainer.className = "relative";

          const kebabButton = document.createElement("button");
          kebabButton.className = "text-gray-500 focus:outline-none ml-2";
          kebabButton.innerHTML = `<i class="fas fa-ellipsis-v"></i>`;
          kebabMenuContainer.appendChild(kebabButton);

          const kebabMenu = document.createElement("div");
          kebabMenu.className =
            "absolute right-0 mt-2 w-24 bg-white rounded shadow-lg z-10 hidden";
          kebabMenu.innerHTML = `
            <button class="block w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-gray-100" onclick="window.location.href='/reschedule.php?appointment_id=${appointment.appointment_id}'">Reschedule</button>
            <button class="block w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-gray-100" onclick="handleCancel(${appointment.appointment_id}, ${patient_id})">Cancel</button>
          `;
          kebabMenuContainer.appendChild(kebabMenu);

          // Toggle kebab menu visibility on click
          kebabButton.addEventListener("click", (e) => {
            e.stopPropagation();
            kebabMenu.classList.toggle("hidden");
          });

          // Close the menu when clicking outside
          document.addEventListener("click", () => {
            kebabMenu.classList.add("hidden");
          });

          actionsCell.appendChild(kebabMenuContainer);
        }

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

// Function to handle appointment cancellation
function handleCancel(appointment_id, patient_id) {
  if (confirm("Are you sure you want to cancel this appointment?")) {
    fetch(`/api/cancel_appointment.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ appointment_id }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status) {
          alert("Appointment canceled successfully.");
          fetchAppointments(patient_id); // Refresh the appointments
        } else {
          alert(
            data.message || "Failed to cancel appointment. Please try again."
          );
        }
      })
      .catch((error) => console.error("Error canceling appointment:", error));
  }
}

function fetchDoctorAppointments(doctor_id) {
  console.log("Doctor ID:", doctor_id);

  // Get the selected type from the dropdown
  const type = document.getElementById("doctorAppointmentType").value;
  console.log("Fetching appointments for type:", type);

  fetch(`/api/get_doctor_appointments.php?doctor_id=${doctor_id}&type=${type}`)
    .then((response) => response.json())
    .then((data) => {
      const appointmentsTable = document.getElementById(
        "doctorAppointmentsTable"
      );
      appointmentsTable.innerHTML = ""; // Clear previous appointments

      data.forEach((appointment) => {
        const row = document.createElement("tr");

        // Patient's Name Cell
        const patientCell = document.createElement("td");
        patientCell.className = "border px-4 py-2";
        patientCell.textContent = appointment.patient_name;

        // Appointment Date Cell
        const dateCell = document.createElement("td");
        dateCell.className = "border px-4 py-2";
        dateCell.textContent = appointment.date;

        // Appointment Time Cell
        const timeCell = document.createElement("td");
        timeCell.className = "border px-4 py-2";
        timeCell.textContent =
          appointment.start_time + " - " + appointment.end_time;

        // Status Cell
        const statusCell = document.createElement("td");
        statusCell.className = "border px-4 py-2";
        statusCell.textContent = appointment.status;

        // Due In Cell
        const dueInCell = document.createElement("td");
        dueInCell.className = "border px-4 py-2";

        const daysDue = calculateDaysDue(appointment.date);
        let isOverdue = false;

        if (daysDue < 0) {
          dueInCell.textContent = "Overdue";
          dueInCell.classList.add("text-red-500", "font-bold");
          isOverdue = true;
        } else {
          dueInCell.textContent = `${daysDue} days`;
          if (daysDue <= 3) {
            dueInCell.classList.add("text-red-500", "font-bold");
          }
        }

        // Actions Cell
        const actionsCell = document.createElement("td");
        actionsCell.className =
          "border px-4 py-2 flex justify-center items-center space-x-2";

        // Logic for actions based on appointment status and type
        if (appointment.status === "no show") {
          // Show "Reschedule" button if the status is "no show"
          const rescheduleButton = document.createElement("button");
          rescheduleButton.textContent = "Reschedule";
          rescheduleButton.className = "text-blue-600 font-bold py-1 px-2";
          rescheduleButton.addEventListener("click", () => {
            window.location.href = `/reschedule.php?appointment_id=${appointment.appointment_id}`;
          });
          actionsCell.appendChild(rescheduleButton);
        } else if (isOverdue && appointment.service_id === 1) {
          // Directly show Reschedule button if it's an overdue online consultation
          const rescheduleButton = document.createElement("button");
          rescheduleButton.textContent = "Reschedule";
          rescheduleButton.className = "text-blue-600 font-bold py-1 px-2";
          rescheduleButton.addEventListener("click", () => {
            window.location.href = `/reschedule.php?appointment_id=${appointment.appointment_id}`;
          });
          actionsCell.appendChild(rescheduleButton);
        } else if (isOverdue && appointment.service_id === 2) {
          // Show toggle buttons for "Completed" and "No Show" if it's an overdue physical consultation
          const completedButton = document.createElement("button");
          completedButton.textContent = "Completed";
          completedButton.className = "text-blue-600 font-bold py-1 px-2";
          completedButton.addEventListener("click", () =>
            updateAppointmentStatus(
              appointment.appointment_id,
              "completed",
              doctor_id
            )
          );
          actionsCell.appendChild(completedButton);

          const noShowButton = document.createElement("button");
          noShowButton.textContent = "No Show";
          noShowButton.className = "text-red-500 font-bold py-1 px-2";
          noShowButton.addEventListener("click", () =>
            updateAppointmentStatus(
              appointment.appointment_id,
              "no show",
              doctor_id
            )
          );
          actionsCell.appendChild(noShowButton);
        } else if (appointment.service_id === 2) {
          // For non-overdue physical consultations, show the Reschedule button only
          const rescheduleButton = document.createElement("button");
          rescheduleButton.textContent = "Reschedule";
          rescheduleButton.className = "text-blue-600 font-bold py-1 px-2";
          rescheduleButton.addEventListener("click", () => {
            window.location.href = `/reschedule.php?appointment_id=${appointment.appointment_id}`;
          });
          actionsCell.appendChild(rescheduleButton);
        } else {
          // Display Join Room button with kebab menu for non-overdue online consultations
          const actionButton = document.createElement("button");

          if (appointment.service_id == 1) {
            actionButton.textContent = "Join Room";
            actionButton.className = "font-bold py-1 px-3 rounded text-white";

            const appointmentDate = new Date(appointment.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            appointmentDate.setHours(0, 0, 0, 0);

            if (today.getTime() === appointmentDate.getTime()) {
              actionButton.disabled = false;
              actionButton.classList.add("bg-blue-500", "hover:bg-blue-600");
            } else {
              actionButton.disabled = true;
              actionButton.classList.add("bg-gray-400", "cursor-not-allowed");
              actionButton.title =
                "You can only join on the day of the appointment";
            }

            actionButton.addEventListener("click", () => {
              fetch(
                `/api/get_meeting_id.php?appointment_id=${appointment.appointment_id}&user_id=${doctor_id}`
              )
                .then((response) => response.json())
                .then((data) => {
                  if (data.meeting_id) {
                    window.location.href = `/conference_room.php?meeting_id=${data.meeting_id}&appointment_id=${appointment.appointment_id}`;
                  } else {
                    alert("Meeting ID not found or unauthorized.");
                  }
                })
                .catch((error) =>
                  console.error("Error fetching meeting ID:", error)
                );
            });

            actionsCell.appendChild(actionButton);
          }

          // Kebab Menu for non-overdue online appointments only
          if (!isOverdue) {
            const kebabMenuContainer = document.createElement("div");
            kebabMenuContainer.className = "relative inline-block";

            const kebabButton = document.createElement("button");
            kebabButton.className = "text-gray-500 focus:outline-none ml-2";
            kebabButton.innerHTML = `<i class="fas fa-ellipsis-v"></i>`;
            kebabMenuContainer.appendChild(kebabButton);

            const kebabMenu = document.createElement("div");
            kebabMenu.className =
              "absolute right-0 mt-2 w-24 bg-white rounded shadow-lg z-10 hidden";
            kebabMenu.innerHTML = `
              <button class="block w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-gray-100" onclick="window.location.href='/reschedule.php?appointment_id=${appointment.appointment_id}'">Reschedule</button>
            `;
            kebabMenuContainer.appendChild(kebabMenu);

            kebabButton.addEventListener("click", (e) => {
              e.stopPropagation();
              kebabMenu.classList.toggle("hidden");
            });

            document.addEventListener("click", () => {
              kebabMenu.classList.add("hidden");
            });

            actionsCell.appendChild(kebabMenuContainer);
          }
        }

        row.appendChild(patientCell);
        row.appendChild(dateCell);
        row.appendChild(timeCell);
        row.appendChild(statusCell);
        row.appendChild(dueInCell);
        row.appendChild(actionsCell);
        appointmentsTable.appendChild(row);
      });
    })
    .catch((error) => console.error("Error fetching appointments:", error));
}

// Update appointment status function
function updateAppointmentStatus(appointment_id, status, doctor_id) {
  fetch(`/api/update_appointment_status.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ appointment_id, status }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert(`Appointment marked as ${status}.`);
        fetchDoctorAppointments(doctor_id);
      } else {
        alert("Failed to update appointment status.");
      }
    })
    .catch((error) =>
      console.error("Error updating appointment status:", error)
    );
}

// Helper function to calculate days due
function calculateDaysDue(dateString) {
  const today = new Date();
  const appointmentDate = new Date(dateString);
  const timeDifference = appointmentDate - today;
  const daysDifference = Math.ceil(timeDifference / (1000 * 3600 * 24));
  return daysDifference;
}

// Functions for Reschedule and Cancel actions
function handleReschedule(appointmentId) {
  // Implement rescheduling logic here, e.g., open a modal with date picker for rescheduling
  console.log("Reschedule appointment with ID:", appointmentId);
}

// Function to reload the calendar for the dashboard
function reloadDashboardCalendar() {
  console.log("Reloading calendar due to WebSocket update"); // Debugging line
  const doctorId = user_role === "doctor" ? user_id : null;
  if (doctorId) {
    loadDoctorCalendar(doctorId);
  } else {
    console.error("Doctor ID not found or user is not a doctor");
  }
}

// Function to load and render doctor's calendar in the dashboard
function loadDoctorCalendar(doctorId) {
  const calendarEl = document.getElementById("calendar");

  if (calendarEl) {
    // Clear any existing calendar instance
    if (calendarEl.fullCalendar) {
      calendarEl.fullCalendar.destroy();
    }

    console.log(`Loading calendar for Doctor ID: ${doctorId}`);

    // Fetch availability data and render the calendar
    fetch(`/api/get_doctor_availability.php?doctor_id=${doctorId}`)
      .then((response) => response.json())
      .then((data) => {
        // Check if the data is an array or an object with events
        const events = Array.isArray(data) ? data : data.events;

        if (!events) {
          console.warn("No events found in data:", data);
          return;
        }

        console.log("Fetched events:", events);

        // Assign event ID for each availability if not already set
        events.forEach((event) => {
          if (!event.id) event.id = event.availability_id;
        });

        const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: "timeGridWeek",
          selectable: true,
          timeZone: "Asia/Manila",
          headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
          },
          events: events,
          eventClick: function (info) {
            const event = info.event;
            handleEventSelection(event, doctorId, null);
          },
        });

        calendar.render();
      })
      .catch((error) => console.error("Error loading calendar:", error));
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

// Function to reload appointments based on user role
function updateAppointments(userRole, userId) {
  if (userRole === "doctor") {
    // Fetch and reload appointments for the doctor
    fetchDoctorAppointments(userId);
  } else if (userRole === "patient") {
    // Fetch and reload appointments for the patient
    fetchAppointments(userId);
  }
}
