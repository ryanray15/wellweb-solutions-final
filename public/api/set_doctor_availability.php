<?php
session_start();
require_once '../../config/database.php';

$db = include '../../config/database.php';
header('Content-Type: application/json');

// Decode incoming data
$input = json_decode(file_get_contents('php://input'), true);

$doctor_id = $input['doctor_id'] ?? null;
$consultation_type = $input['consultation_type'] ?? null;
$consultation_duration = $input['consultation_duration'] ?? null;
$date = $input['date'] ?? null;
$start_time = $input['start_time'] ?? null;
$end_time = $input['end_time'] ?? null;
$status = $input['status'] ?? 'Available';

if ($doctor_id && $date && $start_time && $end_time && $consultation_type && $consultation_duration) {
    // Check if the doctor is trying to insert an overlapping time slot of the same consultation type
    $overlapCheckQuery = $db->prepare("
        SELECT * FROM doctor_availability 
        WHERE doctor_id = ? 
        AND date = ? 
        AND start_time < ? 
        AND end_time > ? 
        AND consultation_type = ?
        AND status = 'Available'
    ");
    $overlapCheckQuery->bind_param("issss", $doctor_id, $date, $end_time, $start_time, $consultation_type);
    $overlapCheckQuery->execute();
    $overlappingSlot = $overlapCheckQuery->get_result()->fetch_assoc();

    // Prevent inserting new slots of the same consultation type if overlapping exists
    if ($overlappingSlot && $status == 'Available') {
        echo json_encode(['status' => false, 'message' => 'Cannot insert overlapping slots of the same consultation type']);
        exit;
    }

    // Check for overlapping "Available" slots to split when inserting "Not Available"
    $overlapSlotQuery = $db->prepare("
        SELECT * FROM doctor_availability 
        WHERE doctor_id = ? 
        AND date = ? 
        AND start_time < ? 
        AND end_time > ?
    ");
    $overlapSlotQuery->bind_param("isss", $doctor_id, $date, $end_time, $start_time);
    $overlapSlotQuery->execute();
    $existing_slot = $overlapSlotQuery->get_result()->fetch_assoc();

    if ($existing_slot) {
        $original_start_time = $existing_slot['start_time'];
        $original_end_time = $existing_slot['end_time'];

        // Begin transaction
        $db->begin_transaction();

        try {
            // Split the original slot if necessary
            if ($original_start_time < $start_time) {
                // Insert the pre-split slot (time before the new slot)
                $preSlotQuery = $db->prepare("
                    INSERT INTO doctor_availability (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'Available')
                ");
                $preSlotQuery->bind_param("isssss", $doctor_id, $consultation_type, $consultation_duration, $date, $original_start_time, $start_time);
                $preSlotQuery->execute();
            }

            // Only insert the post-split slot if the time slot after the "Not Available" is empty or available
            if ($original_end_time > $end_time) {
                $checkPostSlotQuery = $db->prepare("
                    SELECT * FROM doctor_availability 
                    WHERE doctor_id = ? 
                    AND date = ? 
                    AND start_time = ?
                    AND status = 'Not Available'
                ");
                $checkPostSlotQuery->bind_param("iss", $doctor_id, $date, $end_time);
                $checkPostSlotQuery->execute();
                $postSlotExists = $checkPostSlotQuery->get_result()->fetch_assoc();

                // If there is no "Not Available" slot below or the time slot is not empty
                if (!$postSlotExists) {
                    // Insert the post-split slot (time after the new slot)
                    $postSlotQuery = $db->prepare("
                        INSERT INTO doctor_availability (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status)
                        VALUES (?, ?, ?, ?, ?, ?, 'Available')
                    ");
                    $postSlotQuery->bind_param("isssss", $doctor_id, $consultation_type, $consultation_duration, $date, $end_time, $original_end_time);
                    $postSlotQuery->execute();
                }
            }

            // Insert the new "Not Available" slot
            $insertNewSlotQuery = $db->prepare("
                INSERT INTO doctor_availability (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insertNewSlotQuery->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $start_time, $end_time, $status);
            $insertNewSlotQuery->execute();

            // Remove the original slot as it's now split into new parts
            $deleteOriginalSlotQuery = $db->prepare("
                DELETE FROM doctor_availability 
                WHERE availability_id = ?
            ");
            $original_availability_id = $existing_slot['availability_id'];
            $deleteOriginalSlotQuery->bind_param("i", $original_availability_id);
            $deleteOriginalSlotQuery->execute();

            // Commit transaction
            $db->commit();

            echo json_encode(['status' => true, 'message' => 'Availability set successfully']);
        } catch (Exception $e) {
            // Rollback transaction if any error occurs
            $db->rollback();
            echo json_encode(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    } else {
        // If no overlapping slots, just insert the new slot directly
        $insertQuery = $db->prepare("
            INSERT INTO doctor_availability (doctor_id, consultation_type, consultation_duration, date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertQuery->bind_param("issssss", $doctor_id, $consultation_type, $consultation_duration, $date, $start_time, $end_time, $status);

        if ($insertQuery->execute()) {
            echo json_encode(['status' => true, 'message' => 'Availability set successfully']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to set availability']);
        }
    }
} else {
    echo json_encode(['status' => false, 'message' => 'Missing required fields']);
}
