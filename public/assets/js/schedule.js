// schedule.js

// Function to fetch and populate doctors dropdown
function fetchDoctorsDropdown() {
  fetch("/api/get_doctors.php")
    .then((response) => response.json())
    .then((data) => {
      const doctorSelect = document.getElementById("doctor_id");
      data.forEach((doctor) => {
        const option = document.createElement("option");
        option.value = doctor.user_id;
        option.text = `Dr. ${doctor.name}`;
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

// Handle disabling unavailable slots
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

// Load the schedule page data when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  if (document.getElementById("doctor_id")) fetchDoctorsDropdown();
});
