let selectedDoctorId = null; // This will store the selected doctor ID

// Function to fetch and populate services
function fetchServicesDropdown() {
  fetch("/api/get_services.php")
    .then((response) => response.json())
    .then((data) => {
      const serviceSelect = document.getElementById("service_id");
      serviceSelect.innerHTML = '<optgroup label="Services"></optgroup>'; // Default option

      if (data.length > 0) {
        data.forEach((service) => {
          const option = document.createElement("option");
          option.value = service.service_id; // Ensure the correct field is used here
          option.text = service.name;
          serviceSelect.appendChild(option);
        });

        console.log("Service dropdown populated:", serviceSelect.innerHTML); // Debugging
      } else {
        serviceSelect.innerHTML =
          "<option value=''>No services available</option>";
      }
    })
    .catch((error) => console.error("Error fetching services:", error));
}

// Function to fetch and populate specializations
function fetchSpecializationsDropdown(serviceId) {
  fetch(`/api/get_specializations.php?service_id=${serviceId}`)
    .then((response) => response.json())
    .then((data) => {
      const specializationSelect = document.getElementById("specialization_id");
      specializationSelect.innerHTML =
        '<optgroup label="Specializations"></optgroup>'; // Default option

      if (data.length > 0) {
        data.forEach((specialization) => {
          const option = document.createElement("option");
          option.value = specialization.id;
          option.text = specialization.name;
          specializationSelect.appendChild(option);
        });
      } else {
        specializationSelect.innerHTML =
          "<option value=''>No specializations available</option>";
      }
    })
    .catch((error) => console.error("Error fetching specializations:", error));
}

// Function to fetch and populate doctors based on specialization
function fetchDoctors(specializationId) {
  fetch(`/api/get_doctors.php?specialization_id=${specializationId}`)
    .then((response) => response.json())
    .then((data) => {
      const doctorsContainer = document.getElementById("doctorsContainer");
      doctorsContainer.innerHTML = ""; // Clear the container first

      if (data.length > 0) {
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

        doctorsContainer.classList.remove("hidden"); // Ensure the doctor grid is shown
        attachDoctorClickHandlers(); // Attach click handlers to the new doctor cards
      } else {
        doctorsContainer.innerHTML =
          "<p>No doctors available for this specialization.</p>";
      }
    })
    .catch((error) => console.error("Error fetching doctors:", error));
}

// Attach click handlers to doctor cards
function attachDoctorClickHandlers() {
  const doctorCards = document.querySelectorAll(".doctor-card");

  doctorCards.forEach((card) => {
    card.addEventListener("click", function () {
      // Remove the highlight from all cards
      doctorCards.forEach((c) => c.classList.remove("border-green-500"));

      // Highlight the selected card
      this.classList.add("border-green-500");

      // Store the selected doctor ID
      selectedDoctorId = this.getAttribute("data-doctor-id");

      // Debugging: Check if the doctor ID is being correctly captured
      console.log("Selected Doctor ID:", selectedDoctorId);
    });
  });
}

// Load Calendar (called from multistep.js when moving to the final step)
function loadCalendar(doctorId) {
  if (!doctorId) {
    console.error("Doctor ID is not selected.");
    return;
  }

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

  // Check for full-day unavailability
  dateInput.addEventListener("change", function () {
    const selectedDate = this.value;
    if (unavailableDays.includes(selectedDate)) {
      alert(
        "This date is unavailable for any appointments. Please choose another date."
      );
      this.value = "";
    }
  });

  // Check for specific time unavailability
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

// Function to handle form submission and scheduling
function handleScheduleAppointment(patientId) {
  const scheduleButton = document.querySelector('button[type="submit"]');
  scheduleButton.addEventListener("click", function (e) {
    e.preventDefault(); // Prevent the form from submitting immediately

    const selectedDate = document.getElementById("date").value;
    const selectedTime = document.getElementById("time").value;
    const serviceId = document.getElementById("service_id").value;

    console.log("Selected Service ID:", serviceId); // Debugging log for service_id

    if (!selectedDate || !selectedTime || !selectedDoctorId || !serviceId) {
      alert("Please fill in all fields");
      return;
    }

    const requestData = {
      patient_id: patientId, // Use the passed patient ID
      doctor_id: selectedDoctorId, // Use the stored doctor ID
      service_id: serviceId,
      date: selectedDate,
      time: selectedTime,
    };

    console.log("Request Data:", requestData); // Debugging line
    fetch("/api/schedule_appointment.php", {
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
}

// Ensure the form submission and scheduling logic still works
document.addEventListener("DOMContentLoaded", function () {
  checkUserSession().then((sessionData) => {
    if (sessionData.status && sessionData.user_id) {
      // Load services when the form is ready
      fetchServicesDropdown();

      // Add event listener to load specializations when service is selected
      document
        .getElementById("service_id")
        .addEventListener("change", function () {
          const serviceId = this.value;
          fetchSpecializationsDropdown(serviceId);
        });

      // Add event listener to load doctors when specialization is selected
      document
        .getElementById("specialization_id")
        .addEventListener("change", function () {
          const specializationId = this.value;
          fetchDoctors(specializationId);
        });

      // Attach the schedule button functionality
      handleScheduleAppointment(sessionData.user_id);
    }
  });
});
