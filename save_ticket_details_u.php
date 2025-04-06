<?php
// save_ticket_details.php

require_once './src/Database.php';
require_once './src/ticket-user.php';
require_once './src/user.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './src/PHPMailer/src/Exception.php';
require './src/PHPMailer/src/PHPMailer.php';
require './src/PHPMailer/src/SMTP.php';

// Start session to get admin user_id
session_start();

// Get database connection
$db = Database::getInstance();

// Get POST data
$ticketuserId = $_POST['ticketId'];
$teamId = $_POST['teamId'];
$requesterId = $_POST['requesterId'];

// Get admin_id from session
$adminId = $_SESSION['user_id'];

// Fetch the ticket user details from the ticketuser table
$sql = "SELECT * FROM ticketuser WHERE id = ?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing query: ' . $db->error]));
}
$stmt->bind_param("i", $ticketuserId);
$stmt->execute();
$result = $stmt->get_result();
$ticketuser = $result->fetch_object();
$stmt->close();

if (!$ticketuser) {
    die(json_encode(['success' => false, 'message' => 'Ticket user not found.']));
}

// Fetch user details from the user table
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing query: ' . $db->error]));
}
$stmt->bind_param("i", $ticketuser->user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_object();
$stmt->close();

if (!$user) {
    die(json_encode(['success' => false, 'message' => 'User not found.']));
}

$currentDateTime = date('Y-m-d H:i:s');

// Insert the new ticket
$insertTicketSql = "INSERT INTO ticket (title, body, admin_id, requester, branch_id, location_id, team, priority, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $db->prepare($insertTicketSql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing insert query: ' . $db->error]));
}
$stmt->bind_param(
    "ssisiiisss",
    $ticketuser->title,
    $ticketuser->body,
    $adminId,
    $requesterId,
    $ticketuser->branch_id,
    $ticketuser->location_id,
    $teamId,
    $ticketuser->priority,
    $currentDateTime,
    $currentDateTime
);
if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'message' => 'Error executing insert query: ' . $stmt->error]));
}

// Get the inserted ticket ID
$insertedTicketId = $stmt->insert_id;
$stmt->close();

// Check for files in the user's folder
$userFilePath = './upload/user/' . $ticketuserId . '/';
if (is_dir($userFilePath)) {
    // List all files in the directory
    $files = scandir($userFilePath);

    // Valid extensions for the files
    $validExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'];

    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $validExtensions)) {
            $filePath = $userFilePath . $file;
            $uploadDir = './upload/' . $insertedTicketId . '/';

            // Create ticket folder if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Move file to the ticket folder
            $newFilePath = $uploadDir . basename($file);
            if (copy($filePath, $newFilePath)) {
                // Save file details in ticket_images table
                $relativePath = $insertedTicketId . '/' . basename($file);
                $insertFileSql = "INSERT INTO ticket_images (ticket_id, image_path) VALUES (?, ?)";
                $fileStmt = $db->prepare($insertFileSql);
                if ($fileStmt) {
                    $fileStmt->bind_param("is", $insertedTicketId, $relativePath);
                    $fileStmt->execute();
                    $fileStmt->close();
                }
            }
        }
    }
}

// Send email to ticket user using PHPMailer
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'mail.medgo.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'helpdesk@medgo.net';
    $mail->Password = 'passwordhelpdesk'; // Replace with a secure method
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Recipient settings
    $mail->setFrom('helpdesk@medgo.net', 'Helpdesk');
    $mail->addAddress($user->email);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Ticket Accepted';
    $mail->Body = 'Dear ' . htmlspecialchars($user->name) . ',<br><br>We have accepted your ticket with ID: ' . $ticketuserId . '.<br><br><strong>Title:</strong> '
        . htmlspecialchars($ticketuser->title) . '<br><strong>Description:</strong> ' . htmlspecialchars($ticketuser->body) . '<br><strong>Priority:</strong> ' . $ticketuser->priority .
        '<br><strong>Created At:</strong> ' . $ticketuser->created_at . '<br><br>Best regards,<br>Helpdesk Team';

    // Send email
    $mail->send();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ticket created, but email could not be sent. Error: ' . $mail->ErrorInfo]);
    exit;
}

// Update the ticket user status to 'yes'
$updateTicketuserStatusSql = "UPDATE ticketuser SET status = 'yes' WHERE id = ?";
$stmt = $db->prepare($updateTicketuserStatusSql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing status update query: ' . $db->error]));
}
$stmt->bind_param("i", $ticketuserId);
$stmt->execute();
$stmt->close();

// Return success response
echo json_encode(['success' => true, 'ticketId' => $insertedTicketId, 'message' => 'Ticket created successfully']);
?>
