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

// Periodically check for expired availabilities every minute
setInterval(checkExpiredAvailabilities, 60000);

wss.on("connection", (ws) => {
  console.log("Client connected to WebSocket.");

  // Optional: Send a welcome message or initial data if needed
  ws.send(JSON.stringify({ message: "Welcome to the WebSocket server!" }));

  ws.on("close", () => {
    console.log("Client disconnected from WebSocket.");
  });
});

console.log("WebSocket server is running on ws://localhost:8080");
