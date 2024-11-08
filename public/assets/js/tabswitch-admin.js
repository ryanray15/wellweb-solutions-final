// Function to reset tabs to default styles
function resetTabs() {
  const tabs = ["specializationsTab", "usersTab", "verificationTab"];
  tabs.forEach((tab) => {
    const button = document.getElementById(tab);
    button.classList.remove("bg-blue-600", "text-black");
    button.classList.add("bg-white", "text-blue-600");
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
    this.classList.add("bg-blue-600", "text-black"); // Set active tab style
  });

document.getElementById("usersTab").addEventListener("click", function () {
  resetTabs(); // Reset styles for all tabs
  document.getElementById("usersContent").classList.remove("hidden");
  document.getElementById("specializationsContent").classList.add("hidden");
  document.getElementById("verificationContent").classList.add("hidden");
  this.classList.add("bg-blue-600", "text-black"); // Set active tab style
});

document
  .getElementById("verificationTab")
  .addEventListener("click", function () {
    resetTabs(); // Reset styles for all tabs
    document.getElementById("verificationContent").classList.remove("hidden");
    document.getElementById("specializationsContent").classList.add("hidden");
    document.getElementById("usersContent").classList.add("hidden");
    this.classList.add("bg-blue-600", "text-black"); // Set active tab style
  });
