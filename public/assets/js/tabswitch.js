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
            pane.style.opacity = '0'; // Reset opacity for animation
            pane.style.transform = 'translateX(-20px)'; // Reset position for animation
        });

        // Show the selected tab pane
        const selectedPane = document.getElementById(tabId);
        if (selectedPane) {
            selectedPane.classList.add("active");
            // Trigger reflow to restart the animation
            requestAnimationFrame(() => {
                selectedPane.style.opacity = '1'; // Fade in
                selectedPane.style.transform = 'translateX(0)'; // Move to original position
            });
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