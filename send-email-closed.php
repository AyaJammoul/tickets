<?php

//Here when we press on ticket closed an email will send for team member  and ticket owner that this ticket is unsolved


// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './src/PHPMailer/src/Exception.php';
require './src/PHPMailer/src/PHPMailer.php';
require './src/PHPMailer/src/SMTP.php';
require_once './src/ticket.php';
require_once './src/team-member.php';
require_once './src/Database.php';
require_once './src/user.php'; // Ensure you include this for the User class
require_once './src/requester.php'; // Include the Requester class

// Check if ID is set in POST request
if (!isset($_POST['id']) || !ctype_digit($_POST['id'])) {
    echo json_encode(['status' => 400, 'msg' => 'Invalid request.']);
    exit();
}

$ticketId = $_POST['id'];

// Find the ticket by ID
$ticket = Ticket::find($ticketId);
if (!$ticket) {
    echo json_encode(['status' => 404, 'msg' => 'Ticket not found.']);
    exit();
}

// Fetch team member email
$teamMemberId = $ticket->team_member;
$teamMemberEmail = TeamMember::getEmailById2($teamMemberId);

if (!$teamMemberEmail) {
    echo json_encode(['status' => 404, 'msg' => 'Team member email not found.']);
    exit();
}

// Fetch requester information
$requesterInfo = Requester::getEmailById($ticketId);

if (!$requesterInfo) {
    echo json_encode(['status' => 404, 'msg' => 'Requester information not found.']);
    exit();
}

// Fetch the user who created the ticket
$event = new Ticket();
$ticketEvents = $event->findCreateByTicket($ticketId); // Get the array of events
if (!$ticketEvents || count($ticketEvents) < 1) { // Check if events array is empty
    echo json_encode(['status' => 404, 'msg' => 'Ticket event not found.']);
    exit();
}

// Assuming you want to get the email from the first event
$userEmail = $ticketEvents[0]->email; // Adjust based on your class structure

if (!$userEmail) {
    echo json_encode(['status' => 404, 'msg' => 'User email not found.']);
    exit();
}

// Prepare to send emails using PHPMailer
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'mail.medgo.net';
    $mail->SMTPAuth = true;
    $mail->Username = 'helpdesk@medgo.net';
    $mail->Password = 'passwordhelpdesk'; // Ensure this is correct
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use secure protocol
    $mail->Port = 465; // Set the SMTP port

    // Sending email to the team member
    $mail->setFrom('helpdesk@medgo.net', 'Ticket System');
    $mail->addAddress($teamMemberEmail); 

    $mail->Subject = "Ticket Closed: " . $ticket->title;
    $mail->Body = "Hello,\n\nThe ticket id " . $ticket->id . " titled '" . $ticket->title . "' has been unsolved.\n\nDescription: " . $ticket->body;

    if (!$mail->send()) {
        error_log('Mailer error to team member: ' . $mail->ErrorInfo); 
        echo json_encode(['status' => 500, 'msg' => 'Failed to send email to team member.']);
        exit();
    } else {
        error_log('Email successfully sent to team member.');
    }

    // Reset email settings for user
    $mail->clearAddresses(); 
    $mail->addAddress($userEmail); 

    $mail->Subject = "Ticket Closed Notification: " . $ticket->title;
    $mail->Body = "Hello,\n\nThe ticket id " . $ticket->id . " titled '" . $ticket->title . "' that you created has been unsolved.\n\nDescription: " . $ticket->body;

    if (!$mail->send()) {
        error_log('Mailer error to user: ' . $mail->ErrorInfo); 
        echo json_encode(['status' => 500, 'msg' => 'Failed to send email to user.']);
        exit();
    } else {
        error_log('Email successfully sent to user.');
    }

    // Prepare to send the final email to the requester
    $userEmail = $requesterInfo->email; // Get the requester's email
    $userName = $requesterInfo->name;   // Get the requester's name

    // Sending email to the requester
    $mail->clearAddresses(); 
    $mail->addAddress($userEmail); 

    // Prepare the email body for the requester
    $mail->Subject = "Ticket Closed Notification: " . $ticket->title;
    $mail->Body = "Dear " . htmlspecialchars($userName) . ",\n\n" .
        "Thank you for contacting Help Desk. Your ticket (" . 
        $ticket->id . " - " . htmlspecialchars($ticket->title) . ") has been resolved. " .
        "If you feel that the ticket has not been resolved to your satisfaction or you need additional assistance, please reply to this notification to provide additional information.\n\n";

    if (!$mail->send()) {
        error_log('Mailer error to requester: ' . $mail->ErrorInfo); 
        echo json_encode(['status' => 500, 'msg' => 'Failed to send email to requester.']);
        exit();
    } else {
        error_log('Email successfully sent to requester.');
    }

    // Prepare JSON response with email details
   echo json_encode([
    'status' => 200,
    'msg' => 'Email sent successfully to the team member, user, and requester.'
]);


} catch (Exception $e) {
    error_log('Mailer error: ' . $e->getMessage()); 
    echo json_encode(['status' => 500, 'msg' => 'Mailer error: ' . $e->getMessage()]);
}

?>
