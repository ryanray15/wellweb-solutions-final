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
    public $availability_id;
    public $date;
    public $start_time;
    public $end_time;
    public $status;
    public $meeting_id;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table . " (patient_id, doctor_id, service_id, availability_id, date, start_time, end_time, status, meeting_id) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("SQL prepare failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("iiiissss", $this->patient_id, $this->doctor_id, $this->service_id, $this->availability_id, $this->date, $this->start_time, $this->end_time, $this->meeting_id);

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
        $query = "UPDATE " . $this->table . " SET date = ?, start_time = ?, end_time = ?,status = 'rescheduled' WHERE appointment_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $this->date, $this->start_time, $this->end_time, $this->appointment_id);
        return $stmt->execute();
    }

    public function cancel()
    {
        // Begin transaction to ensure both operations succeed
        $this->conn->begin_transaction();

        try {
            // Step 1: Update the appointment status to 'canceled'
            $query = "UPDATE " . $this->table . " SET status = 'canceled' WHERE appointment_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->appointment_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update appointment status: " . $stmt->error);
            }

            // Step 2: Set the related availability slot status back to 'Available'
            $availabilityQuery = "UPDATE doctor_availability SET status = 'Available' WHERE availability_id = ?";
            $availabilityStmt = $this->conn->prepare($availabilityQuery);
            $availabilityStmt->bind_param("i", $this->availability_id);
            if (!$availabilityStmt->execute()) {
                throw new Exception("Failed to update availability status: " . $availabilityStmt->error);
            }

            // Commit transaction if both updates succeed
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback if any update fails
            $this->conn->rollback();
            error_log("Error in canceling appointment: " . $e->getMessage());
            return false;
        }
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
}
