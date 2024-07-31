<?php
// src/models/User.php

class User {
    private $conn;
    private $table = 'Users';

    public $user_id;
    public $name;
    public $email;
    public $password;
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table . " (name, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssss", $this->name, $this->email, $this->password, $this->role);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function login() {
        $query = "SELECT user_id, password, role FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $stmt->bind_result($this->user_id, $this->password, $this->role);
        $stmt->fetch();
        return $stmt->num_rows > 0;
    }

    public function find_by_email() {
        $query = "SELECT user_id FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $this->email);
        $stmt->execute();
        $stmt->bind_result($this->user_id);
        $stmt->fetch();
        return $stmt->num_rows > 0;
    }

    public function update_password() {
        $query = "UPDATE " . $this->table . " SET password = ? WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ss", $this->password, $this->email);
        return $stmt->execute();
    }
}
?>
