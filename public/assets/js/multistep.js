let currentStep = 0; // Current step is set to be the first step (0)

showStep(currentStep); // Display the current step

function showStep(step) {
  const steps = document.getElementsByClassName("step");
  steps[step].style.display = "block"; // Show the current step

  // Fix the Previous/Next buttons:
  if (step === 0) {
    document.getElementById("prevBtn").style.display = "none";
  } else {
    document.getElementById("prevBtn").style.display = "inline";
  }

  if (step === steps.length - 1) {
    document.getElementById("nextBtn").style.display = "none"; // Hide the Next button on the final step
    if (selectedDoctorId && consultationType && specializationId) {
      console.log(
        `Loading Calendar with Doctor ID: ${selectedDoctorId}, Consultation Type: ${consultationType}, Specialization ID: ${specializationId}`
      );
      loadDoctorCalendar(selectedDoctorId, consultationType, specializationId);
    } else {
      console.error(
        "Doctor ID, consultation type, or specialization ID is missing."
      );
    }
  } else {
    document.getElementById("nextBtn").style.display = "inline";
    document.getElementById("nextBtn").innerHTML = "Next";
  }
}

function nextPrev(n) {
  const steps = document.getElementsByClassName("step");

  // Hide the current step:
  steps[currentStep].style.display = "none";

  // Increase or decrease the current step by 1:
  currentStep += n;

  // If you have reached the end of the form...
  if (currentStep >= steps.length) {
    document.getElementById("scheduleForm").submit();
    return false;
  }

  // Otherwise, display the correct step:
  showStep(currentStep);

  // If moving back and the calendar was loaded, clear it
  if (n < 0 && currentStep < steps.length - 1) {
    document.getElementById("calendar").innerHTML = ""; // Clear the calendar when moving back
  }
}
