document.addEventListener("DOMContentLoaded", function () {
  // Login Form Handling
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", function (event) {
      event.preventDefault();
      const formData = new FormData(loginForm);
      const data = {
        email: formData.get("email"),
        password: formData.get("password"),
      };

      fetch("/api/login.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((result) => {
          alert(result.message); // Ensure the alert is shown
          if (result.status) {
            // Store user data in session
            sessionStorage.setItem("user_id", result.user_id);
            sessionStorage.setItem("role", result.role);

            // Redirect to dashboard or home page after successful login
            window.location.href = "/dashboard.php"; // Assuming a dashboard page
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again."); // Alert in case of an error
        });
    });
  }
});
