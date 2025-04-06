<?php
// save_ticket_details.php

require_once './src/Database.php';
require_once './src/ticket-manager.php';
require_once './src/manager.php';

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
$ticketManagerId = isset($_POST['ticketId']) ? (int)$_POST['ticketId'] : 0;
$teamId = isset($_POST['teamId']) ? (int)$_POST['teamId'] : 0;
$requesterId = isset($_POST['requesterId']) ? (int)$_POST['requesterId'] : 0;

// Validate required data
if (!$ticketManagerId || !$teamId || !$requesterId) {
    die(json_encode(['success' => false, 'message' => 'Invalid input. Please ensure all fields are filled.']));
}

// Get admin_id from session
$adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if (!$adminId) {
    die(json_encode(['success' => false, 'message' => 'Admin user not logged in.']));
}

// Fetch the ticket manager details from the ticketmanager table
$sql = "SELECT * FROM ticketmanager WHERE id = ?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing query: ' . $db->error]));
}
$stmt->bind_param("i", $ticketManagerId);
$stmt->execute();
$result = $stmt->get_result();
$ticketManager = $result->fetch_object();

$managerid = $ticketManager->manager_id;
$sql = "SELECT * FROM manager WHERE id = ?";
$stmt = $db->prepare($sql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing query: ' . $db->error]));
}
$stmt->bind_param("i", $managerid);
$stmt->execute();
$result = $stmt->get_result();
$manager = $result->fetch_object();

if (!$ticketManager) {
    die(json_encode(['success' => false, 'message' => 'Ticket manager not found.']));
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
    $ticketManager->title,
    $ticketManager->body,
    $adminId,
    $requesterId,
    $ticketManager->branch_id,
    $ticketManager->location_id,
    $teamId,
    $ticketManager->priority,
    $currentDateTime,
    $currentDateTime
);
if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'message' => 'Error executing insert query: ' . $stmt->error]));
}

// Get the inserted ticket ID
$insertedTicketId = $stmt->insert_id;

// Check for files in the manager's folder
$managerFilePath = './upload/manager/' . $ticketManagerId . '/';
if (is_dir($managerFilePath)) {
    // List all files in the directory
    $files = scandir($managerFilePath);

    // Valid extensions for the files
    $validExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'doc'];

    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $validExtensions)) {
            $filePath = $managerFilePath . $file;
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
                } else {
                    die(json_encode(['success' => false, 'message' => 'Error preparing file insert query']));
                }
            } else {
                die(json_encode(['success' => false, 'message' => 'Failed to move the file: ' . $file]));
            }
        }
    }
}

// Send email to ticket manager using PHPMailer
$mail =new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'mail.medgo.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'helpdesk@medgo.net';
    $mail->Password = 'passwordhelpdesk'; // Ensure this is correct
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use secure protocol
    $mail->Port = 465; // Set the SMTP port

    // Recipient settings
    $mail->setFrom('helpdesk@medgo.net', 'Helpdesk');
    $mail->addAddress($manager->email);  // Recipient's email address

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Ticket Accepted';
    $mail->Body    = 'Dear ' . $manager->name . ',<br><br>We have been accepted your ticket with ID: ' . $ticketManagerId . '.<br><br><strong>Title:</strong> '
    . $ticketManager->title . '<br><strong>Description:</strong> ' . $ticketManager->body . '<br><strong>Priority:</strong> ' . $ticketManager->priority .
    '<br><strong>Created At:</strong> ' . $ticketManager->created_at .'<br><br>Best regards,<br>Helpdesk Team';

    // Send email
    $mail->send();
    
// Update the ticket manager status to 'yes'
$updateTicketManagerStatusSql = "UPDATE ticketmanager SET status = 'yes' WHERE id = ?";
$stmt = $db->prepare($updateTicketManagerStatusSql);
if (!$stmt) {
    die(json_encode(['success' => false, 'message' => 'Error preparing status update query: ' . $db->error]));
}
$stmt->bind_param("i", $ticketManagerId);
if (!$stmt->execute()) {
    die(json_encode(['success' => false, 'message' => 'Error updating ticket manager status']));
}

    echo json_encode(['success' => true, 'ticketId' => $insertedTicketId, 'message' => 'Ticket created successfully and email sent.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ticket created, but email could not be sent. Error: ' . $mail->ErrorInfo]);
}

?>
