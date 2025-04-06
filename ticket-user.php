<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include './header.php';
require_once './src/user.php';
require_once './src/ticket-u-image.php';
require_once './src/ticket-user.php';
require './src/helper-functions.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './src/PHPMailer/src/Exception.php';
require './src/PHPMailer/src/PHPMailer.php';
require './src/PHPMailer/src/SMTP.php';

$err = '';
$msg = '';

$branchId = $_SESSION['branch_id']; 
$userId = $_SESSION['user_id']; 


$sql = "SELECT * FROM users where branch_id = $branchId AND id = $userId ";
$res = $db->query($sql);
$users = [];
$sql1 = "SELECT id, building, door_code FROM location WHERE branch_id = $branchId ORDER BY building ASC";
$res1 = $db->query($sql1);
$locations = [];
while ($row = $res->fetch_object()) {
    $users[] = $row;
}
while ($row1 = $res1->fetch_object()) {
    $locations[] = $row1;
}


//We make a new ticket. First after filling the informations of the ticket customer by the user, the admin get the email 

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $subject = $_POST['subject'];
    $comment = $_POST['comment'];
    $locationId = $_POST['location'];
    $priority = $_POST['priority'];
    $imagePath = '';

    if ($locationId == 'none') {
        $err = "Please select a location.";
    } elseif (strlen($subject) < 1) {
        $err = "Please enter a subject.";
    } elseif (strlen($comment) < 1) {
        $err = "Please enter a comment.";
    } else {
        try {
           
            $ticket = new TicketUser([
                'title' => $subject,
                'body' => $comment,
                'user_id' => $userId,
                'branch_id' => $branchId,
                'location_id' => $locationId,
                'priority' => $priority
            ]);

            $savedTicket = $ticket->save();
            $ticket_id = $savedTicket->id; // Ticket ID for image uploads

            if (!empty($_FILES['files']['name'][0])) {
                $uploadDir = "./upload/user/$ticket_id/";
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                foreach ($_FILES['files']['name'] as $key => $fileName) {
                    $tmpName = $_FILES['files']['tmp_name'][$key];
                    $lowerCaseName = strtolower($fileName);
                    $targetPath = $uploadDir . basename($lowerCaseName);
                    $fileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));

                    if (in_array($fileType, ['jpg', 'png', 'jpeg', 'gif', 'pdf', 'doc', 'docx'])) {
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $imagePath = "$ticket_id/$lowerCaseName";
                            ImageU::create($ticket_id, $imagePath);
                        }
                    }
                }
            }

            $msg = "Ticket created successfully!";
        } catch (Exception $e) {
            $err = "Error creating ticket: " . $e->getMessage();
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

                <form id="ticketForm" action="ticket-user.php" method="post" enctype="multipart/form-data" class="form-horizontal">
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-3 col-form-label">Champion Name <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <?php foreach ($users as $user) : ?>
                            <input type="name" name="name" class="form-control" id="name" readonly required placeholder="Name user" value="<?= $user->name; ?>" data-email="<?= $user->email; ?>" data-phone="<?= $user->phone; ?>">
                            <?php endforeach ?>
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="email" class="col-sm-3 col-form-label">Champion Email <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <input type="email" name="email" class="form-control" id="email" readonly required placeholder="Email user" value="<?= $user->email; ?>">
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="phone" class="col-sm-3 col-form-label">Champion Phone <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <input type="tel" name="phone" class="form-control" id="phone" readonly required placeholder="Phone user" value="<?= $user->phone; ?>">
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="subject" class="col-sm-3 col-form-label">Subject <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <input type="text" name="subject" class="form-control" placeholder="Enter subject" required></textarea>
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="comment" class="col-sm-3 col-form-label">Description <span class="required">*</span></label></label>
                        <div class="col-sm-9">
                            <textarea name="comment" class="form-control" placeholder="Enter description" required></textarea>
                        </div>
                    </div>
                   <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                       <label for="location" class="col-sm-3 col-form-label">Location <span class="required">*</span></label>
                       <div class="col-sm-9">
                           <select name="location" class="form-control" required>
                               <option value="none" selected disabled>Select a location</option>
                               <?php foreach ($locations as $location): ?>
                               <option value="<?= $location->id; ?>">
                                   <?= htmlspecialchars($location->building); ?>, <?= htmlspecialchars($location->door_code); ?>
                               </option>
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
document.querySelector('form').addEventListener('submit', function (e) {
    const locationSelect = document.querySelector('select[name="location"]');
    if (locationSelect.value === "none") {
        e.preventDefault(); // Prevent form submission
        alert("Please select a location.");
        locationSelect.focus(); // Bring focus to the dropdown
    }
});
</script>