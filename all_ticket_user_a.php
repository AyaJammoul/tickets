<?php
ob_start();
include './header.php';
require_once './src/Database.php'; 
require_once './src/ticket-user.php';
require_once './src/user.php';

// Get branch ID from session
$branchId = $_SESSION['branch_id'];

// Fetch all tickets for the branch
$tickets = Ticketuser::findAll2($branchId);

// Get database connection
$db = Database::getInstance();

// Fetch all users
$sql = "SELECT id, name FROM users ORDER BY name ASC";
$res = $db->query($sql);
$users = [];

// Map user ID to user name for easy lookup
while ($row = $res->fetch_object()) {
    $users[$row->id] = $row->name;
}

// Fetch all locations
$sql = "SELECT id, building, door_code FROM location";
$res = $db->query($sql);
$locations = [];

// Map location ID to building and door code for easy lookup
while ($row = $res->fetch_object()) {
    $locations[$row->id] = $row->building . ', ' . $row->door_code;
}

// Fetch all ticket images
$sql = "SELECT id, ticket_id, image_path FROM ticket_u_images";
$res = $db->query($sql);
$images = [];

// Map ticket ID to image paths for easy lookup
while ($row = $res->fetch_object()) {
    if (!isset($images[$row->ticket_id])) {
        $images[$row->ticket_id] = [];
    }
    $images[$row->ticket_id][] = $row->image_path; // Allow multiple images per ticket
}
// Fetch all teams
$sql = "SELECT id, name FROM team ORDER BY name ASC";
$res = $db->query($sql);
$teams = [];

// Map user ID to user name for easy lookup
while ($row = $res->fetch_object()) {
    $teams[$row->id] = $row->name;
}
// Fetch all requesters
$sql = "SELECT id, name FROM requester ORDER BY name ASC";
$res = $db->query($sql);
$requesters = [];

// Map user ID to user name for easy lookup
while ($row = $res->fetch_object()) {
    $requesters[$row->id] = $row->name;
}
ob_end_flush();
?>

<script>
// Initialize DataTable with descending order by ID
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[0, "desc"]]
    });
});

// Function to show images in a modal
function showImages(imagePaths) {
    const carousel = document.getElementById('carouselImages');
    carousel.innerHTML = ''; // Clear previous images
    imagePaths.forEach((path, index) => {
        const isActive = index === 0 ? 'active' : '';
        carousel.innerHTML += `
            <div class="carousel-item ${isActive}">
                <img src="/upload/user/${path}" class="d-block w-100" alt="Ticket Image">
            </div>
        `;
    });
    $('#imageModal').modal('show'); // Show the modal
}
</script>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
            <li class="breadcrumb-item active">Overview</li>
        </ol>
        <div class="card mb-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Description</th>
                                <th>user</th>
                                <th>Location</th>
                                <th>Priority</th>
                                <th>File</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ticket->id); ?></td>
                                <td><?php echo htmlspecialchars($ticket->title); ?></td>
                                <td><?php echo htmlspecialchars($ticket->body); ?></td>
                                <td>
                                    <?php
                                    // Get user name based on user ID
                                    echo isset($users[$ticket->user_id]) 
                                        ? htmlspecialchars($users[$ticket->user_id]) 
                                        : 'N/A';
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    // Get location based on location ID
                                    echo isset($locations[$ticket->location_id]) 
                                        ? htmlspecialchars($locations[$ticket->location_id]) 
                                        : 'N/A';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($ticket->priority); ?></td>
                                <td>
                                    <?php
                                    // Display files (images or other types)
                                    if (isset($images[$ticket->id])) {
                                        $imagePaths = $images[$ticket->id];
                                        echo "<a href='#' onclick='showImages([" . implode(',', array_map(fn($path) => "\"$path\"", $imagePaths)) . "])'>View Files</a>";
                                    } else {
                                        echo 'No file';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $date = new DateTime($ticket->created_at); 
                                    echo $date->format('d-m-Y H:i:s');
                                    ?>
                                </td>
                               <td width="150px">
    <button class="btn btn-danger btn-sm" onclick="updateStatus(<?php echo $ticket->id; ?>, 'no')">No</button>
    <button class="btn btn-success btn-sm" onclick="showTeamPopup(<?php echo $ticket->id; ?>)">Yes</button>
</td>

                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Ticket Images</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="imageCarousel" class="carousel slide" data-ride="carousel">
          <div class="carousel-inner" id="carouselImages"></div>
          <a class="carousel-control-prev" href="#imageCarousel" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="sr-only">Previous</span>
          </a>
          <a class="carousel-control-next" href="#imageCarousel" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="sr-only">Next</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Team Selection Modal -->
<div class="modal fade" id="teamModal" tabindex="-1" role="dialog" aria-labelledby="teamModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="teamModalLabel">Assign Team</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="teamForm">
                    <div class="form-group">
                        <label for="teamSelect">Select Team</label>
                        <select class="form-control" id="teamSelect" name="team_id">
                            <!-- Populate team options dynamically -->
                            <?php foreach ($teams as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="requesterelect">Select Requester</label>
                        <select class="form-control" id="requesterSelect" name="requester_id">
                            <!-- Populate requester options dynamically -->
                            <?php foreach ($requesters as $id => $name): ?>
                            <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveTicketDetails()">Save</button>
            </div>
        </div>
    </div>
</div>


<?php include './footer.php'; ?>
<script>
function updateStatus(ticketId, status) {
    if (confirm("Are you sure you want to update the ticket status to '" + status + "'?")) {
        fetch('./update_ticket_status_u.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ticketId: ticketId, status: status }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Ticket status updated successfully.");
                location.reload(); // Refresh page to see changes
            } else {
                alert("Failed to update ticket status.");
            }
        })
        .catch(err => console.error(err));
    }
}

function showTeamPopup(ticketId) {
    // Open the team selection modal
    $('#teamModal').data('ticket-id', ticketId).modal('show');
}

function saveTicketDetails() {
    const ticketId = $('#teamModal').data('ticket-id');
    const teamId = $('#teamSelect').val();
    const requesterId = $('#requesterSelect').val();

    const formData = new FormData();
    formData.append('ticketId', ticketId);
    formData.append('teamId', teamId);
    formData.append('requesterId', requesterId);
    
    fetch('./save_ticket_details_u.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Ticket updated successfully.");
            $('#teamModal').modal('hide');
            location.reload(); // Refresh page
        } else {
            alert("Failed to save ticket details.");
        }
    })
    .catch(err => console.error(err));
}
</script>
