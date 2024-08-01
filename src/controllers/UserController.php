<?php
// src/controllers/UserController.php

require_once __DIR__ . '/../models/User.php';

class UserController {
    private $db;
    private $user;

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function create($name, $email, $password, $role) {
        $this->user->name = $name;
        $this->user->email = $email;
        $this->user->password = password_hash($password, PASSWORD_BCRYPT); // Hash the password
        $this->user->role = $role;

        if ($this->user->find_by_email()) {
            return ['status' => false, 'message' => 'User already exists'];
        }

        if ($this->user->register()) {
            return ['status' => true, 'message' => 'User created successfully'];
        }

        return ['status' => false, 'message' => 'User creation failed'];
    }

    public function get($user_id) {
        $this->user->user_id = $user_id;

        if ($this->user->find_by_id()) {
            return ['status' => true, 'data' => $this->user];
        }

        return ['status' => false, 'message' => 'User not found'];
    }

    public function update($user_id, $name, $email, $password, $role) {
        $this->user->user_id = $user_id;
        $this->user->name = $name;
        $this->user->email = $email;
        $this->user->password = password_hash($password, PASSWORD_BCRYPT); // Hash the password
        $this->user->role = $role;

        if ($this->user->update()) {
            return ['status' => true, 'message' => 'User updated successfully'];
        }

        return ['status' => false, 'message' => 'User update failed'];
    }

    public function delete($user_id) {
        $this->user->user_id = $user_id;

        if ($this->user->delete()) {
            return ['status' => true, 'message' => 'User deleted successfully'];
        }

        return ['status' => false, 'message' => 'User deletion failed'];
    }
}
?>
