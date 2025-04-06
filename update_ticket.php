<?php
ob_start();
// Include necessary files
include './header.php';
require_once './src/requester.php';
require_once './src/ticket.php';
require_once './src/building.php';
require_once './src/ticket-image.php'; // Ensure TicketImage class is included

$err = '';
$msg = '';

if (!isset($_GET['id'])) {
    $err = "No ticket ID provided.";
} else {
    $ticketId = $_GET['id'];
    $ticket = Ticket::find($ticketId);

    if (!$ticket) {
        $err = "Ticket not found.";
    } else {
        // Fetch teams
        $sql = "SELECT id, name FROM team ORDER BY name ASC";
        $res = $db->query($sql);
        $teams = [];
        while ($row = $res->fetch_object()) {
            $teams[] = $row;
        }

        // Fetch requesters
        $sql = "SELECT * FROM requester ORDER BY name ASC";
        $res = $db->query($sql);
        $requesters = [];
        while ($row = $res->fetch_object()) {
            $requesters[] = $row;
        }
        
        // Fetch locations
        $sql = "SELECT * FROM location ORDER BY building ASC";
        $res = $db->query($sql);
        $locations = [];
        while ($row = $res->fetch_object()) {
            $locations[] = $row;
        }

        // Fetch current requester details
        $currentRequester = $db->query("SELECT * FROM requester WHERE id = {$ticket->requester}")->fetch_object();
        
        // Handle form submission for updating ticket
        if (isset($_POST['submit'])) {
            $subject = $_POST['subject'] ?? '';
            $comment = $_POST['comment'] ?? '';
            $team = $_POST['team'] ?? '';
            $priority = $_POST['priority'] ?? '';
            $requesterId = $_POST['requester'] ?? '';
            $locationId = $_POST['location'] ?? '';

            if (empty($subject) || empty($comment) || $team === '' || $requesterId === '' || $locationId === '') {
                $err = "All fields are required.";
            } else {
                try {
                    // Update ticket details
                    $ticket->title = $subject;
                    $ticket->body = $comment;
                    $ticket->team = $team;
                    $ticket->priority = $priority;
                    $ticket->requester = $requesterId; 
                    $ticket->location_id = $locationId;
                    $ticket->update2($ticketId);
                   $currentRequester = $db->query("SELECT * FROM requester WHERE id = {$ticket->requester}")->fetch_object();
                    
                    $msg = "Ticket updated successfully.";
                } catch (Exception $e) {
                    $err = "Failed to update ticket: " . $e->getMessage();
                }
            }
        }
        
//Here in the update if he wants to add jmage, we have the path to save the image in the upload file. Surely with the extentions that we selected
            // Handle multiple file uploads
            if (isset($_POST['upload'])) {
                $ticketId = $_POST['ticket_id']; 

                $targetDir = "./upload/" . $ticketId . "/";  

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
                                Image::create($ticketId, $imagePath);  
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
    $sql = "SELECT image_path FROM ticket_images WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $imageId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch the file path
        $image = $result->fetch_assoc();
        $filePath = './upload/' . $image['image_path'];

        // Delete the file from the server
        if (file_exists($filePath)) {
            unlink($filePath); // Delete the file
        }

        // Delete the image record from the database
        $deleteSql = "DELETE FROM ticket_images WHERE id = ?";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->bind_param("i", $imageId);
        $deleteStmt->execute();

        $msg = "File deleted successfully.";
        $folderPath = './upload/' . $ticketId; // Path to the folder

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
            $files = Image::getImages($ticketId); 
        }
}
?>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Update Ticket</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Update Ticket</h3>
            </div>
            <div class="card-body">
                <?php if (strlen($err) > 0): ?>
                    <div class="alert alert-danger text-center my-3" role="alert"> <strong>Failed! </strong> <?php echo $err; ?></div>
                <?php endif; ?>

                <?php if (strlen($msg) > 0): ?>
                    <div class="alert alert-success text-center my-3" role="alert"> <strong>Success! </strong> <?php echo $msg; ?></div>
                <?php endif; ?>

                <!-- Ticket Update Form -->
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] . '?id=' . $ticketId; ?>" enctype="multipart/form-data">
                    <!-- Your existing form fields -->
<!-- Requester Selection -->
<div class="form-group row col-lg-8 offset-lg-2">
    <label for="requester" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Requester</label>
    <div class="col-sm-8">
        <select id="requester" name="requester" class="form-control" onchange="updateRequesterDetails()" required>
            <option value="">--select--</option>
            <?php foreach ($requesters as $requester): ?>
                <option value="<?= $requester->id; ?>" <?= $requester->id == $ticket->requester ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($requester->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Email -->
<div class="form-group row col-lg-8 offset-lg-2">
    <label for="email" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Email</label>
    <div class="col-sm-8">
        <input type="email" id="email" name="email" class="form-control" 
               value="<?= htmlspecialchars($currentRequester->email); ?>" readonly>
    </div>
</div>

<!-- Phone -->
<div class="form-group row col-lg-8 offset-lg-2">
    <label for="phone" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Phone</label>
    <div class="col-sm-8">
        <input type="text" id="phone" name="phone" class="form-control" 
               value="<?= htmlspecialchars($currentRequester->phone); ?>" readonly>
    </div>
</div>

                    <div class="form-group row col-lg-8 offset-lg-2">
                        <label for="subject" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Subject</label>
                        <div class="col-sm-8">
                            <input type="text" name="subject" class="form-control" value="<?php echo htmlspecialchars($ticket->title); ?>" required>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2">
                        <label for="comment" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Description</label>
                        <div class="col-sm-8">
                            <textarea name="comment" class="form-control" required><?php echo htmlspecialchars($ticket->body); ?></textarea>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2">
    <label for="location" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Location</label>
    <div class="col-sm-8">
        <select id="location" name="location" class="form-control" required>
            <option value="">--select--</option>
            <?php foreach ($locations as $location): ?>
                <option value="<?= $location->id; ?>" <?= $location->id == $ticket->location_id ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($location->building) .','. htmlspecialchars($location->door_code);?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
                    <div class="form-group row col-lg-8 offset-lg-2">
                        <label for="team" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Team</label>
                        <div class="col-sm-8">
                            <select name="team" class="form-control" required>
                                <option value="">--select--</option>
                                <?php foreach ($teams as $teamOption): ?>
                                    <option value="<?php echo $teamOption->id; ?>" <?php echo ($teamOption->id === $ticket->team ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($teamOption->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2">
                        <label for="priority" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Priority</label>
                        <div class="col-sm-8">
                            <select name="priority" class="form-control" required>
                                <option value="">--select--</option>
                                <option value="low" <?php echo ($ticket->priority === 'low' ? 'selected' : ''); ?>>Low</option>
                                <option value="medium" <?php echo ($ticket->priority === 'medium' ? 'selected' : ''); ?>>Medium</option>
                                <option value="high" <?php echo ($ticket->priority === 'high' ? 'selected' : ''); ?>>High</option>
                            </select>
                        </div>
                    </div>
                    <br>
                    <div class="form-group row col-lg-8 offset-lg-2">
                        <button type="submit" name="submit" class="btn btn-primary">Update Ticket</button>
                    </div>
                </form>

                    <br><br>
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

                <!-- Displaying Files -->
                <?php if (!empty($files)): ?>
                    <h5 class="mt-3">Uploaded Files</h5><br>
                    <div class="row">
                        <?php foreach ($files as $file): ?>
                            <div class="col-md-2 mb-3">
                                <div class="card">
                                    <?php if (in_array(pathinfo($file['image_path'], PATHINFO_EXTENSION), ['pdf'])): ?>
                                        <div class="card-body text-center">
                                            <a href="./upload/<?php echo $file['image_path']; ?>" target="_blank" class="btn btn-primary">View PDF</a>
                                        </div>
                                    <?php elseif (in_array(pathinfo($file['image_path'], PATHINFO_EXTENSION), ['doc', 'docx'])): ?>
                                        <div class="card-body text-center">
                                            <a href="./upload/<?php echo $file['image_path']; ?>" target="_blank" class="btn btn-primary">View Word File</a>
                                        </div>
                                    <?php else: ?>
                                        <img src="./upload/<?php echo $file['image_path']; ?>" class="card-img-top" alt="File Image">
                                    <?php endif; ?>
                                    <div class="card-body text-center">
                                        <a href="?id=<?php echo $ticketId; ?>&delete=<?php echo $file['id']; ?>" class="btn btn-danger">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
ob_end_flush();
include './footer.php'; ?>
<script>
function updateRequesterDetails() {
    var requesterId = document.getElementById('requester').value;

    if (requesterId) {
        fetch('get_requester_details.php?id=' + requesterId)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('email').value = data.email;
                    document.getElementById('phone').value = data.phone;
                }
            })
            .catch(error => console.error('Error:', error));
    } else {
        document.getElementById('email').value = '';
        document.getElementById('phone').value = '';
    }
}

</script> 