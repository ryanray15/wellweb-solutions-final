// websocket_server.js

const WebSocket = require("ws");
const mysql = require("mysql");

// Set up WebSocket server on port 8080
const wss = new WebSocket.Server({ port: 8080 });

// Set up MySQL connection
const db = mysql.createConnection({
  host: "localhost",
  user: "root",
  password: "",
  database: "capstone_system_final", // Update to your database name
});

db.connect((err) => {
  if (err) throw err;
  console.log("Connected to the MySQL database.");
});

// Function to check for expired availabilities
const checkExpiredAvailabilities = () => {
  // Query to find availabilities that are past the current date and time
  const query = `
    SELECT availability_id 
    FROM doctor_availability 
    WHERE CONCAT(date, ' ', end_time) < NOW()
  `;

  db.query(query, (err, results) => {
    if (err) throw err;

    // If there are expired availabilities, delete them and notify clients
    if (results.length > 0) {
      const expiredIds = results.map((row) => row.availability_id);

      // Delete expired availabilities from the database
      const deleteQuery =
        "DELETE FROM doctor_availability WHERE availability_id IN (?)";
      db.query(deleteQuery, [expiredIds], (deleteErr) => {
        if (deleteErr) throw deleteErr;
        console.log(`Deleted expired availabilities: ${expiredIds.join(", ")}`);

        // Notify all connected clients about the expired availabilities
        wss.clients.forEach((client) => {
          if (client.readyState === WebSocket.OPEN) {
            client.send(
              JSON.stringify({
                type: "expired_availabilities",
                expiredIds,
              })
            );
          }
        });
      });
    }
  });
};

// Function to check for completed video calls and update appointments
const checkCompletedVideoCalls = () => {
  // Query to find completed video calls where both participants have left
  // and the entry hasn't been processed yet
  const query = `
    SELECT meeting_id, doctor_id, patient_id
    FROM video_call_history
    WHERE status = 'completed' AND end_time IS NOT NULL AND status_checked = FALSE
  `;

  db.query(query, (err, results) => {
    if (err) throw err;

    // If there are completed calls, update the related appointments
    if (results.length > 0) {
      results.forEach((call) => {
        const { meeting_id, doctor_id, patient_id } = call;

        // Update the appointment status to 'completed'
        const updateQuery = `
          UPDATE appointments
          SET status = 'completed'
          WHERE doctor_id = ? AND patient_id = ? AND meeting_id = ?
        `;

        db.query(
          updateQuery,
          [doctor_id, patient_id, meeting_id],
          (updateErr) => {
            if (updateErr) {
              console.error("Error updating appointment status:", updateErr);
            } else {
              console.log(
                `Appointment marked as completed for meeting_id: ${meeting_id}`
              );

              // Mark the video call as processed in the video_call_history table
              const markProcessedQuery = `
                UPDATE video_call_history
                SET status_checked = TRUE
                WHERE meeting_id = ? AND doctor_id = ? AND patient_id = ?
              `;

              db.query(
                markProcessedQuery,
                [meeting_id, doctor_id, patient_id],
                (markErr) => {
                  if (markErr) {
                    console.error(
                      "Error marking video call as processed:",
                      markErr
                    );
                  } else {
                    console.log(
                      `Video call marked as processed for meeting_id: ${meeting_id}`
                    );

                    // Notify all connected clients about the completed appointment
                    wss.clients.forEach((client) => {
                      if (client.readyState === WebSocket.OPEN) {
                        client.send(
                          JSON.stringify({
                            type: "check_call_completion",
                            meeting_id,
                            doctor_id,
                            patient_id,
                          })
                        );
                      }
                    });
                  }
                }
              );
            }
          }
        );
      });
    }
  });
};

// Periodically check for expired availabilities every minute
setInterval(checkExpiredAvailabilities, 60000);

// Periodically check for completed video calls every minute
setInterval(checkCompletedVideoCalls, 60000);

wss.on("connection", (ws) => {
  console.log("Client connected to WebSocket.");

  // Optional: Send a welcome message or initial data if needed
  ws.send(JSON.stringify({ message: "Welcome to the WebSocket server!" }));

  ws.on("close", () => {
    console.log("Client disconnected from WebSocket.");
  });
});

console.log("WebSocket server is running on ws://localhost:8080");
