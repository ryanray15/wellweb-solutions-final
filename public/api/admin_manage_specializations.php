<?php
require_once '../../config/database.php';
$db = include '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO specializations (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            echo json_encode(['status' => true, 'message' => 'Specialization added successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to add specialization.']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Specialization name cannot be empty.']);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $id = $_DELETE['id'] ?? 0;

    if ($id) {
        $stmt = $db->prepare("DELETE FROM specializations WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => true, 'message' => 'Specialization deleted successfully!']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to delete specialization.']);
        }
    } else {
        echo json_encode(['status' => false, 'message' => 'Invalid specialization ID.']);
    }
    exit();
}

$specializations = $db->query("SELECT * FROM specializations")->fetch_all(MYSQLI_ASSOC);

echo json_encode($specializations);
