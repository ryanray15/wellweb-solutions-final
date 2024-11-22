document.addEventListener("DOMContentLoaded", function () {
  // Your existing JavaScript code here
  function resetTabs() {
    const tabs = ["specializationsTab", "usersTab", "verificationTab"];
    tabs.forEach((tab) => {
      document
        .getElementById(tab)
        .classList.remove("bg-blue-900", "text-black");
      document.getElementById(tab).classList.add("bg-blue-600", "text-white");
    });
  }

  document
    .getElementById("specializationsTab")
    .addEventListener("click", function () {
      resetTabs(); // Reset styles for all tabs
      document
        .getElementById("specializationsContent")
        .classList.remove("hidden");
      document.getElementById("usersContent").classList.add("hidden");
      document.getElementById("verificationContent").classList.add("hidden");
      this.classList.add("bg-blue-900", "text-white"); // Set active tab style
    });

  document.getElementById("usersTab").addEventListener("click", function () {
    resetTabs(); // Reset styles for all tabs
    document.getElementById("usersContent").classList.remove("hidden");
    document.getElementById("specializationsContent").classList.add("hidden");
    document.getElementById("verificationContent").classList.add("hidden");
    this.classList.add("bg-blue-900", "text-white"); // Set active tab style
  });

  document
    .getElementById("verificationTab")
    .addEventListener("click", function () {
      resetTabs(); // Reset styles for all tabs
      document.getElementById("verificationContent").classList.remove("hidden");
      document.getElementById("specializationsContent").classList.add("hidden");
      document.getElementById("usersContent").classList.add("hidden");
      this.classList.add("bg-blue-900", "text-white"); // Set active tab style
    });
});
