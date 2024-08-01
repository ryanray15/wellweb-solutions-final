// src/assets/js/main.js

document.addEventListener("DOMContentLoaded", function() {
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        registerForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(registerForm);
            const data = {
                name: formData.get("name"),
                email: formData.get("email"),
                password: formData.get("password"),
                role: formData.get("role"),
            };

            fetch("/api/register.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message); // Ensure the alert is shown
                if (result.status) {
                    window.location.href = "/login.html";
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again."); // Alert in case of an error
            });
        });
    }

    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", function(event) {
            event.preventDefault();
            const formData = new FormData(loginForm);
            const data = {
                email: formData.get("email"),
                password: formData.get("password"),
            };

            fetch("/api/login.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                alert(result.message); // Ensure the alert is shown
                if (result.status) {
                    // Redirect to dashboard or home page after successful login
                    window.location.href = "/dashboard.html"; // Assuming a dashboard page
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred. Please try again."); // Alert in case of an error
            });
        });
    }
});
