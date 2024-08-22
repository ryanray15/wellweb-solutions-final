const appointmentSelect = document.getElementById("appointment_id");
let selectedDoctorId = null;

// Fetch appointments and populate the dropdown
function fetchAppointments() {
  fetch("/api/get_appointments.php?patient_id=<?php echo $user_id; ?>")
    .then((response) => response.json())
    .then((data) => {
      appointmentSelect.innerHTML = data
        .map(
          (appointment) =>
            `<option value="${appointment.appointment_id}" data-doctor-id="${appointment.doctor_id}">
                                Appointment with Dr. ${appointment.doctor_name} on ${appointment.date} at ${appointment.time}
                            </option>`
        )
        .join("");

      // Trigger change event to load the calendar for the selected appointment
      appointmentSelect.dispatchEvent(new Event("change"));
    })
    .catch((error) => console.error("Error fetching appointments:", error));
}

// Handle appointment selection change
appointmentSelect.addEventListener("change", function () {
  const selectedOption =
    appointmentSelect.options[appointmentSelect.selectedIndex];
  selectedDoctorId = selectedOption.getAttribute("data-doctor-id");

  if (selectedDoctorId) {
    // Fetch and display the calendar with doctor's availability
    fetchDoctorAvailability(selectedDoctorId);
  } else {
    console.error("Doctor ID not found.");
  }
});

// Fetch and display doctor's availability
function fetchDoctorAvailability(doctorId) {
  if (!doctorId) {
    console.error("Doctor ID is null or undefined.");
    return;
  }

  var calendarEl = document.getElementById("calendar");
  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "timeGridWeek",
    selectable: true,
    timeZone: "Asia/Manila", // Use local time zone
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    events: {
      url: `/api/get_doctor_availability.php`,
      method: "GET",
      extraParams: {
        doctor_id: doctorId, // Pass the correct doctor_id here
      },
      failure: function () {
        alert("There was an error while fetching availability!");
      },
    },
  });

  calendar.render();
}

// Load the reschedule page data when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  checkUserSession().then((sessionData) => {
    const patientId = sessionData.user_id;

    // Proceed with page load after session check
    fetchAppointments();
    fetchDoctorAvailability(patientId);
  });
});
