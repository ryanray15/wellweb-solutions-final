<?php
// src/models/Appointment.php

class Appointment {
    private $conn;
    private $table = 'Appointments';

    public $appointment_id;
    public $patient_id;
    public $doctor_id;
    public $service_id;
    public $date;
    public $time;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " (patient_id, doctor_id, service_id, date, time, status) VALUES (?, ?, ?, ?, ?, 'scheduled')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiiss", $this->patient_id, $this->doctor_id, $this->service_id, $this->date, $this->time);
        error_log("Executing query: " . $stmt->get_result()); // Add this line to log the query
        return $stmt->execute();
    }

    public function reschedule() {
        $query = "UPDATE " . $this->table . " SET date = ?, time = ?, status = 'rescheduled' WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $this->date, $this->time, $this->appointment_id);
        return $stmt->execute();
    }

    public function cancel() {
        $query = "UPDATE " . $this->table . " SET status = 'cancelled' WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->appointment_id);
        return $stmt->execute();
    }

    // Existing methods (find_by_id, update, delete) ...
}
?>
