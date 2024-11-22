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

    public function getAvailability($doctor_id)
    {
        return $this->doctorAvailabilityModel->getAvailabilityByDoctor($doctor_id);
    }

    public function setAvailability($doctor_id, $availabilityData)
    {
        foreach ($availabilityData as $availability) {
            $date = $availability['date'];
            $start_time = $availability['start_time'];
            $end_time = $availability['end_time'];
            $consultation_type = $availability['consultation_type'];
            $consultation_duration = $availability['consultation_duration'];

            $this->doctorAvailabilityModel->setAvailability(
                $doctor_id,
                $date,
                $start_time,
                $end_time,
                $consultation_type,
                $consultation_duration
            );
        }

        return ['status' => true, 'message' => 'Availability updated successfully'];
    }

    public function updateAppointmentStatus($appointment_id, $status)
    {
        $appointment = $this->appointmentModel->getAppointmentById($appointment_id);

        if (!$appointment) {
            return ['status' => false, 'message' => 'Appointment not found'];
        }

        $this->appointmentModel->updateStatus($appointment_id, $status);

        return ['status' => true, 'message' => 'Appointment status updated successfully'];
    }
}
