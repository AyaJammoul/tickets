<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include './header.php';
require_once './src/requester.php';
require_once './src/ticket.php';
require './src/helper-functions.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './src/PHPMailer/src/Exception.php';
require './src/PHPMailer/src/PHPMailer.php';
require './src/PHPMailer/src/SMTP.php';

$err = '';
$msg = '';

$branchId = $_SESSION['branch_id']; 
$adminId = $_SESSION['user_id']; 

// Getting teams 
$sql = "SELECT id, name FROM team ORDER BY name ASC";
$res = $db->query($sql);
$teams = [];
$sql1 = "SELECT * FROM requester  where branch_id = $branchId ORDER BY name ASC";
$res1 = $db->query($sql1);
$requesters = [];
$sql2 = "SELECT id, building, door_code FROM location WHERE branch_id = $branchId ORDER BY building ASC";
$res2 = $db->query($sql2);
$locations = [];
while ($row = $res->fetch_object()) {
    $teams[] = $row;
}
while ($row1 = $res1->fetch_object()) {
    $requesters[] = $row1;
}
while ($row2 = $res2->fetch_object()) {
    $locations[] = $row2;
}


//We make a new ticket. First after filling the informations of the ticket customer by the admin, the admin get the email of the customer then send him email that the ticket have been created with the time of creation

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
    $comment = $_POST['comment'];
    $locationId = $_POST['location_id'];
    $team = $_POST['team'];
    $priority = $_POST['priority'];
    $imagePath = '';

    if ($name == 'none') {
        $err = "Please select requester name";
    } else if (strlen($subject) < 1) {
        $err = "Please enter subject";
    } else if (strlen($comment) < 1) {
        $err = "Please enter comment";
    } else if ($team == 'none') {
        $err = "Please select team";
    } else {
        try {
            $sql = "SELECT * FROM requester WHERE email = ? And branch_id = $branchId";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Requester already exists, retrieve their details
                $savedRequester = $result->fetch_object();
            } 
            // Create a new ticket
            $ticket = new Ticket([
                'title' => $subject,
                'body' => $comment,
                'requester' => $savedRequester->id,
                'branch_id' =>$branchId,
                'admin_id' =>$adminId,
                'location_id' => $locationId,
                'team' => $team,
                'priority' => $priority
            ]);

            $savedTicket = $ticket->save();
            $ticket_id = $savedTicket->id; // Use this ID for image uploads

            // Handle image uploads
            if (isset($_FILES['files']) && $_FILES['files']['error'][0] === UPLOAD_ERR_OK) {
    $uploadDir = './upload/' . $ticket_id . '/';
    
    // Create the directory if it does not exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
 
//With foreach were are making upload dir with file name to save it inside upload folder. Using if clause we're saving ticket id with file name inside the database

    foreach ($_FILES['files']['name'] as $key => $name) {
        $tmpName = $_FILES['files']['tmp_name'][$key];
        $lowerCaseName = strtolower($name);
        $fileName = basename($lowerCaseName);  // Get the file name
        
        $targetPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $targetPath)) {
            // Store only the image name in the database
            $imagePath = $ticket_id . '/' . $fileName; // Save the image name, not the full path

            // Insert the ticket_id and image name into the database
            $imageSql = "INSERT INTO ticket_images (ticket_id, image_path) VALUES (?, ?)";
            $imageStmt = $db->prepare($imageSql);
            $imageStmt->bind_param("is", $ticket_id, $imagePath);
            $imageStmt->execute();
        }
    }
}
$clientName = isset($savedRequester->name) && !empty($savedRequester->name) ? $savedRequester->name : "Valued Customer";
            // Sending email notification
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.medgo.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'helpdesk@medgo.net';
            $mail->Password = 'passwordhelpdesk';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            // Send email to the requester
            $createdAt = $savedTicket->created_at; // Retrieve created_at timestamp
            $mail->setFrom('helpdesk@medgo.net', 'Ticket System');
            $mail->addAddress($email); // Send to requester's email
            $mail->isHTML(true);
            $mail->Subject = "Ticket Created: " . $subject;
            $mail->Body = "Dear " . htmlspecialchars($clientName)  . ",<br><br>Your ticket has been created successfully with the ticket ID: " . $savedTicket->id . ".<br>Title: " . $savedTicket->title . "<br>Description: " . $savedTicket->body. "<br>If you wish to provide additional information, please reply to this email or login to your account.<br><br>Our team will get back to you shortly.";

            $mail->send();
            $msg .= " An email was sent to the client.";
        } catch (Exception $e) {
            $err .= " Failed to send email to the client: " . $e->getMessage();
        }

//We will go and bring email of the admin that create the ticket so also he will get an email that the ticket have been created
$creator_email = $_SESSION['email'];

            // Send email to the admin
            $mail->clearAddresses();
            $mail->addAddress($creator_email);
            $mail->Subject = "New Ticket Created: " . $subject;
            $mail->Body = "A new ticket has been created.<br>Details:<br>Requester: " . $name . "<br>Ticket ID: " . $savedTicket->id . "<br>Title: " . $savedTicket->title . "<br>Description: " . $savedTicket->body . "<br>Priority: " . $priority . "<br>Created At: " . $createdAt . "<br><br>Please review the ticket.";

            try {
                $mail->send();
                $msg .= " An email was also sent to the admin.";
            } catch (Exception $e) {
                $err .= " Failed to send email to admin: " . $e->getMessage();
            }
            
        // Final message to indicate both emails were sent
        if (strpos($msg, "An email was sent to the client.") !== false && strpos($msg, "An email was also sent to the admin.") !== false) {
            $msg = "Emails were sent to both the client and the admin.";
        }
    }
}
?>
<style>
    .required {
    color: red;
}
</style>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">New Ticket</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a new ticket</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($err)) : ?>
                    <div class="alert alert-danger"><?= $err; ?></div>
                <?php endif; ?>
                <?php if (!empty($msg)) : ?>
                    <div class="alert alert-success"><?= $msg; ?></div>
                <?php endif; ?>

                <form id="ticketForm" action="ticket.php" method="post" enctype="multipart/form-data" class="form-horizontal">
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-3 col-form-label">Requester Name <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <select name="name" class="form-control" id="name" required>
                                <option value="none" selected>Select a Requester</option>
                                <?php foreach ($requesters as $requester) : ?>
                                    <option value="<?= $requester->id; ?>" data-email="<?= $requester->email; ?>" data-phone="<?= $requester->phone; ?>">
                                        <?= $requester->name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="email" class="col-sm-3 col-form-label">Requester Email <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <input type="email" name="email" class="form-control" id="email" readonly required placeholder="Email Requester">
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="phone" class="col-sm-3 col-form-label">Requester Phone <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <input type="tel" name="phone" class="form-control" id="phone" readonly required placeholder="Phone Requester">
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="subject" class="col-sm-3 col-form-label">Subject <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <input type="text" name="subject" class="form-control" placeholder="Enter subject" required />
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="comment" class="col-sm-3 col-form-label">Description <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <textarea name="comment" class="form-control" placeholder="Enter description" required></textarea>
                        </div>
                    </div>
                     <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="location" class="col-sm-3 col-form-label">location <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <select name="location_id" class="form-control" required>
                                <option value="none" selected disabled>Select a location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location->id; ?>"><?= $location->building; ?> , <?= $location->door_code; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="team" class="col-sm-3 col-form-label">Team <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <select name="team" class="form-control" required>
                                <option value="none" selected disabled>Select a team</option>
                                <?php foreach ($teams as $team): ?>
                                    <option value="<?= $team->id; ?>"><?= $team->name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="priority" class="col-sm-3 col-form-label">Priority <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <select name="priority" class="form-control" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="fileUpload" class="col-sm-3 col-form-label">Upload Files:</label>
                        <div class="col-sm-9">
                            <!-- Here in the input we specify the files that we can choose for the image -->
                            <input type="file" name="files[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="form-control">
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <div class="col-sm-8 offset-sm-2">
                            <button type="submit" name="submit" class="btn btn-primary" onclick="createTicket()">Create Ticket</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php ob_end_flush();
include './footer.php'; ?>
<script>
    // JavaScript to autofill email and phone based on selected requester
    document.getElementById('name').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        document.getElementById('email').value = selectedOption.getAttribute('data-email');
        document.getElementById('phone').value = selectedOption.getAttribute('data-phone');
    });
  
document.querySelector('form').addEventListener('submit', function (e) {
    const errors = []; // Array to hold error messages

    const locationSelect = document.querySelector('select[name="location_id"]');
    if (locationSelect.value === "none") {
        errors.push("Please select a location.");
    }

    const teamSelect = document.querySelector('select[name="team"]');
    if (teamSelect.value === "none") {
        errors.push("Please select a team.");
    }

    const requesterSelect = document.querySelector('select[name="name"]');
    if (requesterSelect.value === "none") {
        errors.push("Please select a requester name.");
    }

    if (errors.length > 0) {
        e.preventDefault(); // Prevent form submission
        alert(errors.join("\n")); // Show all errors in one popup
        // Optionally focus on the first invalid field
        if (locationSelect.value === "none") {
            locationSelect.focus();
        } else if (teamSelect.value === "none") {
            teamSelect.focus();
        } else if (requesterSelect.value === "none") {
            requesterSelect.focus();
        }
    }
});


</script>
