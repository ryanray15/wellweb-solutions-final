document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("consultation-details-form");

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const rate = document.getElementById("consultation_rate").value;
    const openTime = document.getElementById("clinic_open_time").value;
    const closeTime = document.getElementById("clinic_close_time").value;

    fetch("/api/save_consultation_details.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        rate: rate,
        openTime: openTime,
        closeTime: closeTime,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          alert("Details saved successfully!");
          updateCalendar(openTime, closeTime);
        } else {
          alert("Failed to save details.");
        }
      })
      .catch((error) => console.error("Error:", error));
  });

  function updateCalendar(openTime, closeTime) {
    const calendarEl = document.getElementById("calendar");
    if (calendarEl) {
      const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: "timeGridWeek",
        slotMinTime: openTime,
        slotMaxTime: closeTime,
        headerToolbar: {
          left: "prev,next today",
          center: "title",
          right: "dayGridMonth,timeGridWeek,timeGridDay",
        },
        events: [],
      });
      calendar.render();
    }
  }
});
