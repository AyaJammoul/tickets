<?php
ob_start();
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require './src/PHPMailer/src/Exception.php';
require './src/PHPMailer/src/PHPMailer.php';
require './src/PHPMailer/src/SMTP.php';
include './header.php';

// Check for valid ticket ID
if (!isset($_GET['id']) || strlen($_GET['id']) < 1 || !ctype_digit($_GET['id'])) {
    echo '<script> history.back()</script>';
    exit();
}

require_once './src/requester.php';
require_once './src/team.php';
require_once './src/ticket.php';
require_once './src/team-member.php';
require_once './src/comment.php';
require_once './src/ticket-image.php';
require_once './src/ticket-champion-image.php';
require_once './src/admin.php';


$err = '';
$msg = '';
$status = ''; // Initialize status variable
$ticket = Ticket::find($_GET['id']);
$teams = Team::findAll();
$events = Admin::findByTicket($ticket->id);
$comments = Comment::findByTicket($ticket->id);

// Ensure $ticket->location_id is valid
if (isset($ticket->location_id)) {
    // Use parameterized query to prevent SQL injection
    $stmt = $db->prepare("SELECT id, building, door_code FROM location WHERE id = ?");
    $stmt->bind_param("i", $ticket->location_id); // Assuming location_id is an integer
    $stmt->execute();
    $result = $stmt->get_result();

    $locations = [];

    // Fetch and process results
    while ($row = $result->fetch_object()) {
        $locations[$row->id] = [
            'building' => $row->building,
            'door_code' => $row->door_code
        ];
    }
    $stmt->close();
} else {
    $locations = [];
}

//Here we get the ticket id to make assign and put team member for it. After choosing team member he will be notified by email from his team with details of the ticket

if (isset($_POST['submit'])) {
    $teamMemberId = $_POST["team_member"]; // The selected new team member ID
    $id = $_GET['id'];
    // Fetch the new and old team member email addresses
    $newEmail = TeamMember::getEmailById($teamMemberId);
    
    //If we have an old team member we will take his id then choose new member. The old one will be notified that he is no longer with this ticket while the new one will be notified that he started with a new ticket with details
    $oldTeamMemberId = $ticket->team_member; // Old team member ID
    $oldEmail = TeamMember::getoldEmailById($oldTeamMemberId); // Corrected

    if ($newEmail) {
        try {
            // Update the ticket with the new team member
            $ticket->team_member = $teamMemberId;
            $updateTicket = $ticket->update($id);

            // Initialize PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.medgo.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'helpdesk@medgo.net';
            $mail->Password = 'passwordhelpdesk';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            // Send email to the new team member
           $mail->isHTML(true); // Enable HTML in the email body
           $mail->setFrom('helpdesk@medgo.net', 'Ticket System');
           $mail->addAddress($newEmail); // New user email
           $mail->Subject = "Ticket Assigned: " . $ticket->title;
           $mail->Body = "Hello,<br><br>"
           . "A new ticket has been assigned to you. Please check the details:<br><br>"
           . "ID: <a href='https://medgo.net/ticket-details-view?id=" . $ticket->id . "'>" . $ticket->id . "</a><br>"
           . "Title: " . $ticket->title . "<br>"
           . "Description: " . $ticket->body . "<br>"
           . "Priority: " . $ticket->priority . "<br>"
           . "Created_AT: " . $ticket->created_at;
            if ($mail->send()) {
                $msg = "Ticket assigned and email sent successfully to new user " . $newEmail;
                error_log("Email sent successfully to " . $newEmail); // Log success
            } else {
                $err = "Ticket assigned, but failed to send email to new user: " . $mail->ErrorInfo;
                error_log("PHPMailer Error (new user): " . $mail->ErrorInfo); // Log error
            }
            // Now, notify the old user that the ticket has been reassigned if they exist
            if ($oldEmail) {
                $mail->clearAddresses(); // Clear previous addresses
                $mail->addAddress($oldEmail); // Old user email
                $mail->Subject = "Ticket Reassigned: " . $ticket->title;
                $mail->Body = "Hello,\n\nThe ticket id " .$ticket->id. " titled '" . $ticket->title . "' has been reassigned to another team member.";

                if ($mail->send()) {
                    $msg .= " Email also sent to old user " . $oldEmail;
                    error_log("Email sent successfully to old user " . $oldEmail); // Log success
                } else {
                    $err .= " Failed to send email to old user: " . $mail->ErrorInfo;
                    error_log("PHPMailer Error (old user): " . $mail->ErrorInfo); // Log error
                }
            }

        } catch (Exception $e) {
            $err = "Failed to assign ticket: " . $e->getMessage();
            error_log("Error assigning ticket: " . $e->getMessage()); // Log exception
        }
    } else {
        $err = "Invalid team member selected.";
        error_log("Invalid team member selected."); // Log error
    }
}

//In comments when the member write one, it will  be sent to the admin that create the ticket with the deatils of it

if (isset($_POST['comment'])) {
    $body = $_POST["comment_body"];

    try {
        $comment = new Comment([
            'ticket-id' => $ticket->id,
            'team-member' => $ticket->team_member,
            'body' => $body,
        ]);
        $comment->save();
        $msg = "Successfully commented on the ticket";
        error_log("Comment added successfully.");
        
         $sql = "SELECT a.email 
                FROM ticket t
                JOIN admin a ON t.admin_id = a.id 
                WHERE t.id = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $ticket->id);  
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_object();
            $adminEmail = $admin->email;
        
        if ($adminEmail) {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.medgo.net';
            $mail->SMTPAuth = true;
            $mail->Username = 'helpdesk@medgo.net';
            $mail->Password = 'passwordhelpdesk'; 
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->isHTML(true);
            $mail->setFrom('helpdesk@medgo.net', 'Ticket System');
            $mail->addAddress($adminEmail); 
            $mail->Subject = "New Comment on Ticket: " . $ticket->title;
            $mail->Body = "Hello,<br><br>"
                . "A new comment has been added to the ticket you created:<br><br>"
                . "ID: <a href='https://medgo.net/ticket-details-view?id=" . $ticket->id . "'>" . $ticket->id . "</a><br>"
                . "Title: " . $ticket->title . "<br>"
                . "Description: " . $ticket->body . "<br>"
                . "Comment: " . $body . "<br><br>";

            if ($mail->send()) {
                error_log("Comment email sent successfully to " . $adminEmail); 
            } else {
                error_log("Failed to send comment email: " . $mail->ErrorInfo); 
            }
        }

    }
    }catch (Exception $e) {
        $err = "Failed to comment on the ticket: " . $e->getMessage();
        error_log("Error commenting on ticket: " . $e->getMessage());
    }
}

//Here we are presenting the available files according to ticket id
$image = new Image();
$images = $image->getImagesByEvent($ticket->id); // Replace with the actual ticket ID
 $ticketId = $_GET['id'];
//Here in the update if he wants to add jmage, we have the path to save the image in the upload file. Surely with the extentions that we selected
            // Handle multiple file uploads
            if (isset($_POST['upload'])) {
                $ticketId = $_POST['ticket_id']; 

                $targetDir = "./upload/champion/" . $ticketId . "/";  

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true); 
                }

                if (isset($_FILES['filesToUpload']) && !empty($_FILES['filesToUpload']['name'][0])) {
                    $uploadOk = 1;
                    $fileErrors = [];

                    foreach ($_FILES['filesToUpload']['name'] as $index => $fileName) {
                        $fileTmpName = $_FILES['filesToUpload']['tmp_name'][$index];
                        $lowerCaseName = strtolower($fileName);
                        $targetFile = $targetDir . basename($lowerCaseName);
                        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

                        // Check if file type is allowed
                        if (in_array($fileType, ['jpg', 'png', 'jpeg', 'gif', 'pdf', 'doc', 'docx'])) {
                            if (move_uploaded_file($fileTmpName, $targetFile)) {
                                $imagePath = $ticketId . '/' . basename($lowerCaseName); 
                                ImageChampion::create($ticketId, $imagePath);  
                            } else {
                                $fileErrors[] = "Sorry, there was an error uploading file: " . $fileName;
                            }
                        } else {
                            $fileErrors[] = "File type not allowed: " . $fileName;
                        }
                    }

                    if (empty($fileErrors)) {
                        $msg = "Files uploaded successfully.";
                    } else {
                        $err = implode("<br>", $fileErrors);
                    }
                } else {
                    $err = "No files were uploaded.";
                }
            }
            //Here in delete we get the ticket image id to delete it. It is according to each image id
// Handle file deletion
if (isset($_GET['delete'])) {
    $imageId = $_GET['delete'];

    // Query the database to get the file path based on image ID
    $sql = "SELECT image_path FROM ticket_champion_images WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the file path
        $image = $result->fetch_assoc();
        $filePath = './upload/champion/' . $image['image_path'];

        // Delete the file from the server
        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file
        }

        // Delete the image record from the database
        $deleteSql = "DELETE FROM ticket_champion_images WHERE id = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->bind_param("i", $imageId);
        $deleteStmt->execute();

        $msg = "File deleted successfully.";
        $folderPath = './upload/champion/' . $ticketId; // Path to the folder

//If the folder no longer have anything inside of it, it will automatically delete the file
// Check if the folder exists
if (is_dir($folderPath)) {
    // Check if the folder is empty
    if (count(scandir($folderPath)) == 2) { // '.' and '..' only
        // The folder is empty, so delete it
        rmdir($folderPath);
    } 
} 

    } else {
        $err = "File not found.";
    }
}


    // Fetch images and document files associated with the ticket
    $files = ImageChampion::getImages($ticketId); 
    
   $stmt = $db->prepare("SELECT id, name, t_quantity
                      FROM stock
                      WHERE t_quantity REGEXP '^[0-9]+(\.[0-9]+)?[a-zA-Z]+$' 
                      AND CAST(SUBSTRING_INDEX(t_quantity, ' ', 1) AS DECIMAL(10,2)) > 0");

$stmt->execute();
$result = $stmt->get_result();

$stocks = [];

// Fetch and process results
while ($row = $result->fetch_object()) {
    $stocks[] = $row; // Add the object to the array
}

$stmt->close();

?>
<style>
    .modal-body img {
    max-width: 100%;
    height: auto;
}

</style>
<div id="content-wrapper">

    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Ticket details</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <div class="row mx-auto">
                    <div>
                        <?php echo $ticket->displayStatusBadge() ?>
                        <small class="text-info ml-2"><?php echo $ticket->title ?> <span class="text-muted">
                                <?php $date = new DateTime($ticket->created_at ?? ''); ?>
                                <?php echo $date->format('d-m-Y H:i:s') ?>
                            </span></small>
                    </div>
                </div>
            </div>
            <?php if ($user->role == 'admin'): ?>
            <div class="card-body">
                <form method="post">
                    <div class="col-lg-8 col-md-8 col-sm-12 offset-lg-2 offset-md-2">
                        <?php if (strlen($err) > 1) : ?>
                        <div class="alert alert-danger text-center my-3" role="alert">
                            <strong>Failed! </strong> <?php echo $err; ?>
                        </div>
                        <?php endif ?>

                        <?php if (strlen($msg) > 1) : ?>
                        <div class="alert alert-success text-center my-3" role="alert">
                            <strong>Success! </strong> <?php echo $msg; ?>
                        </div>
                        <?php endif ?>

                        <div class="form-group row">
                            <label for="team" class="col-sm-3 col-form-label">Team</label>
                            <div class="col-sm-8">
                                <select class="form-control" id="team-dropdown" onchange="getTeamMember(event.target.value)">
                                    <option>--select--</option>
                                    <?php foreach ($teams as $team): ?>
                                    <option <?php echo $team->id == $ticket->team ? 'selected' : null ?>
                                        value="<?php echo $team->id ?>">
                                        <?php echo $team->name ?>
                                    </option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="assigned" class="col-sm-3 col-form-label">Assigned</label>
                            <div class="col-sm-8">
                                <select class="form-control" name="team_member" id="team-member-dropdown">
                                    <option>--select--</option>
                                </select>
                            </div>
                        </div>
                        <div class="text-center">
                            <button class="btn btn-primary" type="submit" name="submit">Assign</button>
                        </div>
                    </div>
                </form>
            </div>
             <?php endif; ?>
        </div>
       
        <div class="card mb-3">
    <div class="card-header">Ticket Details</div>
    <div class="card-body">
        <h5 class="card-title"><?php echo htmlspecialchars($ticket->title, ENT_QUOTES, 'UTF-8'); ?></h5>
        <p class="card-text"><?php echo nl2br(htmlspecialchars($ticket->body, ENT_QUOTES, 'UTF-8')); ?></p>
        <p class="card-text">  <?php 
   if (!empty($locations)) {
    foreach ($locations as $id => $location) {
        echo "Building: " . htmlspecialchars($location['building'], ENT_QUOTES, 'UTF-8') . "<br>";
        echo "Door Code: " . htmlspecialchars($location['door_code'], ENT_QUOTES, 'UTF-8') . "<br>";
    }
} else {
    echo "No location data found.";
}
?></p>
    </div>
</div>
<?php if ($images && is_array($images)): ?>
    <div class="card mb-3">
        <div class="card-header">Ticket Files</div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($images as $img): ?>
                    <?php if (isset($img['image_path'])): ?>
                        <?php 
                        $filePath = htmlspecialchars($img['image_path'], ENT_QUOTES, 'UTF-8');
                        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);
                        ?>
                        
                       <div class="col-lg-2 col-md-6 mb-3">
    <div class="card">
        <!-- f this file has an extentions like jpg, jpeg, png he will present the image of it and we click on it, it will maximise -->
        <?php if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])): ?>
            <!-- Display image
            If u click on the image the image will maximise-->
            <img src="./upload/<?php echo $filePath; ?>" alt="Ticket Image" class="card-img-top" data-toggle="modal" data-target="#imageModal" onclick="showImage('./upload/<?php echo $filePath; ?>')">
            <!-- If it has pdf/doc/docx, it must add the file we have, sure after clicking on it will take us to check the file. Definitely that pdf will take a pdf design while word as word design -->
        <?php elseif (in_array($fileExtension, ['pdf', 'doc', 'docx'])): ?>
            <!-- Display document icon -->
            <div class="card-body text-center">
                <a href="./upload/<?php echo htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                    <i class="fas fa-file-<?php echo ($fileExtension === 'pdf') ? 'pdf' : 'word'; ?> fa-5x"></i>
                    <p class="card-text"><?php echo strtoupper($fileExtension); ?> FILE</p>
                </a>
            </div>
        <?php else: ?>
            <p>Unsupported file type.</p>
        <?php endif; ?>
    </div>
</div>

                    <?php else: ?>
                        <p>Image path not found.</p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Modal for displaying enlarged image -->
    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img id="modalImage" src="" alt="Enlarged Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="card mb-3">
        <div class="card-header">Ticket Images</div>
        <!-- If we dont have image for  this image it will present for him that no images found for this event -->
        <div class="card-body">
            <p>No images found for this event.</p>
        </div>
    </div>
<?php endif; ?>

<script>
//This function is to let the div work to maximise the image
function showImage(imagePath) {
    // Set the source of the modal image to the clicked image
    document.getElementById('modalImage').src = imagePath;
}
</script>
    <div class="card mb-3">
    <div class="card-header">Comments</div>
    <div class="card-body">
        <?php if ($user->role == 'member'): ?>
        <form method="post"  id="commentForm">
            <div class="form-group">
                <textarea class="form-control" id="comment_body" name="comment_body" rows="3" placeholder="Add a comment..."></textarea>
            </div>
            <button class="btn btn-primary" type="submit" name="comment">Submit Comment</button>
        </form>
        <hr>
        <?php endif ?>
        <?php if (count($comments) > 0): ?>
        <ul class="list-group">
            <div class="col-lg-12 my-3">
                <div class="list-group">
                    <?php foreach($comments as $c): ?>
                    <div class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between align-items-start">
                            <!-- Display the user's name on the left -->
                            <h6 class="mb-1">
                                <?php echo TeamMember::getName($c->team_member); ?>
                            </h6>
                           <div class="text-end">
                                <?php if ($user->role == 'member'): ?>
                               <?php if ($_SESSION['user_id'] == $c->team_member): ?>
                               <i class="bi bi-pencil" id="edit-<?php echo $c->id; ?>" onclick="editComment(<?php echo $c->id; ?>)" style="cursor: pointer; margin-right: 20px;"></i>
                               <?php endif; ?><?php endif ?>
                               <small class="text-muted"><?php $d = new DateTime($c->created_at); echo $d->format('d-m-Y H:i:s'); ?></small>
                               </div>
                        </div>
                        <!-- Display the comment body -->
                        <p id="comment-<?php echo $c->id; ?>" class="mb-1"><?php echo $c->body; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </ul>
        <?php else: ?>
        <p>No comments yet.</p>
        <?php endif; ?>
    </div>
</div>
 <?php if ($ticket->status == 'solved' || $ticket->status == 'pending'): ?>
<div class="card mb-3">
    <div class="card-header">Stocks</div>
    <div class="card-body">
        <?php if ($user->role == 'member'): ?>
            <form id="stockForm">
                <div class="form-group">
                    <label for="stocks">Select Stocks:</label>
                    <div id="stockCheckboxes" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccc; padding: 10px;">
                        <?php foreach ($stocks as $stock): ?>
                            <?php if ($stock->t_quantity > 0): ?>
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        id="stock-<?php echo $stock->id; ?>" 
                                        name="stocks[]" 
                                        value="<?php echo $stock->id; ?>">
                                    <label class="form-check-label" for="stock-<?php echo $stock->id; ?>">
                                        <?php echo $stock->name; ?> (Available: <?php echo $stock->t_quantity; ?>)
                                    </label>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="button" id="useStockButton" class="btn btn-primary">Use Selected Stock</button>
            </form>
            <br><br>
            <?php endif ?>
       
        <!-- Display the selected stocks -->
        <h4>Selected Stocks</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Stock Name</th>
                    <th>Quantity Used</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody id="selectedStocksTableBody">
                <?php
                    $ticketId = $ticket->id;
                    $conn = Database::getInstance();
                    $query = "SELECT ss.stock_id, ss.t_quantity, s.name , ss.created_at
                              FROM stock_selections ss
                              JOIN stock s ON ss.stock_id = s.id
                              WHERE ticket_id =? ";

                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('i' , $ticketId);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['t_quantity']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>
 <?php endif; ?>
<div class="modal fade" id="stockPopup" tabindex="-1" role="dialog" aria-labelledby="stockPopupLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockPopupLabel">Selected Stock Details</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
            </div>
            <input type="hidden" id="ticketId" value="<?= $ticket->id; ?>">
            <input type="hidden" id="userId" value="<?= $_SESSION['user_id']; ?>">
            <div class="modal-body">
                <form id="popupForm">
                    <div id="selectedStocksContainer"></div>
                    <button type="submit" class="btn btn-success" id="saveButton">
                        Save
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;" id="saveSpinner"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
 <?php if ($ticket->status == 'solved' || $ticket->status == 'pending'): ?>
  <div class="card mb-3">
    <div class="card-header">Upload Files  <?php if ($user->role == 'admin'): ?> from Champion <?php endif ?></h5></div>
    <div class="card-body">
               <?php if ($user->role == 'member'): ?>
               <!-- File Upload Form -->
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticketId; ?>">
                    <div class="form-group">
                        <div class="form-group row col-lg-8 offset-lg-2">
                        <label for="filesToUpload"  class="col-sm-12 col-lg-2 col-md-2 col-form-label">Upload Files</label>
                        <div class="col-sm-8">
                        <input type="file" name="filesToUpload[]" multiple class="form-control">
                        </div>
                        </div>
                    </div>
                    <br>
                    <div class="form-group row col-lg-8 offset-lg-2">
                    <button type="submit" name="upload" class="btn btn-success">Upload Files</button>
                    </div>
                </form>
                <?php endif ?>
                <!-- Displaying Files -->
                <?php if (!empty($files)): ?>
                    <h5 class="mt-3">Uploaded Files <br>
                    <div class="row">
                        <?php foreach ($files as $file): ?>
                            <div class="col-md-2 mb-3">
                                <div class="card">
                                    <?php if (in_array(pathinfo($file['image_path'], PATHINFO_EXTENSION), ['pdf'])): ?>
                                        <div class="card-body text-center">
                                            <a href="./upload/champion/<?php echo $file['image_path']; ?>" target="_blank" class="btn btn-primary">View PDF</a>
                                        </div>
                                    <?php elseif (in_array(pathinfo($file['image_path'], PATHINFO_EXTENSION), ['doc', 'docx'])): ?>
                                        <div class="card-body text-center">
                                            <a href="./upload/champion/<?php echo $file['image_path']; ?>" target="_blank" class="btn btn-primary">View Word File</a>
                                        </div>
                                    <?php elseif (in_array(pathinfo($file['image_path'], PATHINFO_EXTENSION), ['jpg','jpeg','png'])):?>
                                        <img src="./upload/champion/<?php echo $file['image_path']; ?>" class="card-img-top" alt="File Image" data-toggle="modal" data-target="#Modal" onclick="showImage1('./upload/champion/<?php echo $filePath; ?>')">
                                    <?php else: ?>
                                       <div class="card-body">
                                           <p>No images found for this event.</p>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($user->role == 'member'): ?>
                                    <div class="card-body text-center">
                                        <a href="?id=<?php echo $ticketId; ?>&delete=<?php echo $file['id']; ?>" class="btn btn-danger">Delete</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif ?>
         <div class="modal fade" id="Modal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img id="modal" src="" alt="Enlarged Image" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

<script>
//This function is to let the div work to maximise the image
function showImage1(imagePath) {
    // Set the source of the modal image to the clicked image
    document.getElementById('modal').src = imagePath;
}
</script>
        <div class="card mb-3">
            <div class="card-header">Update Status</div>
            <div class="card-body">
            <form method="post" id="statusForm">
    <input type="hidden" name="id" value="<?php echo $ticket->id; ?>">
    <div class="form-group">
        <label for="status">Select Status</label>
        <select class="form-control" name="status" id="status">
            <option value="">--select--</option> 
            <option value="open">Open</option>
            <option value="pending">Pending</option>
            <option value="closed">Closed</option>
            <option value="solved">Solved</option>
        </select>
    </div>
    <button class="btn btn-success" type="submit">Update Status</button>
    <br><br>
    <div id="msg"></div>
</form>


            </div>
        </div>
        <div class="col-lg-12 my-3">
            <div class="list-group">
                <?php foreach($events as $e):?>
                <a href="#" class="list-group-item list-group-item-action">
                    <p class="mb-1">This ticket created by</p>
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1"><?php echo TeamMember::getNameadmin($e->admin_id)?></h6>
                        <?php $d = new DateTime($e->created_at)?>
                        <small><?php echo $d->format('d-m-Y H:i:s')?></small>
                    </div>
                </a>
                <?php endforeach?>
            </div>
        </div>
    </div>


</div>
<?php ob_end_flush();
include './footer.php'; ?>

<script>

//This function is used when the user or the member change the status, it will update the status ticket

jQuery('#statusForm').submit(function (e) {
    e.preventDefault(); // Prevent form from submitting normally

    var formData = new FormData($(this)[0]);
    var selectedStatus = jQuery('#status').val(); // Get selected status

    jQuery('#msg').html(
        '<div class="flakes-message success" style="text-align:center"><strong>Processing...</strong></div>'
    );

    // Use AJAX to update the status
   jQuery.ajax({
    url: './src/update-ticket.php', // Endpoint for updating the ticket
    type: 'post',
    dataType: 'text',
    data: formData,
    contentType: false,
    processData: false,
    success: function (res) {
        let result = JSON.parse(res);
        if (result.status == 200) {
            // Show success message for updating ticket
            jQuery('#msg').html(
                '<div class="btn btn-success" style="text-align:center"><strong><span class="fa fa-check"></span> Success!</strong> ' +
                result.msg + '</div>'
            );

            // If status is "closed", send the email
            if (selectedStatus === 'closed') {
                sendClosedStatusEmail(); // Function to handle sending the email
            }

            // Always reload the page after a short delay
            setTimeout(function() {
                location.reload();
            }, 2000); // 2 seconds delay for user feedback
        } else {
            // Show failure message for updating ticket
            jQuery('#msg').html(
                '<div class="btn btn-danger" style="text-align:center"><strong><span class="fa fa-times"></span> Failed!</strong> ' +
                result.msg + '</div>'
            );
        }
    },
    error: function () {
        // Show error message in case of failure
        jQuery('#msg').html(
            '<div class="btn btn-danger" style="text-align:center"><strong><span class="fa fa-times"></span> Error!</strong> ' +
            'An error occurred during the request.</div>'
        );
    }
});

});

//This function used when the member or admin chooses status closed it will get them to send email closed page to send email

// Function to send email when the status is "closed"
function sendClosedStatusEmail() {
    jQuery.ajax({
        url: './send-email-closed.php', // Endpoint to send email for "closed" status
        type: 'post',
        data: { id: '<?php echo $ticket->id; ?>' }, // Pass the ticket ID
        success: function (res) {
            let result = JSON.parse(res);
            if (result.status == 200) {
                // Show success message after sending the email
                jQuery('#msg').html(
                    '<div class="btn btn-success" style="text-align:center"><strong>Success!</strong> ' +
                    result.msg + '</div>'
                );
                // Reload the page after a short delay
                setTimeout(function() {
                    location.reload();
                }, 3000); // 3 seconds delay
            } else {
                // Show failure message if email sending failed
                jQuery('#msg').html(
                    '<div class="btn btn-danger" style="text-align:center"><strong>Failed!</strong> ' +
                    result.msg + '</div>'
                );
            }
        },
        error: function () {
            // Show error message if an error occurred during the email request
            jQuery('#msg').html(
                '<div class="btn btn-danger" style="text-align:center"><strong>Error!</strong> ' +
                'An error occurred while sending the email.</div>'
            );
        }
    });
}

//Edit comment function makes the member edit the comment he wrote no one other him can edit it

function editComment(commentId) {
    // Get the current comment text
    let commentText = document.getElementById(`comment-${commentId}`).innerText;
    
    // Replace the comment text with a textarea and save button
    document.getElementById(`comment-${commentId}`).innerHTML = `
        <textarea class="form-control" id="edit-text-${commentId}" rows="3">${commentText}</textarea>
        <button class="btn btn-primary mt-2" onclick="updateComment(${commentId})">Save</button>
    `;
}

//Update comment function is a function that updates the comment in database

function updateComment(commentId) {
    let updatedText = document.getElementById(`edit-text-${commentId}`).value; // Get the updated comment text

    // Make an AJAX call to update the comment
    jQuery.ajax({
        url: './src/update-comment.php', // Replace with your actual endpoint to update comments
        type: 'post',
        data: {
            comment_id: commentId,
            edit_body: updatedText
        },
        success: function(res) {
            let result = JSON.parse(res);
            if (result.status === 200) {
                // Update the UI with the new comment text
                document.getElementById(`comment-${commentId}`).innerHTML = updatedText;
            } else {
                alert("Failed to update comment: " + result.msg);
            }
        },
        error: function() {
            alert("An error occurred while updating the comment.");
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const useStockButton = document.getElementById('useStockButton');

    if (useStockButton) {
        useStockButton.addEventListener('click', function () {
            // Get all selected stock checkboxes
            const selectedStocks = Array.from(document.querySelectorAll('#stockCheckboxes input:checked'));

            // Check if any stock is selected
            if (selectedStocks.length === 0) {
                alert('Please select at least one stock.');
                return;
            }

            // Get the container where we will display the selected stock details
            const selectedStocksContainer = document.getElementById('selectedStocksContainer');
            selectedStocksContainer.innerHTML = ''; // Clear previous content

            // Populate selected stock details
            selectedStocks.forEach(stock => {
                const stockId = stock.value; // Get stock ID
                const labelElement = stock.parentNode.querySelector('label'); // Locate the label
                const stockName = labelElement ? labelElement.innerText.split(' (')[0] : ''; // Extract stock name

                // Extract quantity and unit using a regular expression
                const stockQuantityMatch = labelElement 
                    ? labelElement.innerText.match(/\(Available: (\d+)\s*([a-zA-Z]*)\)/) 
                    : null;

                if (stockQuantityMatch) {
                    const quantityAvailable = stockQuantityMatch[1]; // Extract available quantity
                    const unit = stockQuantityMatch[2] || ''; // Extract the unit (e.g., pcs, kg, etc.)

                    // Add HTML for each selected stock with its ID and quantity input
                    selectedStocksContainer.innerHTML += `
                        <div class="form-group">
                            <label for="quantity-${stockId}">${stockName} (Available: ${quantityAvailable} ${unit})</label>
                            <input type="number" class="form-control" id="quantity-${stockId}" name="quantities[${stockId}]" max="${quantityAvailable}" min="1" required>
                            <input type="hidden" name="stockIds[]" value="${stockId}">
                            <input type="hidden" name="units[${stockId}]" value="${unit}">
                        </div>
                    `;
                } else {
                    console.error('Failed to parse quantity for stock:', labelElement);
                }
            });

            // Debugging: Log the generated content
            console.log('HTML Content Added to Modal:', selectedStocksContainer.innerHTML);

            // Open the modal
            const modal = new bootstrap.Modal(document.getElementById('stockPopup'));
            modal.show();
        });
    }
});

document.getElementById('popupForm').addEventListener('submit', function (event) {
    event.preventDefault();

    // Collect selected stock IDs and quantities
    const selections = [];
    const quantityInputs = document.querySelectorAll('input[name^="quantities"]'); // Get all input fields for quantities

    // Collect ticketId and userId from hidden inputs
    const ticketId = document.getElementById('ticketId').value; // Get the ticketId
    const userId = document.getElementById('userId').value; // Get the userId

    quantityInputs.forEach(input => {
        const stockId = input.name.replace('quantities[', '').replace(']', ''); // Extract stock ID from the input name
        const quantity = input.value;

        // Add the selected stock and quantity to the selections array
        selections.push({
            stockId: stockId,
            quantity: quantity
        });
    });

    // If no selection was made, alert and stop
    if (selections.length === 0) {
        alert('Please select at least one stock.');
        return;
    }

    // Add ticketId and userId to the selections object
    const data = {
        ticketId: ticketId,
        userId: userId,
        stocks: selections
    };

    // Show spinner on the Save button
    const saveButton = document.getElementById('saveButton');
    const saveSpinner = document.getElementById('saveSpinner');
    saveSpinner.style.display = 'inline-block'; // Show spinner
    saveButton.disabled = true; // Disable button to prevent multiple submissions

    // Send data to the server
    fetch('process_stocks.php', {
        method: 'POST',
        body: JSON.stringify(data), // Send the whole data object as JSON
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Stocks successfully used!');
            location.reload(); // Refresh the page upon success
        } else {
            alert('Failed to save stocks: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        // Hide spinner and re-enable button regardless of success or failure
        saveSpinner.style.display = 'none';
        saveButton.disabled = false;
    });
});


</script>