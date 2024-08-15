<?php
// src/controllers/AuthController.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $db;
    private $user;

    public function __construct($db)
    {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function register($first_name, $middle_initial, $last_name, $contact_number, $address, $email, $password, $role, $gender, $specializations = [])
    {
        $this->user->first_name = $first_name;
        $this->user->middle_initial = $middle_initial;
        $this->user->last_name = $last_name;
        $this->user->contact_number = $contact_number;
        $this->user->address = $address;
        $this->user->email = $email;
        $this->user->password = password_hash($password, PASSWORD_BCRYPT); // Hash the password
        $this->user->role = $role;
        $this->user->gender = $gender; // Handling the gender field

        if ($this->user->find_by_email()) {
            return ['status' => false, 'message' => 'User already exists'];
        }

        if ($this->user->register($specializations)) {
            return ['status' => true, 'message' => 'User registered successfully'];
        }

        return ['status' => false, 'message' => 'Registration failed'];
    }

    public function login($email, $password)
    {
        $this->user->email = $email;

        if ($this->user->login() && password_verify($password, $this->user->password)) { // Verify the password
            return ['status' => true, 'message' => 'Login successful', 'role' => $this->user->role];
        }

        return ['status' => false, 'message' => 'Invalid email or password'];
    }

    public function reset_password($user_id, $new_password)
    {
        $this->user->user_id = $user_id;  // Use user_id instead of email
        $this->user->password = password_hash($new_password, PASSWORD_BCRYPT); // Hash the new password

        if ($this->user->update_password()) {
            return ['status' => true, 'message' => 'Password updated successfully'];
        }

        return ['status' => false, 'message' => 'Password update failed'];
    }
}
