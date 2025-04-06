<?php
ob_start(); 
    include './header.php';
    require_once './src/ticket.php';
    require_once './src/requester.php';
    require_once './src/team.php';
    require_once './src/user.php';
    require_once './src/admin.php';

    // Initialize User or Admin based on session role
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'admin') {
            $user = Admin::find($_SESSION['user_id']);
        } else {
            $user = User::find($_SESSION['user_id']);
        }
    } else {
        // Redirect to login if no user session found
        header("Location: sign_in");
        exit();
    }

    $branchId = $_SESSION['branch_id'];
    $requester = new Requester();
    $team = new Team();
    // Check if the user object exists and determine ticket retrieval based on role
    if (isset($user->role) && $user->role === 'admin') {
        // Retrieve all open tickets for the admin by branch
        $tickets = Ticket::findByStatus('open', $branchId);
    } else {
        // Retrieve tickets specific to the user for non-admins
        $tickets = Ticket::findByStatusAndUser('open', $user->id);
    }

   
    // Check if 'del' is set in the query string
if (isset($_GET['del'])) {
    $ticketId = intval($_GET['del']); // Get the ticket ID

    // Call the delete method
    if (Ticket::delete($ticketId)) {
        // Redirect back to the page to prevent resubmission on reload
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<script>alert('Failed to delete the ticket. Please try again.');</script>";
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
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
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
                                <th>Requester</th>
                                <th>Team</th>
                                <th>Agent</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo $ticket->id?></a></td>
                                <td><a href="./ticket-details-view?id=<?php echo $ticket->id?>"><?php echo $ticket->title?></a></td>
                                <td><?php echo $requester::find($ticket->requester)->name?></td>
                                <td><?php echo $team::find($ticket->team)->name ?? ' ';?></td>
                                 <td>
                    <?php 
                    $userObj = User::find($ticket->team_member); // Assuming User::find() returns a User object
                    echo $userObj && isset($userObj->name) ? $userObj->name : "N/A";
                    ?>
                </td>
                                <td><button class="btn btn-danger"><?php echo $ticket->status ?></button></td>
                                <?php $date = new DateTime($ticket->created_at); ?>
                                <td><?php echo $date->format('d-m-Y H:i:s')?> </td>
                                <td width="100px">
                                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                        <div class="btn-group" role="group">
                                            <button id="btnGroupDrop1" type="button" class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                Action
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                <a href="./ticket-details-view?id=<?php echo $ticket->id?>" class="dropdown-item">View</a>
                                                <?php if ($user->role === 'admin'): ?>
                                                    <a class="dropdown-item" href="update-ticket-info?id=<?php echo $ticket->id; ?>">Update</a>
                                                    <a class="dropdown-item" onclick="return confirm('Are you sure to delete?')" href="?del=<?php echo $ticket->id; ?>">Delete</a>
                                                <?php endif; ?>
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
    <?php include './footer.php'; ?>
</div>
