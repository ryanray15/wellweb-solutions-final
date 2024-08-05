<?php
require_once __DIR__ . '/../models/DoctorAvailability.php';
require_once __DIR__ . '/../models/Appointment.php';

class DoctorAvailabilityController
{
    private $doctorAvailabilityModel;
    private $appointmentModel;

    public function __construct($db)
    {
        $this->doctorAvailabilityModel = new DoctorAvailability($db);
        $this->appointmentModel = new Appointment($db);
    }

    // Get availability for a specific doctor
    public function getAvailability($doctor_id)
    {
        return $this->doctorAvailabilityModel->getAvailabilityByDoctor($doctor_id);
    }

    // Set or update availability for a doctor
    public function setAvailability($doctor_id, $availabilityData)
    {
        foreach ($availabilityData as $availability) {
            $day = $availability['day'];
            $start_time = $availability['start_time'];
            $end_time = $availability['end_time'];

            $this->doctorAvailabilityModel->setAvailability($doctor_id, $day, $start_time, $end_time);
        }

        return ['status' => true, 'message' => 'Availability updated successfully'];
    }

    // Update appointment status
    public function updateAppointmentStatus($appointment_id, $status)
    {
        $appointment = $this->appointmentModel->getAppointmentById($appointment_id);

        if (!$appointment) {
            return ['status' => false, 'message' => 'Appointment not found'];
        }

        $this->appointmentModel->updateStatus($appointment_id, $status);

        // Send notification to the patient (pseudocode, implement according to your notification system)
        // NotificationService::sendNotification($appointment['patient_id'], 'Your appointment status has changed to ' . $status);

        return ['status' => true, 'message' => 'Appointment status updated successfully'];
    }
}
