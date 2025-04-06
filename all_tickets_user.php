<?php
ob_start();
include './header.php';
require_once './src/ticket-user.php';

$ticket = new Ticketuser();

//We are selecting which ticket we want to check. If admin he can check every thing, while a normal user It depends on the id
  $branchId = $_SESSION['branch_id'];
  $userId = $_SESSION['user_id'];
  
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'member') {
             $tickets = Ticketuser::findAll($branchId,$userId);  
        }
    } else {
        // Redirect to login if no user session found
        header("Location: sign_in");
        exit();
    }
    foreach ($tickets as $ticket):
    $ticketlocation = $ticket->location_id;

    $sql = "SELECT id, building, door_code FROM location WHERE branch_id = $branchId AND id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $ticketlocation);
    $stmt->execute();
    $result = $stmt->get_result();
    $location = $result->fetch_object();

    // Ensure $location exists before trying to access properties
    
endforeach;
 

if (isset($_GET['del'])) {
    $id = $_GET['del'];
    try {
        $ticket->delete($id);
        echo '<script>alert("Ticket deleted successfully");window.location = "./all-ticket-user"</script>';
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
ob_end_flush();
?>
<script>
//We are working on descending order to let the newst id obtain first

    $(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[0, "desc"]] 
    });
});
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
                                <th>location</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo $ticket->id?></a></td>
                                <td><a href="./ticket-details-view?id=<?php echo $ticket->id ?>"><?php echo htmlspecialchars($ticket->title) ?></a></td>
                                <td><?php echo  $location ->building. ', ' . $location ->door_code ?></td>
                                <?php $date = new DateTime($ticket->created_at); ?>
                                <td><?php echo $date->format('d-m-Y H:i:s'); ?> </td>
                                <td width="100px">
                                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                        <div class="btn-group" role="group">
                                            <button id="btnGroupDrop1" type="button" class="btn btn-outline-primary dropdown-toggle"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Action
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                <a href="./edit-ticket-of-user?id=<?php echo $ticket->id ?>" class="dropdown-item">View</a>
                                                <a class="dropdown-item" onclick="return confirm('Are you sure to delete?')"
                                                    href="?del=<?php echo $ticket->id; ?>">Delete</a>
                                            </div>
                                        </div>
                                    </div>
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

<?php include './footer.php'; ?>
