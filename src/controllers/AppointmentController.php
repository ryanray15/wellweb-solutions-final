<?php
// src/controllers/AppointmentController.php

require_once __DIR__ . '/../models/Appointment.php';

class AppointmentController {
    private $db;
    private $appointment;

    public function __construct($db) {
        $this->db = $db;
        $this->appointment = new Appointment($db);
    }

    public function schedule($patient_id, $doctor_id, $service_id, $date, $time) {
        $this->appointment->patient_id = $patient_id;
        $this->appointment->doctor_id = $doctor_id;
        $this->appointment->service_id = $service_id;
        $this->appointment->date = $date;
        $this->appointment->time = $time;

        if ($this->appointment->create()) {
            return ['status' => true, 'message' => 'Appointment scheduled successfully'];
        }

        return ['status' => false, 'message' => 'Appointment scheduling failed'];
    }

    public function reschedule($appointment_id, $date, $time) {
        $this->appointment->appointment_id = $appointment_id;
        $this->appointment->date = $date;
        $this->appointment->time = $time;

        if ($this->appointment->reschedule()) {
            return ['status' => true, 'message' => 'Appointment rescheduled successfully'];
        }

        return ['status' => false, 'message' => 'Appointment rescheduling failed'];
    }

    public function cancel($appointment_id) {
        $this->appointment->appointment_id = $appointment_id;

        if ($this->appointment->cancel()) {
            return ['status' => true, 'message' => 'Appointment canceled successfully'];
        }

        return ['status' => false, 'message' => 'Appointment cancellation failed'];
    }

    // Existing methods (create, get, update, delete) ...
}
?>
