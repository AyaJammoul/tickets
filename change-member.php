<?php
// Start output buffering at the very beginning
ob_start();

require_once './src/team.php';
require_once './src/ticket.php';
require_once './src/team-member.php';
require_once './src/Database.php';
include './header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load PHPMailer
require './src/PHPMailer/src/Exception.php';
require './src/PHPMailer/src/PHPMailer.php';
require './src/PHPMailer/src/SMTP.php';

//We take the member id, team id and ticket id to use them with the emails
if (!isset($_GET['member-id'], $_GET['team-id'], $_GET['ticket-ids'])) {
    echo 'Invalid parameters!';
    exit();
}

$memberId = $_GET['member-id'];
$teamId = $_GET['team-id'];

// Validate the parameters to ensure they're digits
if (!ctype_digit($memberId) || !ctype_digit($teamId)) {
    echo 'Invalid parameters!';
    exit();
}

// Decode the ticket IDs from JSON
$ticketIds = json_decode($_GET['ticket-ids'], true);

// Ensure ticketIds is an array
if (!is_array($ticketIds)) {
    echo 'Invalid ticket IDs!';
    exit();
}

$teamMemberInstance = new TeamMember();
$activeMembers = $teamMemberInstance->findActiveMembers($teamId);

//We take the old id user that works on a ticket with his email and see the name of his team then we make update stopped to stop him from working with this ticket and send him email that he is stopped from this ticket then we choose a new usee and send him email wi the ticket he must work with details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_member_id']) && ctype_digit($_POST['new_member_id'])) {
    $newMemberId = $_POST['new_member_id'];

    // Transfer tickets to the new team member
    $ticket = new Ticket();
    $transferErrors = [];

    foreach ($ticketIds as $ticketId) {
        if (!$ticket->transferTicket($ticketId, $newMemberId)) {
            $transferErrors[] = "Failed to transfer ticket ID: $ticketId.";
        }
    }
    $oldMember = TeamMember::find($memberId); 
    $oldEmail = TeamMember::getoldEmailById($memberId);
    $teamName = Team::getNameById($teamId);

    if ($oldMember && $oldMember->updateStatus($memberId, $teamId, 'stopped' ,  $teamName)) {
        if (empty($transferErrors)) {
            // Get the new member's email
            $newEmail = TeamMember::getEmailById($newMemberId);

            if (sendEmail($newEmail, $ticketIds, 'new') && sendEmail($oldEmail, $ticketIds, 'old', $teamName)) {
                // Redirect after processing
                header("Location: ./modify-team-member?team-id=" . urlencode($teamId));
                exit();
            } else {
                echo "Error sending email notifications.";
            }
        } else {
            // Show errors if any transfer failed
            echo implode("<br>", $transferErrors);
        }
    } else {
        echo "Error: Could not stop the old member.";
    }
}

// Function to send email notifications
function sendEmail($email, $ticketIds, $type , $teamName = null) {
    $mail = new PHPMailer(true);

    try {
        // Email server settings
        $mail->isSMTP();
        $mail->Host = 'mail.medgo.net';
        $mail->SMTPAuth = true;
        $mail->Username = 'helpdesk@medgo.net';
        $mail->Password = 'passwordhelpdesk';  // Make sure to use secure credentials
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Sender info
        $mail->setFrom('helpdesk@medgo.net', 'Ticket System');
        $mail->addAddress($email);  // Recipient email

        // Email subject and body based on the type (new or old member)
        if ($type === 'new') {
            $mail->Subject = 'Ticket(s) Assigned to You';
        } else {
            $mail->Subject = 'Ticket(s) Reassigned';
        }

        // Start building the email body
        $emailBody = 'Hello,<br><br>';
        if ($type === 'new') {
            $emailBody .= 'You have been assigned the following ticket(s):<br><br>';
        } else {
            $emailBody .= 'The following ticket(s) have been reassigned from you:<br><br>';
        }

        // Fetch the full ticket details
        $ticketDetailsArray = [];
        foreach ($ticketIds as $ticketId) {
            $ticketDetails = Ticket::find($ticketId); // Assume this method retrieves the ticket details by ID
            if ($ticketDetails) {
                $ticketDetailsArray[] = $ticketDetails;
            }
        }

        // Build the email body with ticket details
        foreach ($ticketDetailsArray as $ticket) {
            $emailBody .= "
                <strong>Ticket ID:</strong> {$ticket->id}<br>
                <strong>Title:</strong> {$ticket->title}<br>
                <strong>Description:</strong> {$ticket->body}<br>
                <strong>Status:</strong> {$ticket->status}<br>
                <strong>Priority:</strong> {$ticket->priority}<br>
                <strong>Created At:</strong> {$ticket->created_at}<br>
                <br>
            ";
        }
        
        if ($type === 'new') {
           $emailBody .= 'Please check the ticket system for more details.';
        }else {
          $emailBody .= "You are stopped from team: <strong>{$teamName}</strong>.";
        }
      
        // Set the email body and send the email
        $mail->isHTML(true);  // Enable HTML in email body
        $mail->Body = $emailBody;
        $mail->send();

        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

?>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Change Team Member</li>
        </ol>
        <div class="card mb-3">
            <div class="card-header">
                <h3>Choose a new team member:</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <label for="new_member_id">Team Member:</label>
                    <select class="form-control" name="new_member_id" id="new_member_id" required>
                        <?php foreach ($activeMembers as $activeMember):  // We choose a user to take a ticket instead of the old user?>
                            <option value="<?php echo htmlspecialchars($activeMember['id']); ?>">
                                <?php echo htmlspecialchars($activeMember['user_name']); ?>  
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <br>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include './footer.php';
// Flush the output buffer
ob_end_flush();
?>
