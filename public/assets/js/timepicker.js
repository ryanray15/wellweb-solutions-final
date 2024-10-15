$(document).ready(function () {
  // Function to initialize timepicker with dynamic interval
  function initializeTimepicker(interval) {
    $("#start_time").timepicker({
      timeFormat: "h:mm p",
      interval: interval, // Dynamic interval
      minTime: "8:00am",
      maxTime: "6:00pm",
      defaultTime: "8:00am",
      startTime: "8:00am",
      dynamic: false,
      dropdown: true,
      scrollbar: true,
    });

    $("#end_time").timepicker({
      timeFormat: "h:mm p",
      interval: interval, // Dynamic interval
      minTime: "8:00am",
      maxTime: "6:00pm",
      defaultTime: "9:00am",
      startTime: "8:00am",
      dynamic: false,
      dropdown: true,
      scrollbar: true,
    });
  }

  // Initialize with default consultation duration (30 mins)
  let consultationDuration = $("#consultation_duration").val() || 30;
  initializeTimepicker(consultationDuration);

  // When consultation duration changes, reinitialize the timepicker
  $("#consultation_duration").on("change", function () {
    consultationDuration = $(this).val();
    console.log("Consultation Duration Changed to: " + consultationDuration);

    // Disable and unbind previous timepicker
    $("#start_time, #end_time").timepicker("destroy"); // Proper way to destroy timepicker

    // Reinitialize with new interval
    initializeTimepicker(consultationDuration);
  });
});
