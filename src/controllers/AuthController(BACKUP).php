<?php
// src/controllers/AuthController.php

require_once '../config/database.php';
require_once '../models/User.php';

class AuthController
{
    private $db;
    private $user;

    public function __construct()
    {
        $this->db = $mysqli;
        $this->user = new User($this->db);
    }

    public function register($name, $email, $password, $role)
    {
        $this->user->name = $name;
        $this->user->email = $email;
        $this->user->password = password_hash($password, PASSWORD_BCRYPT);
        $this->user->role = $role;

        if ($this->user->find_by_email()) {
            return ['status' => false, 'message' => 'User already exists'];
        }

        if ($this->user->register()) {
            return ['status' => true, 'message' => 'User registered successfully'];
        }

        return ['status' => false, 'message' => 'Registration failed'];
    }

    public function login($email, $password)
    {
        $this->user->email = $email;

        if ($this->user->login() && password_verify($password, $this->user->password)) {
            return ['status' => true, 'message' => 'Login successful', 'role' => $this->user->role];
        }

        return ['status' => false, 'message' => 'Invalid email or password'];
    }

    public function reset_password($email, $new_password)
    {
        $this->user->email = $email;
        $this->user->password = password_hash($new_password, PASSWORD_BCRYPT);

        if ($this->user->update_password()) {
            return ['status' => true, 'message' => 'Password updated successfully'];
        }

        return ['status' => false, 'message' => 'Password update failed'];
    }
}
