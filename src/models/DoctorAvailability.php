<?php
class DoctorAvailability
{
    private $conn;
    private $table = 'doctor_availability';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAvailabilityByDoctor($doctor_id)
    {
        $query = "SELECT * FROM $this->table WHERE doctor_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function setAvailability($doctor_id, $date, $start_time, $end_time, $consultation_type, $consultation_duration)
    {
        $query = "
            INSERT INTO $this->table 
            (doctor_id, date, start_time, end_time, status, consultation_type, consultation_duration) 
            VALUES (?, ?, ?, ?, 'Available', ?, ?)
            ON DUPLICATE KEY UPDATE 
                start_time = VALUES(start_time), 
                end_time = VALUES(end_time),
                status = 'Available',
                consultation_type = VALUES(consultation_type),
                consultation_duration = VALUES(consultation_duration)
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issssi", $doctor_id, $date, $start_time, $end_time, $consultation_type, $consultation_duration);
        $stmt->execute();
    }
}
