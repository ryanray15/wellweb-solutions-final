<?php
// src/controllers/AuthController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function register($name, $email, $password, $role) {
        $this->user->name = $name;
        $this->user->email = $email;
        $this->user->password = password_hash($password, PASSWORD_BCRYPT); // Hash the password
        $this->user->role = $role;

        if ($this->user->find_by_email()) {
            return ['status' => false, 'message' => 'User already exists'];
        }

        if ($this->user->register()) {
            return ['status' => true, 'message' => 'User registered successfully'];
        }

        return ['status' => false, 'message' => 'Registration failed'];
    }

    public function login($email, $password) {
        $this->user->email = $email;

        if ($this->user->login() && password_verify($password, $this->user->password)) { // Verify the password
            return ['status' => true, 'message' => 'Login successful', 'role' => $this->user->role];
        }

        return ['status' => false, 'message' => 'Invalid email or password'];
    }

    public function reset_password($email, $new_password) {
        $this->user->email = $email;
        $this->user->password = password_hash($new_password, PASSWORD_BCRYPT); // Hash the new password

        if ($this->user->update_password()) {
            return ['status' => true, 'message' => 'Password updated successfully'];
        }

        return ['status' => false, 'message' => 'Password update failed'];
    }
}
?>
