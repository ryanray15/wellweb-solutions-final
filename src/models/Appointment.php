<?php
// src/models/Appointment.php

class Appointment
{
    private $conn;
    private $table = 'appointments';

    public $appointment_id;
    public $patient_id;
    public $doctor_id;
    public $service_id;
    public $date;
    public $time;
    public $status;
    public $consultation_type;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table . " (patient_id, doctor_id, service_id, date, time, consultation_type, status) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("SQL prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("iiisss", $this->patient_id, $this->doctor_id, $this->service_id, $this->date, $this->time, $this->consultation_type);  // Bind consultation_type

        if ($stmt->execute()) {
            error_log("Appointment created successfully.");
            return true;
        } else {
            error_log("Failed to create appointment: " . $stmt->error);
            return false;
        }
    }


    public function reschedule()
    {
        $query = "UPDATE " . $this->table . " SET date = ?, time = ?, status = 'rescheduled' WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssi", $this->date, $this->time, $this->appointment_id);
        return $stmt->execute();
    }

    public function cancel()
    {
        $query = "UPDATE " . $this->table . " SET status = 'canceled' WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->appointment_id);
        return $stmt->execute();
    }

    public function getAppointmentById($appointment_id)
    {
        $query = "SELECT * FROM $this->table WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    public function updateStatus($appointment_id, $status)
    {
        $query = "UPDATE $this->table SET status = ? WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $appointment_id);
        $stmt->execute();
    }

    // Existing methods (find_by_id, update, delete) ...
}
