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
                if (result.status) {
                    alert(result.message);
                    window.location.href = "/login.html";
                } else {
                    alert(result.message);
                }
            })
            .catch(error => console.error("Error:", error));
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
                if (result.status) {
                    alert(result.message);
                    // Redirect to dashboard or home page after successful login
                } else {
                    alert(result.message);
                }
            })
            .catch(error => console.error("Error:", error));
        });
    }
});
