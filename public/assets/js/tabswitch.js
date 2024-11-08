// JavaScript to handle tab switching
document.addEventListener("DOMContentLoaded", function () {
  // Select all tab links
  const tabLinks = document.querySelectorAll(".tab-link");

  // Select all tab content panels
  const tabPanes = document.querySelectorAll(".tab-pane");

  // Function to show the corresponding tab content
  function showTab(tabId) {
    // Hide all tab panes
    tabPanes.forEach(function (pane) {
      pane.classList.remove("active");
    });

    // Show the selected tab pane
    const selectedPane = document.getElementById(tabId);
    if (selectedPane) {
      selectedPane.classList.add("active");
    }

    // Highlight the active tab link
    tabLinks.forEach(function (link) {
      link.classList.remove("active");
    });

    const activeLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
    if (activeLink) {
      activeLink.classList.add("active");
    }
  }

  // Add event listeners to all tab links
  tabLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      const tabId = link.getAttribute("data-tab");
      showTab(tabId);
    });
  });

  // Initially show the "Your Appointments" tab by default
  showTab("appointments");
});

//TODO!!!
