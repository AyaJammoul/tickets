<?php
ob_start();
    include './header.php';
    require_once './src/ticket.php';
    require_once './src/requester.php';
    require_once './src/team.php';
    require_once './src/user.php';
    require_once './src/admin.php';

    // Initialize User class and check session
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

    // Initialize other classes
    $requester = new Requester();
    $team = new Team();
$branchId = $_SESSION['branch_id'];  
    // Check if the user object exists and has a role
    if (isset($user->role) && $user->role === 'admin') {
        $tickets = Ticket::findByStatus('pending',$branchId);
    } else {
        $tickets = Ticket::findByStatusAndUser('pending', $user->id);
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
            <?php foreach ($tickets as $ticket) : ?>
              <tr>
                 <td><?php echo $ticket->id?></a></td>
                <td><a href="./ticket-details-view?id=<?php echo $ticket->id ?>"><?php echo $ticket->title; ?></a></td>
                
                <td>
                    <?php 
                    $requesterObj = Requester::find($ticket->requester); // Assuming Requester::find() returns a Requester object
                    echo $requesterObj && isset($requesterObj->name) ? $requesterObj->name : "N/A";
                    ?>
                </td>

                <td>
                    <?php 
                    $teamObj = Team::find($ticket->team); // Assuming Team::find() returns a Team object
                    echo $teamObj && isset($teamObj->name) ? $teamObj->name : "N/A";
                    ?>
                </td>

                <td>
                    <?php 
                    $userObj = User::find($ticket->team_member); // Assuming User::find() returns a User object
                    echo $userObj && isset($userObj->name) ? $userObj->name : "N/A";
                    ?>
                </td>

                <td><button class="btn btn-warning"><?php echo $ticket->status; ?></button></td>

                <?php $date = new DateTime($ticket->created_at); ?>
                <td><?php echo $date->format('d-m-Y H:i:s'); ?></td>

                <td width="100px">
                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                        <div class="btn-group" role="group">
                            <button id="btnGroupDrop1" type="button" class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                <a class="dropdown-item" href="./ticket-details-view?id=<?php echo $ticket->id ?>">View</a>
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