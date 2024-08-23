// Reschedule Module
const RescheduleModule = (() => {
  let selectedDoctorId = null;

  const init = () => {
    document.addEventListener("DOMContentLoaded", () => {
      // Check user session and proceed with loading appointments
      checkUserSession().then((sessionData) => {
        if (sessionData.status && sessionData.user_id) {
          const appointmentSelect = document.getElementById("appointment_id");

          // Load appointments and set up event listeners
          loadAppointments(appointmentSelect, sessionData.user_id);
          setupAppointmentChangeListener(appointmentSelect);
          handleRescheduleAppointment(sessionData.user_id);
        } else {
          console.error("User session is invalid.");
        }
      });
    });
  };

  // Load appointments and populate the dropdown
  const loadAppointments = (appointmentSelect, patientId) => {
    fetch(`/api/get_appointments.php?patient_id=${patientId}`)
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
  };

  // Set up event listener for appointment selection changes
  const setupAppointmentChangeListener = (appointmentSelect) => {
    appointmentSelect.addEventListener("change", function () {
      const selectedOption =
        appointmentSelect.options[appointmentSelect.selectedIndex];
      selectedDoctorId = selectedOption.getAttribute("data-doctor-id");

      if (selectedDoctorId) {
        // Fetch and display the calendar with doctor's availability
        CalendarModule.loadCalendar(selectedDoctorId);
      } else {
        console.error("Doctor ID not found.");
      }
    });
  };

  // Handle form submission and rescheduling
  const handleRescheduleAppointment = (patientId) => {
    const rescheduleButton = document.querySelector('button[type="submit"]');
    rescheduleButton.addEventListener("click", function (e) {
      e.preventDefault();

      const selectedAppointmentId =
        document.getElementById("appointment_id").value;
      const selectedDate = document.getElementById("date").value;
      const selectedTime = document.getElementById("time").value;

      if (
        !selectedAppointmentId ||
        !selectedDate ||
        !selectedTime ||
        !selectedDoctorId
      ) {
        alert("Please fill in all fields");
        return;
      }

      const requestData = {
        appointment_id: selectedAppointmentId, // Use the selected appointment ID
        doctor_id: selectedDoctorId, // Use the stored doctor ID
        date: selectedDate,
        time: selectedTime,
      };

      fetch("/api/reschedule_appointment.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(requestData),
      })
        .then((response) => response.json())
        .then((data) => {
          alert(data.message);
          if (data.status) {
            window.location.href = "/dashboard.php"; // Redirect on success
          }
        })
        .catch((error) => console.error("Error:", error));
    });
  };

  return {
    init,
  };
})();

// Calendar Module
const CalendarModule = (() => {
  const loadCalendar = (doctorId) => {
    if (!doctorId) {
      console.error("Doctor ID is null or undefined.");
      return;
    }

    const calendarEl = document.getElementById("calendar");
    const calendar = new FullCalendar.Calendar(calendarEl, {
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
  };

  return {
    loadCalendar,
  };
})();

// Initialization
RescheduleModule.init();
