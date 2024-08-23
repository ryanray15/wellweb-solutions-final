let currentStep = 0; // Current step is set to be the first step (0)
showStep(currentStep); // Display the current step

function showStep(step) {
  // This function will display the specified step of the form
  const steps = document.getElementsByClassName("step");
  steps[step].style.display = "block";

  // Fix the Previous/Next buttons:
  if (step === 0) {
    document.getElementById("prevBtn").style.display = "none";
  } else {
    document.getElementById("prevBtn").style.display = "inline";
  }

  if (step === steps.length - 1) {
    document.getElementById("nextBtn").innerHTML = "Submit";
  } else {
    document.getElementById("nextBtn").innerHTML = "Next";
  }
}

function nextPrev(n) {
  // This function will figure out which step to display
  const steps = document.getElementsByClassName("step");

  // Hide the current step:
  steps[currentStep].style.display = "none";

  // Increase or decrease the current step by 1:
  currentStep = currentStep + n;

  // If you have reached the end of the form...
  if (currentStep >= steps.length) {
    document.getElementById("scheduleForm").submit();
    return false;
  }

  // Otherwise, display the correct step:
  showStep(currentStep);
}
