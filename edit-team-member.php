<?php
ob_start();

require_once './src/team-member.php';
require_once './src/team.php';
require_once './src/ticket.php';
include './header.php';

if (!isset($_GET['team-id']) || strlen($_GET['team-id']) < 1 || !ctype_digit($_GET['team-id'])) {
    echo '<script> history.back(); </script>';
    exit();
}
//From id we can take all Informations about the user in the team
$teamId = $_GET['team-id'];
$memberData = TeamMember::findByTeam2($teamId);
$members = $memberData['members'] ?? [];
$teamName = $memberData['team_name'] ?? 'Unknown Team';

?>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Edit Team Member</li>
        </ol>
        <div class="card mb-3">
            <div class="card-header">
                <h3>Team: <?php echo htmlspecialchars($teamName); ?></h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Created at</th>
                                <th>Status</th>
                                <th>Stopped at</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member->id); ?></td>
                                        <td><?php echo htmlspecialchars($member->name); ?></td>
                                        <td><?php $date = new DateTime($member->created_at); echo $date->format('d-m-Y H:i:s'); ?></td>
                                        <td><?php echo htmlspecialchars($member->status); ?></td>
                                        <td>
                                            <?php
                                            if ($member->status === 'stopped') {
                                                echo htmlspecialchars($member->updated_at);
                                            } else {
                                                echo "---";
                                            }
                                            ?>
                                        </td>
                                        <td width="150px">
                                            <?php if ($member->status === 'stopped'): ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to activate this member?');" style="display:inline;">
                                                    <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member->id); ?>">
                                                    <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($teamId); ?>">
                                                    <input type="hidden" name="action" value="activate">
                                                    <button type="submit" class="btn btn-success">Activate Member</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to stop this member?');" style="display:inline;">
                                                    <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($member->id); ?>">
                                                    <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($teamId); ?>">
                                                    <input type="hidden" name="action" value="stop">
                                                    <button type="submit" class="btn btn-warning">Stop Member</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">No team members found for team: <strong><?php echo htmlspecialchars($teamName); ?></strong>.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!--If we want to stop a user from team we must check if he has a ticket inside the table or not, according to that it will popup -->
<div class="modal fade" id="ticketsModal" tabindex="-1" role="dialog" aria-labelledby="ticketsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketsModalLabel">Open or Pending Tickets</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Dynamic content will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
include './footer.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_id'], $_POST['team_id'], $_POST['action']) && ctype_digit($_POST['member_id']) && ctype_digit($_POST['team_id'])) {
    
    //When we want to check if the user doesn't have ticket we choose the member id, and team id. According to that we will knew
    
    $memberId = $_POST['member_id'];
    $teamId = $_POST['team_id'];
    $ticket = new Ticket();
    
    //We check if he have a ticket according to status open and pending. If the user was inside the popup we put all titles of tickets he have
    
    $hasOpenOrPendingTickets = $ticket->hasOpenOrPendingTickets($memberId, $teamId);    

    //If he doesn't have any ticket automatically will be stopped from the team

    if ($_POST['action'] === 'stop') {
        if ($hasOpenOrPendingTickets) {
            $openPendingTickets = $ticket->getOpenPendingTickets($memberId, $teamId); 
            echo"ok";
            ?>
            <script>
                const openPendingTickets = <?php echo json_encode($openPendingTickets); ?>;
                
                function showTicketsModal() {
                    const modalBody = document.getElementById('modal-body');
                    modalBody.innerHTML = '';
        
                    // Create a message indicating that the member can't be stopped
                    const message = document.createElement('p');
                    message.innerText = 'This member cannot be stopped because they have unresolved tickets:';
                    modalBody.appendChild(message);
                    
                    // List the unresolved tickets
                    if (openPendingTickets.length > 0) {
                        const ticketList = document.createElement('ul');
                        openPendingTickets.forEach(ticket => {
                            const ticketItem = document.createElement('li');
                            ticketItem.innerText = ticket.title; // Assuming ticket has a title property
                            ticketList.appendChild(ticketItem);
                        });
                        modalBody.appendChild(ticketList);
                    } else {
                        modalBody.innerHTML += '<p>No open or pending tickets found.</p>';
                    }
        
                    // Add buttons for changing the member or dismissing the modal
                    const buttonContainer = document.createElement('div');
                    buttonContainer.classList.add('mt-3');
        
                    // Button to change team member
                    const changeButton = document.createElement('button');
                    changeButton.innerText = 'Change Team Member';
                    changeButton.classList.add('btn', 'btn-primary', 'mr-2');
                    changeButton.onclick = function() {
    const memberId = <?php echo json_encode($memberId); ?>;
    const teamId = <?php echo json_encode($teamId); ?>;
    const ticketIds = openPendingTickets.map(ticket => ticket.id); // Ensure tickets have an id property

    // Encode ticketIds as JSON for passing in URL
    const encodedTicketIds = encodeURIComponent(JSON.stringify(ticketIds));

    // Redirect to change-member.php with necessary parameters
    window.location.href = './ticket_member_transfer?member-id=' + memberId + '&team-id=' + teamId + '&ticket-ids=' + encodedTicketIds;
};
                    buttonContainer.appendChild(changeButton);
        
                    // Button to dismiss the modal
                    const okButton = document.createElement('button');
                    okButton.innerText = 'OK';
                    okButton.classList.add('btn', 'btn-secondary');
                    okButton.onclick = function() {
                        $('#ticketsModal').modal('hide'); // Close the modal
                    };
                    buttonContainer.appendChild(okButton);
        
                    modalBody.appendChild(buttonContainer);
        
                    $('#ticketsModal').modal('show'); 
                }
        
                showTicketsModal();
            </script>
            <?php
        } else {
            $teamMember = TeamMember::find($memberId);
            if ($teamMember && $teamMember->updateStatus($memberId , $teamId, 'stopped')) {
                header("Location: ./modify-team-member?team-id=" . urlencode($teamId));
                exit();
            } else {
                echo "Error: Failed to update the member status.";
            }
        }
    } elseif ($_POST['action'] === 'activate') {
        $teamMember = TeamMember::find($memberId); 
        if ($teamMember && $teamMember->updateStatus($memberId , $teamId, 'active')) {
            header("Location: ./modify-team-member?team-id=" . urlencode($teamId));
            exit();
        } else {
            echo "Error: Failed to activate the member.";
        }
    }
}
ob_end_flush();
?>
<script>

//This function is to know how many user we have in this team

function viewTeamMembers(ticketId) {
    console.log('Fetching team members for ticket ID:', ticketId); // Check ticketId
    fetch(`./src/get-team-members.php?ticket_id=${ticketId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const membersList = document.getElementById('modal-body');
            membersList.innerHTML = ''; // Clear previous content
            if (data.length > 0) {
                data.forEach(member => {
                    const memberItem = document.createElement('p');
                    memberItem.innerText = member.name; // Assuming member object has a name property
                    membersList.appendChild(memberItem);
                });
            } else {
                membersList.innerHTML = '<p>No team members found for this ticket.</p>';
            }
        })
        .catch(error => console.error('Error fetching team members:', error));
}

</script>