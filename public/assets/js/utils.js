// utils.js

// Function to check if the user is logged in
function checkUserSession() {
  return fetch("/api/get_session.php")
    .then((response) => response.json())
    .then((data) => {
      if (!data.status || !data.user_id) {
        // Redirect to login page if user is not logged in
        // window.location.href = "/login.html"; // Ensure this line is uncommented
      }
      return data;
    })
    .catch((error) => {
      console.error("Error fetching session data:", error);
      return { status: false };
    });
}
