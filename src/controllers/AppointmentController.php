<?php
// src/controllers/AppointmentController.php

require_once __DIR__ . '/../models/Appointment.php';

class AppointmentController
{
    private $db;
    private $appointment;

    public function __construct($db)
    {
        $this->db = $db;
        $this->appointment = new Appointment($db);
    }

    public function schedule($patient_id, $doctor_id, $service_id, $date, $start_time, $end_time, $meeting_id = null)
    {
        error_log("Scheduling appointment for patient $patient_id with doctor $doctor_id on $date at $start_time and ends at $end_time");
        $this->appointment->patient_id = $patient_id;
        $this->appointment->doctor_id = $doctor_id;
        $this->appointment->service_id = $service_id;
        $this->appointment->date = $date;
        $this->appointment->start_time = $start_time;
        $this->appointment->end_time = $end_time;
        $this->appointment->meeting_id = $meeting_id;

        if ($this->appointment->create()) {
            error_log("Appointment scheduled successfully");
            return ['status' => true, 'message' => 'Appointment scheduled successfully'];
        }

        error_log("Failed to schedule appointment");
        return ['status' => false, 'message' => 'Appointment scheduling failed'];
    }

    public function reschedule($appointment_id, $date, $start_time, $end_time)
    {
        $this->appointment->appointment_id = $appointment_id;
        $this->appointment->date = $date;
        $this->appointment->start_time = $start_time;
        $this->appointment->end_time = $end_time;

        if ($this->appointment->reschedule()) {
            return ['status' => true, 'message' => 'Appointment rescheduled successfully'];
        }

        return ['status' => false, 'message' => 'Appointment rescheduling failed'];
    }

    public function cancel($appointment_id)
    {
        $this->appointment->appointment_id = $appointment_id;

        if ($this->appointment->cancel()) {
            return ['status' => true, 'message' => 'Appointment canceled successfully'];
        }

        return ['status' => false, 'message' => 'Appointment cancellation failed'];
    }

    // Existing methods (create, get, update, delete) ...
}
