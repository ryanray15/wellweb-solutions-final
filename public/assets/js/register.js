// register.js

document.addEventListener("DOMContentLoaded", function () {
  const roleSelect = document.getElementById("role");
  const specializationContainer = document.getElementById(
    "specialization-container"
  );
  const specializationSelect = document.getElementById("specialization");
  const addressLabel = document.getElementById("addressLabel");

  if (
    roleSelect &&
    specializationContainer &&
    specializationSelect &&
    addressLabel
  ) {
    function fetchSpecializationsForRegistration() {
      fetch("/api/get_specializations.php")
        .then((response) => response.json())
        .then((data) => {
          specializationSelect.innerHTML = ""; // Clear previous options
          if (data.length > 0) {
            data.forEach((spec) => {
              const option = document.createElement("option");
              option.value = spec.id;
              option.text = spec.name;
              specializationSelect.appendChild(option);
            });
          } else {
            specializationSelect.innerHTML =
              "<option value=''>No specializations available</option>";
          }
        })
        .catch((error) =>
          console.error("Error fetching specializations:", error)
        );
    }

    roleSelect.addEventListener("change", function () {
      if (this.value === "doctor") {
        specializationContainer.classList.remove("hidden");
        fetchSpecializationsForRegistration();
        addressLabel.textContent = "Clinic Address:";
      } else {
        specializationContainer.classList.add("hidden");
        specializationSelect.innerHTML = ""; // Clear the specializations if not a doctor
        addressLabel.textContent = "Address:";
      }
    });

    // Initial check to set up the form if the role is pre-selected (e.g., from back navigation)
    if (roleSelect.value === "doctor") {
      specializationContainer.classList.remove("hidden");
      fetchSpecializationsForRegistration();
      addressLabel.textContent = "Clinic Address:";
    } else {
      specializationContainer.classList.add("hidden");
      addressLabel.textContent = "Address:";
    }
  }

  // Handle Register Form Submission
  const registerForm = document.getElementById("registerForm");
  if (registerForm) {
    registerForm.addEventListener("submit", function (event) {
      event.preventDefault(); // Prevent the default form submission

      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirm_password").value;

      if (password !== confirmPassword) {
        alert("Passwords do not match!");
        return; // Exit the function without submitting the form
      }

      const formData = new FormData(registerForm);
      const data = {
        first_name: formData.get("first_name"),
        middle_initial: formData.get("middle_initial"),
        last_name: formData.get("last_name"),
        email: formData.get("email"),
        password: formData.get("password"),
        role: formData.get("role"),
        gender: formData.get("gender"),
        contact_number: formData.get("contact_number"),
        address: formData.get("address"),
        specializations: Array.from(specializationSelect.selectedOptions).map(
          (option) => option.value
        ),
      };

      fetch("/api/register.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((result) => {
          if (result.status) {
            alert("Registration successful!");
            window.location.href = "/login.html";
          } else {
            alert("Registration failed: " + result.message);
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          alert("An error occurred. Please try again.");
        });
    });
  }
});
