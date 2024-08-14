<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    // Start a transaction to ensure all deletions are successful before committing
    $db->begin_transaction();

    try {
        // Delete records in doctor_verifications related to this user
        $query = $db->prepare("DELETE FROM doctor_verifications WHERE doctor_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();

        // Delete records in doctor_availability related to this user
        $query = $db->prepare("DELETE FROM doctor_availability WHERE doctor_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();

        // Now delete the user from the users table
        $query = $db->prepare("DELETE FROM users WHERE user_id = ?");
        $query->bind_param("i", $user_id);
        $query->execute();

        // Commit the transaction if all queries are successful
        $db->commit();

        echo json_encode(['status' => true, 'message' => 'User and related records deleted successfully']);
    } catch (mysqli_sql_exception $e) {
        // Rollback the transaction in case of any errors
        $db->rollback();

        echo json_encode(['status' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Invalid user ID']);
}
