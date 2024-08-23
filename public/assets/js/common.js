// common.js

// Profile Dropdown
const profileDropdown = document.getElementById("profileDropdown");
const dropdownMenu = document.getElementById("dropdownMenu");

profileDropdown.addEventListener("click", () => {
  dropdownMenu.classList.toggle("hidden");
});

// Logout functionality
const logoutButton = document.getElementById("logout");
if (logoutButton) {
  logoutButton.addEventListener("click", () => {
    fetch("/api/logout.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status) {
          sessionStorage.removeItem("user_id");
          sessionStorage.removeItem("role");
          window.location.href = "/index.php";
        } else {
          alert("Failed to log out. Please try again.");
        }
      })
      .catch((error) => console.error("Error:", error));
  });
}
