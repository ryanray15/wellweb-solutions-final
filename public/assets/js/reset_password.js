document.addEventListener("DOMContentLoaded", function () {
  // Handle Reset Password Form Submission
  const resetPasswordForm = document.getElementById("resetPasswordForm");
  resetPasswordForm.addEventListener("submit", function (event) {
    event.preventDefault();

    const newPassword = document.getElementById("new_password").value;
    const confirmPassword = document.getElementById("confirm_password").value;

    if (newPassword !== confirmPassword) {
      alert("Passwords do not match!");
      return;
    }

    // Fetch the password update endpoint
    fetch("/api/reset_password.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        new_password: newPassword,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status) {
          alert("Password reset successfully!");
          window.location.href = "/dashboard.php"; // Redirect to dashboard on success
        } else {
          alert("Password reset failed: " + data.message);
        }
      })
      .catch((error) => console.error("Error:", error));
  });
});
