document
  .getElementById("appointmentsTab")
  .addEventListener("click", function () {
    document.getElementById("appointmentsContent").classList.remove("hidden");
    document.getElementById("availabilityContent").classList.add("hidden");
    this.classList.add("bg-blue-900", "text-white");
    document
      .getElementById("availabilityTab")
      .classList.remove("bg-blue-900", "text-black");
  });

document
  .getElementById("availabilityTab")
  .addEventListener("click", function () {
    document.getElementById("availabilityContent").classList.remove("hidden");
    document.getElementById("appointmentsContent").classList.add("hidden");
    this.classList.add("bg-blue-900", "text-white");
    document
      .getElementById("appointmentsTab")
      .classList.remove("bg-blue-900", "text-black");
  });
