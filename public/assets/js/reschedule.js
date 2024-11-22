// Reschedule Module
const RescheduleModule = (() => {
  let selectedDoctorId = null;
  let selectedConsultationType = "online"; // Default consultation type
  let selectedAppointmentId = null; // Holds the currently selected appointment_id

  const init = () => {
    document.addEventListener("DOMContentLoaded", () => {
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

        doctorSelect.addEventListener("change", function () {
          selectedDoctorId = this.value;
          const consultationType =
            document.getElementById("consultation_type").value;
          updateAppointmentsContainer(selectedDoctorId, consultationType);
          CalendarModule.loadCalendar(selectedDoctorId, consultationType);
        });

        if (doctors.length > 0) {
          selectedDoctorId = doctorSelect.value;
          const initialConsultationType =
            document.getElementById("consultation_type").value;
          updateAppointmentsContainer(
            selectedDoctorId,
            initialConsultationType
          );
          CalendarModule.loadCalendar(
            selectedDoctorId,
            initialConsultationType
          );
        } else {
          console.warn("No doctors available for selection.");
        }
      })
      .catch((error) => console.error("Error fetching doctors:", error));
  };

  const setupConsultationTypeChange = () => {
    const consultationTypeSelect = document.getElementById("consultation_type");
    consultationTypeSelect.addEventListener("change", function () {
      selectedConsultationType = this.value;
      if (selectedDoctorId) {
        updateAppointmentsContainer(selectedDoctorId, selectedConsultationType);
        CalendarModule.loadCalendar(selectedDoctorId, selectedConsultationType);
      }
    });
  };

  const updateAppointmentsContainer = (doctorId, consultationType) => {
    const container = document.getElementById("appointments-container");

    fetch(
      `/api/get_reschedule_appointments.php?doctor_id=${doctorId}&consultation_type=${consultationType}`
    )
      .then((response) => response.json())
      .then((data) => {
        container.innerHTML = data.data
          .map(
            (appointment) => `
              <div style="margin-bottom: 10px;">
                <input 
                  type="radio" 
                  name="appointment" 
                  id="appointment_${appointment.appointment_id}" 
                  value="${appointment.appointment_id}" 
                  style="margin-right: 10px;" 
                  onclick="RescheduleModule.selectAppointment(${
                    appointment.appointment_id
                  })">
                <label for="appointment_${
                  appointment.appointment_id
                }" style="color: blue; cursor: pointer;">
                  ${appointment.details || "Consultation"}
                </label>
              </div>`
          )
          .join("");
      })
      .catch((error) => console.error("Error fetching appointments:", error));
  };

  const selectAppointment = (appointmentId) => {
    selectedAppointmentId = appointmentId; // Save the selected appointment ID
    console.log(`Selected Appointment ID: ${selectedAppointmentId}`);
  };

  const getSelectedAppointmentId = () => {
    return selectedAppointmentId;
  };

  return {
    init,
    selectAppointment, // Expose this method so it can be called from the DOM
    getSelectedAppointmentId,
  };
})();

const CalendarModule = (() => {
  const loadCalendar = (doctorId, consultationType) => {
    const calendarEl = document.getElementById("calendar");
    if (!calendarEl) {
      console.error("Calendar element not found");
      return;
    }

    // Fetch slot times from the backend
    fetch(
      `/api/get_doctor_full_availability.php?doctor_id=${doctorId}&consultation_type=${consultationType}`
    )
      .then((response) => response.json())
      .then((data) => {
        const slotMinTime = data.slotTimes?.slotMinTime || "00:00:00";
        const slotMaxTime = data.slotTimes?.slotMaxTime || "24:00:00";

        const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: "timeGridWeek",
          selectable: true,
          editable: false,
          timeZone: "Asia/Manila",
          headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
          },
          slotMinTime: slotMinTime, // Set slotMinTime
          slotMaxTime: slotMaxTime, // Set slotMaxTime
          events: {
            url: `/api/get_doctor_full_availability.php`,
            method: "GET",
            extraParams: {
              doctor_id: doctorId,
              consultation_type: consultationType,
            },
            failure: function () {
              alert("There was an error while fetching availability!");
            },
          },
          eventClick: function (info) {
            const clickedEvent = info.event;

            const selectedAppointmentId =
              RescheduleModule.getSelectedAppointmentId();
            if (!selectedAppointmentId) {
              alert(
                "Please select an appointment from the list before rescheduling."
              );
              return;
            }

            const availabilityId = clickedEvent.id;

            fetch(
              `/api/get_time_slot.php?doctor_id=${doctorId}&availability_id=${availabilityId}`
            )
              .then((response) => response.json())
              .then((timeSlotData) => {
                if (
                  timeSlotData &&
                  timeSlotData.data &&
                  timeSlotData.data.start_time &&
                  timeSlotData.data.end_time
                ) {
                  const selectedDate = timeSlotData.data.date;
                  const selectedStartTime = timeSlotData.data.start_time;
                  const selectedEndTime = timeSlotData.data.end_time;

                  const rescheduleConfirmation = confirm(
                    `Do you want to reschedule your appointment to ${selectedDate} at ${selectedStartTime} and ends at ${selectedEndTime}?`
                  );

                  if (rescheduleConfirmation) {
                    const requestData = {
                      appointment_id: selectedAppointmentId,
                      availability_id: availabilityId,
                      date: selectedDate,
                      start_time: selectedStartTime,
                      end_time: selectedEndTime,
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
                        if (data.status) {
                          alert("Appointment rescheduled successfully!");
                          calendar.refetchEvents(); // Refresh the calendar events
                        } else {
                          alert("Failed to reschedule appointment.");
                        }
                      })
                      .catch((error) => {
                        console.error("Error rescheduling appointment:", error);
                      });
                  }
                } else {
                  console.error(
                    "Time slot data is missing or invalid",
                    timeSlotData
                  );
                  alert("Failed to fetch time slot details. Please try again.");
                }
              })
              .catch((error) => {
                console.error("Error fetching time slot details:", error);
                alert("Error fetching time slot details. Please try again.");
              });
          },
        });

        calendar.render();
      })
      .catch((error) => {
        console.error("Error fetching slot times:", error);
        alert("Error loading calendar. Please try again.");
      });
  };

  return {
    loadCalendar,
  };
})();

// Initialization
RescheduleModule.init();
