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

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function register()
    {
        $query = "INSERT INTO " . $this->table . " (first_name, middle_initial, last_name, contact_number, address, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssss", $this->first_name, $this->middle_initial, $this->last_name, $this->contact_number, $this->address, $this->email, $this->password, $this->role);
        return $stmt->execute();
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
        $query = "SELECT user_id, first_name, middle_initial, last_name, contact_number, address, email, role FROM " . $this->table . " WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $stmt->bind_result($this->user_id, $this->first_name, $this->middle_initial, $this->last_name, $this->contact_number, $this->address, $this->email, $this->role);
        $stmt->fetch();
        return !empty($this->user_id);
    }

    public function update()
    {
        $query = "UPDATE " . $this->table . " SET first_name = ?, middle_initial = ?, last_name = ?, contact_number = ?, address = ?, email = ?, password = ?, role = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssssi", $this->first_name, $this->middle_initial, $this->last_name, $this->contact_number, $this->address, $this->email, $this->password, $this->role, $this->user_id);
        return $stmt->execute();
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
}
