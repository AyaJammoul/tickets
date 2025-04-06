<?php

//In here will be obtained all tickets that don't have a team member
ob_start();
    include './header.php';
    require_once './src/ticket.php';
    require_once './src/requester.php';
    require_once './src/team.php';
    require_once './src/user.php';

    $ticket = new Ticket();
  $branchId = $_SESSION['branch_id'];
    $allTicket = $ticket->unassigned($branchId);
   
    $requester = new Requester();
    $team = new Team();
    $user = new User();

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
                                <th>status</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($allTicket as $ticket):?>
                            <tr>
                                <td><?php echo $ticket->id?></a></td>
                                <td><a href="./ticket-details-view?id=<?php echo $ticket->id?>"><?php echo $ticket->title?></a></td>
                                <td><?php echo $requester::find($ticket->requester)->name?></td>
                                <td><?php echo $team::find($ticket->team)->name;?></td>
                                <?php $usr =  $ticket->team_member ?>
                                <?php if($usr == ''): ?>
                                <td><?php echo $usr ?></td>
<?php endif; ?>
                                <td><button class= "btn btn-danger"><?php echo $ticket->status ?></button></td>
                                <?php $date = new DateTime($ticket->created_at)?>
                                <td><?php echo $date->format('d-m-Y H:i:s')?> </td>
                                <td width="100px">
                                    <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                        <div class="btn-group" role="group">
                                            <button id="btnGroupDrop1" type="button"
                                                class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown"
                                                aria-haspopup="true" aria-expanded="false">
                                                Action
                                            </button>
                                            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                <a class="dropdown-item" href="./ticket-details-view?id=<?php echo $ticket->id?>">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

  </div>
 
  <?php 
  ob_end_flush();
  include './footer.php'; ?>