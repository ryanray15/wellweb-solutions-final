<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? null;
$user_id = $_POST['user_id'] ?? null;

if ($action && $user_id) {
    if ($action == 'view') {
        // Fetch user details
        $query = $db->prepare("SELECT user_id, 
                                      first_name, middle_initial, last_name, 
                                      email, role, contact_number, address 
                               FROM users WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $user = $query->get_result()->fetch_assoc();
        echo json_encode(['status' => true, 'user' => $user]);
    } elseif ($action == 'edit') {
        $first_name = $_POST['first_name'];
        $middle_initial = $_POST['middle_initial'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $contact_number = $_POST['contact_number'];
        $address = $_POST['address'];

        // Update user details
        $query = $db->prepare("UPDATE users SET 
                                      first_name = ?, middle_initial = ?, last_name = ?, 
                                      email = ?, role = ?, 
                                      contact_number = ?, address = ? 
                               WHERE user_id = ?");
        $query->bind_param(
            "sssssssi",
            $first_name,
            $middle_initial,
            $last_name,
            $email,
            $role,
            $contact_number,
            $address,
            $user_id
        );
        $query->execute();
        echo json_encode(['status' => true, 'message' => 'User updated successfully']);
    } elseif ($action == 'delete') {
        // Delete user
        $query = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        echo json_encode(['status' => true, 'message' => 'User deleted successfully']);
    }
} else {
    // Fetch all users
    $result = $db->query("SELECT user_id, 
                                 CONCAT(first_name, ' ', middle_initial, ' ', last_name) as name, 
                                 email, role 
                          FROM users");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['status' => true, 'users' => $users]);
}
