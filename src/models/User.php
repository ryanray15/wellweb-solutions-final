<?php
// src/models/User.php

class User
{
    private $conn;
    private $table = 'Users';

    public $user_id;
    public $first_name;
    public $middle_initial;
    public $last_name;
    public $contact_number;
    public $address;
    public $email;
    public $password;
    public $role;
    public $gender; // New field to handle gender

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function register($specializations = [])
    {
        $query = "INSERT INTO " . $this->table . " (first_name, middle_initial, last_name, contact_number, address, email, password, role, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssss", $this->first_name, $this->middle_initial, $this->last_name, $this->contact_number, $this->address, $this->email, $this->password, $this->role, $this->gender); // Added gender

        if ($stmt->execute()) {
            $this->user_id = $stmt->insert_id;
            if ($this->role === 'doctor' && !empty($specializations)) {
                $this->add_specializations($specializations);
            }
            return true;
        }
        return false;
    }

    public function login()
    {
        $query = "SELECT user_id, password, role FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $stmt->bind_result($this->user_id, $this->password, $this->role);
        $stmt->fetch();
        return !empty($this->user_id);
    }

    public function find_by_email()
    {
        $query = "SELECT user_id FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $stmt->bind_result($this->user_id);
        $stmt->fetch();
        return !empty($this->user_id);
    }

    public function find_by_id()
    {
        $query = "SELECT user_id, first_name, middle_initial, last_name, contact_number, address, email, role, gender FROM " . $this->table . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $stmt->bind_result($this->user_id, $this->first_name, $this->middle_initial, $this->last_name, $this->contact_number, $this->address, $this->email, $this->role, $this->gender); // Added gender
        $stmt->fetch();
        return !empty($this->user_id);
    }

    public function update($specializations = [])
    {
        $query = "UPDATE " . $this->table . " SET first_name = ?, middle_initial = ?, last_name = ?, contact_number = ?, address = ?, email = ?, password = ?, role = ?, gender = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssssi", $this->first_name, $this->middle_initial, $this->last_name, $this->contact_number, $this->address, $this->email, $this->password, $this->role, $this->gender, $this->user_id); // Added gender

        if ($stmt->execute()) {
            if ($this->role === 'doctor' && !empty($specializations)) {
                $this->update_specializations($specializations);
            }
            return true;
        }
        return false;
    }

    public function delete()
    {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        return $stmt->execute();
    }

    public function update_password()
    {
        $query = "UPDATE " . $this->table . " SET password = ? WHERE user_id = ?";  // Use user_id instead of email
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $this->password, $this->user_id);
        return $stmt->execute();
    }

    private function add_specializations($specializations)
    {
        $query = "INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);

        foreach ($specializations as $specialization_id) {
            $stmt->bind_param("ii", $this->user_id, $specialization_id);
            $stmt->execute();
        }
    }

    private function update_specializations($specializations)
    {
        // Delete old specializations
        $query = "DELETE FROM doctor_specializations WHERE doctor_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();

        // Add new specializations
        $this->add_specializations($specializations);
    }
}
