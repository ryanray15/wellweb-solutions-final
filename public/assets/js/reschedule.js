// Reschedule Module
const RescheduleModule = (() => {
  let selectedDoctorId = null;

  const init = () => {
    document.addEventListener("DOMContentLoaded", () => {
      // Check user session and proceed with loading doctors and appointments
      checkUserSession().then((sessionData) => {
        if (sessionData.status && sessionData.user_id) {
          setupDoctorSelection(sessionData.user_id);
          setupConsultationTypeChange();
        } else {
          console.error("User session is invalid.");
        }
      });
    });
  };

  // Load doctors and populate the dropdown
  const setupDoctorSelection = (userId) => {
    const doctorSelect = document.getElementById("doctor_id");

    fetch(`/api/get_reschedule_doctors.php?user_id=${userId}`)
      .then((response) => response.json())
      .then((responseData) => {
        const doctors = responseData.data || [];
        doctorSelect.innerHTML = doctors
          .map(
            (doctor) =>
              `<option value="${doctor.doctor_id}">${doctor.doctor_name}</option>`
          )
          .join("");

        // Set the initial doctor selection and trigger the change event
        doctorSelect.addEventListener("change", function () {
          selectedDoctorId = this.value;
          const consultationType =
            document.getElementById("consultation_type").value;
          updateAppointmentsContainer(selectedDoctorId, consultationType);
          CalendarModule.loadCalendar(selectedDoctorId);
        });

        // Trigger the initial loading
        if (doctors.length > 0) {
          selectedDoctorId = doctorSelect.value;
          const initialConsultationType =
            document.getElementById("consultation_type").value;
          updateAppointmentsContainer(
            selectedDoctorId,
            initialConsultationType
          );
          CalendarModule.loadCalendar(selectedDoctorId);
        } else {
          console.warn("No doctors available for selection.");
        }
      })
      .catch((error) => console.error("Error fetching doctors:", error));
  };

  // Set up consultation type change listener
  const setupConsultationTypeChange = () => {
    const consultationTypeSelect = document.getElementById("consultation_type");
    consultationTypeSelect.addEventListener("change", function () {
      const consultationType = this.value;
      if (selectedDoctorId) {
        updateAppointmentsContainer(selectedDoctorId, consultationType);
      }
    });
  };

  // Update appointments container with the list of appointments based on doctor and consultation type
  const updateAppointmentsContainer = (doctorId, consultationType) => {
    const container = document.getElementById("appointments-container");

    fetch(
      `/api/get_reschedule_appointments.php?doctor_id=${doctorId}&consultation_type=${consultationType}`
    )
      .then((response) => response.json())
      .then((data) => {
        container.innerHTML = data.data
          .map((appointment) => {
            // Extract details from the appointment object as needed
            const details = appointment.details || "";
            const [consultationType, datePart, timePart] =
              details.split(" on ");
            const [date, timeRange] = (datePart || "").split(" from ");
            const [startTime, endTime] = (timeRange || "").split(" to ");

            // Set color based on consultation type
            const color =
              consultationType === "Physical Consultation" ? "green" : "blue";

            return `
            <div class="appointment-slot fc-event draggable-event" style="background-color: ${color}; color: white;">
              ${consultationType || "Consultation"} on ${date || "N/A"} from ${
              startTime || "N/A"
            } to ${endTime || "N/A"}
            </div>`;
          })
          .join("");

        // Make the appointment slots draggable
        new FullCalendar.Draggable(container, {
          itemSelector: ".draggable-event",
          eventData: function (eventEl) {
            return {
              title: eventEl.innerText,
            };
          },
        });
      })
      .catch((error) => console.error("Error fetching appointments:", error));
  };

  return {
    init,
  };
})();

const setupConsultationTypeChange = () => {
  const consultationTypeSelect = document.getElementById("consultation_type");
  consultationTypeSelect.addEventListener("change", function () {
    const consultationType = this.value;
    if (selectedDoctorId) {
      updateAppointmentsContainer(selectedDoctorId, consultationType);
    }
  });
};

// Calendar Module
const CalendarModule = (() => {
  const loadCalendar = (doctorId) => {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) {
      console.error("Calendar element not found");
      return;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: "timeGridWeek",
      selectable: true,
      editable: false,
      droppable: true,
      timeZone: "Asia/Manila",
      headerToolbar: {
        left: "prev,next today",
        center: "title",
        right: "dayGridMonth,timeGridWeek,timeGridDay",
      },
      events: {
        url: `/api/get_doctor_full_availability.php`,
        method: "GET",
        extraParams: {
          doctor_id: doctorId,
        },
        failure: function () {
          alert("There was an error while fetching availability!");
        },
        success: function (data) {
          console.log("Fetched events:", data);
        },
      },
      eventReceive: function (info) {
        const start = info.event.start;
        const end = info.event.end;
        if (!start || !end) {
          console.error("Invalid start or end date:", { start, end });
          info.event.remove();
          return;
        }

        const requestData = {
          doctor_id: doctorId,
          start_time: start.toISOString(),
          end_time: end.toISOString(),
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
            if (!data.status) {
              alert("Failed to reschedule appointment.");
              info.event.remove();
            } else {
              console.log("Appointment rescheduled successfully.");
            }
          })
          .catch((error) => {
            console.error("Error rescheduling appointment:", error);
            info.event.remove();
          });
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
