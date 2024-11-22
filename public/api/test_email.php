<?php
require '../../vendor/autoload.php'; // Load PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Change this if using another provider
    $mail->SMTPAuth = true;
    $mail->Username = 'wellwebsolutions.dev@gmail.com'; // Replace with your email
    $mail->Password = 'jtdovlodmzohrybe'; // Replace with your app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email Settings
    $mail->setFrom('wellwebsolutions.dev@gmail.com', 'Wellwebsolutions'); // Sender email
    $mail->addAddress('ryanlaguna28@gmail.com', 'Ryan'); // Receiver email
    $mail->Subject = 'Test Email from PHPMailer';
    $mail->Body = 'This is a test email sent using PHPMailer with your App Password!';
    $mail->isHTML(true);

    // Send the email
    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
}
