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
        $query = $db->prepare("SELECT user_id, name, email, role FROM users WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();
        $user = $query->get_result()->fetch_assoc();
        echo json_encode(['status' => true, 'user' => $user]);
    } elseif ($action == 'edit') {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        // Update user details
        $query = $db->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?");
        $query->bind_param("sssi", $name, $email, $role, $user_id);
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
    $result = $db->query("SELECT user_id, name, email, role FROM users");
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    echo json_encode(['status' => true, 'users' => $users]);
}
