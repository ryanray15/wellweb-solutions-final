// Function to load canceled appointments for the patient
const loadCanceledAppointments = (patientId) => {
  fetch(`/api/get_canceled_appointments.php?patient_id=${patientId}`)
    .then((response) => response.json())
    .then((data) => {
      const tableBody = document.getElementById("canceledAppointmentsTable");
      tableBody.innerHTML = ""; // Clear previous data

      data.forEach((appointment) => {
        const row = document.createElement("tr");

        // Doctor's Name Cell
        const doctorCell = document.createElement("td");
        doctorCell.className = "border px-4 py-2";
        doctorCell.textContent = "Dr. " + appointment.doctor_name;

        // Date Cell
        const dateCell = document.createElement("td");
        dateCell.className = "border px-4 py-2";
        dateCell.textContent = appointment.date;

        // Time Cell
        const timeCell = document.createElement("td");
        timeCell.className = "border px-4 py-2";
        timeCell.textContent =
          appointment.start_time + " - " + appointment.end_time;

        // Refund Status Cell
        const refundStatusCell = document.createElement("td");
        refundStatusCell.className = "border px-4 py-2";
        refundStatusCell.textContent =
          appointment.refund_status || "Not Requested";

        // Actions Cell
        const actionsCell = document.createElement("td");
        actionsCell.className = "border px-4 py-2";
        if (
          appointment.refund_status === null ||
          appointment.refund_status === "Not Requested"
        ) {
          const refundButton = document.createElement("button");
          refundButton.textContent = "Request Refund";
          refundButton.className =
            "bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded";
          refundButton.addEventListener("click", () => {
            requestRefund(appointment.appointment_id);
          });
          actionsCell.appendChild(refundButton);
        } else {
          actionsCell.textContent = "Refund " + appointment.refund_status;
        }

        row.appendChild(doctorCell);
        row.appendChild(dateCell);
        row.appendChild(timeCell);
        row.appendChild(refundStatusCell);
        row.appendChild(actionsCell);

        tableBody.appendChild(row);
      });
    })
    .catch((error) =>
      console.error("Error fetching canceled appointments:", error)
    );
};

// Function to request a refund
const requestRefund = (appointment_id) => {
  fetch("/api/request_refund.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ appointment_id }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.status) {
        alert("Refund requested successfully!");
        loadCanceledAppointments(patient_id); // Refresh appointments table
      } else {
        alert("Failed to request refund: " + data.message);
      }
    })
    .catch((error) => console.error("Error requesting refund:", error));
};

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  loadCanceledAppointments(patient_id);
});
