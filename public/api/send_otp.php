<?php
date_default_timezone_set('Asia/Manila'); // Set the correct timezone
require_once '../../vendor/autoload.php'; // Load PHPMailer via Composer
require_once '../../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch the email from POST request
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? null;


if (!$email) {
    echo json_encode(['status' => 400, 'message' => 'Email is required']);
    exit();
}

// Establish database connection
$db = include '../../config/database.php';

try {
    // Check if an OTP already exists and is still valid
    $checkQuery = $db->prepare("
        SELECT * 
        FROM OTPs 
        WHERE email = ? AND expires_at > NOW() 
        ORDER BY created_at DESC LIMIT 1
    ");
    $checkQuery->bind_param("s", $email);
    $checkQuery->execute();
    $result = $checkQuery->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            'status' => 400,
            'message' => 'An OTP has already been sent. Please wait until it expires.'
        ]);
        exit();
    }

    // Generate a 4-digit OTP
    $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

    // Calculate the expiry time (5 minutes)
    $expiresAt = (new DateTime())->add(new DateInterval('PT5M'))->format('Y-m-d H:i:s');

    // Insert OTP into the database
    $insertQuery = $db->prepare("
        INSERT INTO OTPs (email, otp, expires_at) 
        VALUES (?, ?, ?)
    ");
    $insertQuery->bind_param("sss", $email, $otp, $expiresAt);
    $insertQuery->execute();

    // Send OTP email using PHPMailer
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Change this based on your email provider
    $mail->SMTPAuth = true;
    $mail->Username = 'wellwebsolutions.dev@gmail.com'; // Replace with your email
    $mail->Password = 'jtdovlodmzohrybe'; // Replace with your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('wellwebsolutions.dev@gmail.com', 'Your App Name');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code';
    $mail->Body = "Your OTP code is <b>$otp</b>. It expires in 5 minutes.";

    $mail->send();

    echo json_encode(['status' => 200, 'message' => 'OTP sent successfully']);
} catch (Exception $e) {
    error_log("Error sending OTP: " . $e->getMessage());
    echo json_encode(['status' => 500, 'message' => 'Failed to send OTP']);
} catch (Throwable $t) {
    error_log("Unexpected error: " . $t->getMessage());
    echo json_encode(['status' => 500, 'message' => 'Internal server error']);
}
