document.addEventListener("DOMContentLoaded", function () {
  const openDashboardButton = document.getElementById("openExpressDashboard");

  openDashboardButton.addEventListener("click", () => {
    fetch("/api/generate_express_dashboard_link.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.status === 200) {
          // Redirect to the Stripe Express Dashboard login URL
          window.location.href = data.login_url;
        } else {
          alert(data.message || "Failed to open Express Dashboard");
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        alert("An error occurred while opening the dashboard");
      });
  });
});
