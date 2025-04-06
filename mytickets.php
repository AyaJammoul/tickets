<?php
ob_start();
include './header.php';
require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

//The ticket will obtain according to user id cause in this page must obtain only his tickets

$tickets = Ticket::findByMember($user->id);

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
            <li class="breadcrumb-item active">My tickets</li>
        </ol>
        <div class="card mb-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Subject</th>
                                <th>Requester</th>
                                <th>Team</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket) : ?>
                                <tr>
                                     <td><?php echo $ticket->id?></a></td>
                                    <td><a href="./ticket-details-view?id=<?php echo $ticket->id ?>"><?php echo $ticket->title ?></a>
                                    </td>
                                    <td><?php echo Requester::find($ticket->requester)->name ?></td>
                                    <td><?php echo Team::find($ticket->team)->name; ?></td>
                                    <?php $usr =  $ticket->team_member ?>
                                    <?php if ($usr == '') : ?>
                                        <td><?php echo $usr ?></td>
                                    <?php endif; ?>
                                    <?php if ($ticket->status == 'solved') : ?>
                                        <td>
                                            <button class="btn btn-success"><?php echo $ticket->status ?></button>
                                        </td>
                                       <?php elseif ($ticket->status == 'pending') : ?>
                                       <td>
                                     <button class="btn btn-warning"><?php echo $ticket->status ?></button>
                                        </td>
                                    <?php else : ?>
                                        <td>
                                            <button class="btn btn-danger"><?php echo $ticket->status ?></button>
                                        </td>
                                    <?php endif; ?>
                                    <?php $date = new DateTime($ticket->created_at) ?>
                                    <td><?php echo $date->format('d-m-Y H:i:s') ?> </td>
                                    <td width="100px">
                                        <div class="btn-group" role="group" aria-label="Button group with nested dropdown">
                                            <div class="btn-group" role="group">
                                                <button id="btnGroupDrop1" type="button" class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Action
                                                </button>
                                                <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                                    <a class="dropdown-item" href="./ticket-details-view?id=<?php echo $ticket->id ?>">View</a>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
    <?php 
    ob_end_flush();
    include './footer.php'; ?>