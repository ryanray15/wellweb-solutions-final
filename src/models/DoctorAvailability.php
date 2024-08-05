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

    public function setAvailability($doctor_id, $day, $start_time, $end_time)
    {
        $query = "INSERT INTO $this->table (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isss", $doctor_id, $day, $start_time, $end_time);
        $stmt->execute();
    }
}
