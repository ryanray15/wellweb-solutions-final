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

    public function register($first_name, $middle_initial, $last_name, $contact_number, $address, $email, $password, $role, $gender, $specializations)
    {
        // Set the user properties
        $this->user->first_name = $first_name;
        $this->user->middle_initial = $middle_initial;
        $this->user->last_name = $last_name;
        $this->user->contact_number = $contact_number;
        $this->user->address = $address;
        $this->user->email = $email;
        $this->user->password = password_hash($password, PASSWORD_BCRYPT); // Hash the password
        $this->user->role = $role;
        $this->user->gender = $gender;

        // Check if user exists
        if ($this->user->find_by_email()) {
            return ['status' => false, 'message' => 'User already exists'];
        }

        // Register user
        if ($this->user->register($specializations)) {
            // Fetch the user ID of the newly registered user
            $user_id = $this->db->insert_id;

            // Insert specializations for the doctor
            if ($role === 'doctor' && !empty($specializations)) {
                foreach ($specializations as $specialization_id) {
                    $stmt = $this->db->prepare("INSERT INTO doctor_specializations (doctor_id, specialization_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $user_id, $specialization_id);
                    $stmt->execute();
                }
            }

            // Return the user ID along with a success message
            return ['status' => true, 'message' => 'User registered successfully', 'user_id' => $user_id];
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

    // Method to save the Stripe account ID into the database for the user
    public function saveStripeAccountId($user_id, $stripe_account_id)
    {
        $query = $this->db->prepare("UPDATE users SET stripe_account_id = ? WHERE user_id = ?");
        $query->bind_param("si", $stripe_account_id, $user_id);

        if ($query->execute()) {
            return true; // Successfully saved
        } else {
            return false; // Failed to save
        }
    }

    // Method to get the Stripe account ID for the user
    public function getStripeAccountId($user_id)
    {
        $query = $this->db->prepare("SELECT stripe_account_id FROM users WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['stripe_account_id'];
        } else {
            return null; // No Stripe account ID found
        }
    }
}
