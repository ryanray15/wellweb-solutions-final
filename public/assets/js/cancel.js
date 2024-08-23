// Cancel Module
const CancelModule = (() => {
  let selectedAppointmentId = null;

  const init = () => {
    document.addEventListener("DOMContentLoaded", () => {
      // Check user session and proceed with loading appointments
      checkUserSession().then((sessionData) => {
        if (sessionData.status && sessionData.user_id) {
          const appointmentSelect = document.getElementById("appointment_id");

          // Load appointments and set up event listeners
          loadAppointments(appointmentSelect, sessionData.user_id);
          setupCancelForm(sessionData.user_id);
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
              `<option value="${appointment.appointment_id}">
                                Appointment with Dr. ${appointment.doctor_name} on ${appointment.date} at ${appointment.time}
                            </option>`
          )
          .join("");

        // Set the first appointment as the selected one (if available)
        if (data.length > 0) {
          selectedAppointmentId = data[0].appointment_id;
          appointmentSelect.dispatchEvent(new Event("change"));
        }
      })
      .catch((error) => console.error("Error fetching appointments:", error));
  };

  // Setup the Cancel Form submission handler
  const setupCancelForm = () => {
    const cancelForm = document.getElementById("cancelForm");
    if (cancelForm) {
      cancelForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const appointment_id = document.getElementById("appointment_id").value;

        try {
          const response = await fetch("/api/cancel_appointment.php", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
            },
            body: JSON.stringify({ appointment_id }),
          });

          const result = await response.json();

          if (result.status) {
            alert("Appointment canceled successfully!");
            window.location.href = "/dashboard.php"; // Redirect on success
          } else {
            alert("Failed to cancel appointment: " + result.message);
          }
        } catch (error) {
          console.error("Error cancelling appointment:", error);
          alert(
            "An error occurred while cancelling the appointment. Please try again."
          );
        }
      });
    }
  };

  return {
    init,
  };
})();

// Initialization
CancelModule.init();
