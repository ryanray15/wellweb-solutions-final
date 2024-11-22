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

db.query("SELECT 1 + 1 AS solution", (err, results) => {
  if (err) {
    console.error("Database connection test failed:", err);
  } else {
    console.log(
      "Database connection test successful. Result:",
      results[0].solution
    );
  }
});

// Store connected clients
const clients = new Map();

// Function to check for expired availabilities
const checkExpiredAvailabilities = () => {
  const query = `
    SELECT availability_id 
    FROM doctor_availability 
    WHERE CONCAT(date, ' ', end_time) < NOW()
  `;

  db.query(query, (err, results) => {
    if (err) throw err;

    if (results.length > 0) {
      const expiredIds = results.map((row) => row.availability_id);

      const deleteQuery = `
        DELETE FROM doctor_availability WHERE availability_id IN (?)
      `;
      db.query(deleteQuery, [expiredIds], (deleteErr) => {
        if (deleteErr) throw deleteErr;
        console.log(`Deleted expired availabilities: ${expiredIds.join(", ")}`);

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
  const query = `
    SELECT meeting_id, doctor_id, patient_id
    FROM video_call_history
    WHERE status = 'completed' AND end_time IS NOT NULL AND status_checked = FALSE
  `;

  db.query(query, (err, results) => {
    if (err) throw err;

    if (results.length > 0) {
      results.forEach((call) => {
        const { meeting_id, doctor_id, patient_id } = call;

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

// Updated WebSocket Message Handling
wss.on("connection", (ws) => {
  console.log("Client connected to WebSocket.");

  ws.on("message", (message) => {
    console.log("Raw WebSocket message received:", message);

    try {
      const data = JSON.parse(message);
      console.log("Parsed WebSocket message:", data);

      if (data.type === "init") {
        // Replace old connection with new one if already exists
        if (clients.has(data.userId)) {
          const oldSocket = clients.get(data.userId);
          if (oldSocket.readyState === WebSocket.OPEN) {
            oldSocket.close();
          }
        }
        clients.set(data.userId, ws);
        console.log("Active clients:", Array.from(clients.keys()));
        console.log(`User ${data.userId} connected.`);
      } else if (data.type === "message") {
        console.log("Processing message:", data);

        const insertQuery = `
        INSERT INTO messages (sender_id, receiver_id, content, timestamp, is_read)
        VALUES (?, ?, ?, NOW(), 0)
        `;

        db.query(
          insertQuery,
          [data.senderId, data.receiverId, data.content],
          (err, result) => {
            if (err) {
              console.error("Error saving message to database:", err);
            } else {
              console.log(
                "Message from",
                data.senderId,
                "to",
                data.receiverId,
                "saved."
              );

              // Notify the receiver
              const receiverSocket = clients.get(parseInt(data.receiverId, 10));
              if (
                receiverSocket &&
                receiverSocket.readyState === WebSocket.OPEN
              ) {
                receiverSocket.send(JSON.stringify(data)); // Real-time message
              }
            }
          }
        );
      }
    } catch (err) {
      console.error("Error parsing WebSocket message:", err);
    }
  });

  ws.on("close", () => {
    console.log("Client disconnected from WebSocket.");
    for (let [userId, socket] of clients.entries()) {
      if (socket === ws) {
        clients.delete(userId);
        console.log(`User ${userId} disconnected.`);
        break;
      }
    }
  });
});

// Periodically check for expired availabilities every minute
setInterval(checkExpiredAvailabilities, 60000);

// Periodically check for completed video calls every minute
setInterval(checkCompletedVideoCalls, 60000);

console.log("WebSocket server is running on ws://localhost:8080");
