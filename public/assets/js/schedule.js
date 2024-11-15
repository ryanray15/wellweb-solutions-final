let selectedDoctorId = null;
let consultationType = null;
let specializationId = null;

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

// Function to fetch and populate doctors based on specialization and consultation type
function fetchDoctors(specializationIdValue, consultationTypeValue) {
  // Assign the values globally
  consultationType = consultationTypeValue;
  specializationId = specializationIdValue;

  console.log(
    `Consultation Type: ${consultationType}, Specialization ID: ${specializationId}`
  );
  fetch(
    `/api/get_doctors.php?specialization_id=${specializationId}&consultation_type=${consultationType}`
  )
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
          "<p>No doctors available for this specialization and consultation type.</p>";
      }
    })
    .catch((error) => console.error("Error fetching doctors:", error));
}

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

      console.log(`Doctor ID: ${selectedDoctorId}`); // Debugging
    });
  });
}

// Function to reload the calendar for the schedule page
function reloadScheduleCalendar() {
  const doctorId = selectedDoctorId;
  const consultationType = this.consultationType;
  const specializationId = this.specializationId;
  const patientId = patient_id;
  loadDoctorCalendar(doctorId, consultationType, specializationId, patientId);
}

// Function to load and render doctor's calendar in the schedule page
function loadDoctorCalendar(
  doctorId,
  consultationType,
  specializationId,
  patientId
) {
  const calendarEl = document.getElementById("calendar");

  if (calendarEl) {
    console.log(
      `Doctor ID: ${doctorId}, Consultation Type: ${consultationType}, Specialization ID: ${specializationId}`
    );
    fetch(
      `/api/get_availability.php?doctor_id=${doctorId}&consultation_type=${consultationType}&specialization_id=${specializationId}`
    )
      .then((response) => response.json())
      .then((data) => {
        console.log("Fetched events:", data);
        const calendar = new FullCalendar.Calendar(calendarEl, {
          initialView: "timeGridWeek",
          selectable: true,
          timeZone: "Asia/Manila",
          headerToolbar: {
            left: "prev,next today",
            center: "title",
            right: "dayGridMonth,timeGridWeek,timeGridDay",
          },
          events: data.events,
          eventClick: function (info) {
            const event = info.event;
            handleEventSelection(event, doctorId, patientId);
          },
        });

        calendar.render();
      })
      .catch((error) => console.error("Error loading calendar:", error));
  } else {
    console.error("Calendar element not found");
  }
}

// Function to fetch the current user session
function fetchCurrentSession() {
  return fetch("/api/get_session.php")
    .then((response) => response.json())
    .then((data) => {
      if (data && data.status && data.user_id) {
        return data.user_id; // Return the patient ID
      } else {
        console.error("Failed to fetch user session or user is not logged in.");
        alert("You must be logged in to book an appointment.");
        return null;
      }
    })
    .catch((error) => {
      console.error("Error fetching session data:", error);
      return null;
    });
}

// Function to handle event selection, fetch accurate time slot details, and confirm booking
async function handleEventSelection(event, doctorId) {
  const patientId = await fetchCurrentSession(); // Fetch the patientId directly

  if (!patientId) {
    console.error(
      "Patient ID is not defined when calling handleEventSelection."
    );
    alert(
      "There was an issue identifying the patient. Please log in and try again."
    );
    return;
  }

  const availabilityId = event.id;

  // Fetch the time slot details using the availabilityId
  fetch(
    `/api/get_time_slot.php?doctor_id=${doctorId}&availability_id=${availabilityId}`
  )
    .then((response) => response.text())
    .then((text) => {
      console.log("Raw time slot response:", text);
      try {
        const timeSlotData = JSON.parse(text);
        if (
          timeSlotData &&
          timeSlotData.data &&
          timeSlotData.data.start_time &&
          timeSlotData.data.end_time
        ) {
          const selectedDate = timeSlotData.data.date;
          const selectedStartTime = timeSlotData.data.start_time;
          const selectedEndTime = timeSlotData.data.end_time;
          const serviceId = document.getElementById("service_id").value;

          const requestData = {
            patient_id: patientId, // Ensure this is directly assigned
            doctor_id: doctorId,
            service_id: serviceId,
            availability_id: event.id,
            date: selectedDate,
            start_time: selectedStartTime,
            end_time: selectedEndTime,
            referrer: document.referrer,
          };

          console.log("Booking Request Data:", requestData);

          const confirmation = confirm(
            `Do you want to book this time slot: ${event.title} - Available on ${selectedDate} at ${selectedStartTime} and ends at ${selectedEndTime}?`
          );

          if (confirmation) {
            createCheckoutSession(requestData);
          }
        } else {
          console.error("Time slot data is missing or invalid", timeSlotData);
          alert("Failed to fetch time slot details. Please try again.");
        }
      } catch (error) {
        console.error("Error parsing time slot response:", error, text);
        alert("Failed to parse time slot details. Please try again.");
      }
    })
    .catch((error) => {
      console.error("Error fetching time slot details:", error);
      alert("Error fetching time slot details. Please try again.");
    });
}

// Function to create a checkout session
function createCheckoutSession(requestData) {
  // Make an API call to create the checkout session
  fetch("/api/create_checkout_session.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(requestData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.checkout_url) {
        // Redirect to Stripe Checkout page
        window.location.href = data.checkout_url;
      } else {
        alert("Failed to create checkout session. Please try again.");
      }
    })
    .catch((error) => console.error("Error creating checkout session:", error));
}

// Function to dynamically generate time slots based on the doctor's availability
function updateTimeSlots(duration, startTime, endTime) {
  const timeInput = document.getElementById("time");
  let timeSlots = [];

  // Parse start and end times using moment.js (or native Date object)
  let currentTime = moment(startTime, "HH:mm");
  let endMomentTime = moment(endTime, "HH:mm");

  while (currentTime.isBefore(endMomentTime)) {
    timeSlots.push(currentTime.format("HH:mm"));
    currentTime.add(duration, "minutes");
  }

  timeInput.innerHTML = timeSlots
    .map((time) => `<option value="${time}">${time}</option>`)
    .join("");
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

// Function to map service_id to consultation_type
function determineConsultationType(serviceId) {
  if (serviceId == 1) {
    return "online"; // Example for online consultation
  } else if (serviceId == 2) {
    return "physical"; // Example for physical consultation
  }
  return "";
}

// Ensure the form submission and scheduling logic still works
document.addEventListener("DOMContentLoaded", function () {
  // WebSocket connection setup
  const socket = new WebSocket("ws://localhost:8080"); // Replace with your WebSocket server address

  socket.onopen = () => {
    console.log("Connected to WebSocket server");
  };

  socket.onmessage = (event) => {
    const message = JSON.parse(event.data);

    if (message.type === "expired_availabilities") {
      console.log("Received expired_availabilities message:", message);

      // Reload calendar when an availability expires
      if (selectedDoctorId) {
        loadDoctorCalendar(
          selectedDoctorId,
          consultationType,
          specializationId,
          patient_id
        );
      } else {
        console.error("Doctor ID is missing, cannot reload calendar.");
      }
    }
  };

  socket.onclose = () => {
    console.log("Disconnected from WebSocket server");
  };

  // Existing functionality
  fetchCurrentSession().then((patientId) => {
    if (patientId) {
      // Load services when the form is ready
      fetchServicesDropdown();

      // Add event listener to load specializations when service is selected
      document
        .getElementById("service_id")
        .addEventListener("change", function () {
          const serviceId = this.value;
          fetchSpecializationsDropdown(serviceId);
        });

      // Adjust event listener to include consultation type
      document
        .getElementById("specialization_id")
        .addEventListener("change", function () {
          const specializationId = this.value;
          const consultationType = document.getElementById("service_id").value;

          if (specializationId && consultationType) {
            fetchDoctors(specializationId, consultationType);
          } else {
            console.error("Specialization ID or Consultation Type is missing");
          }
        });

      // Load the calendar with the patientId
      if (selectedDoctorId) {
        loadDoctorCalendar(
          selectedDoctorId,
          consultationType,
          specializationId,
          patientId
        );
      } else {
        console.error("Please select a doctor before loading the calendar.");
      }
    } else {
      console.error("Failed to get patient session data.");
    }
  });
});
