<?php
ob_start();
include './header.php';
require_once './src/ticket.php';
require_once './src/requester.php';
require_once './src/team.php';
require_once './src/user.php';

$ticket = new Ticket();

//We are selecting which ticket we want to check. If admin he can check every thing, while a normal user It depends on the id
  $branchId = $_SESSION['branch_id'];
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
        if ($_SESSION['user_role'] === 'admin') {
             $tickets = Ticket::findAll($branchId);  
        } else {
             $tickets = Ticket::findByMember($user->id);
        }
    } else {
        // Redirect to login if no user session found
        header("Location: sign_in");
        exit();
    }

$requester = new Requester();
$team = new Team();
$agent = new User();

if (isset($_GET['del'])) {
    $id = $_GET['del'];
    try {
        $ticket->delete($id);
        echo '<script>alert("Ticket deleted successfully");window.location = "./all-ticket-records"</script>';
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
ob_end_flush();
?>
<style>
.spinner-border {
    width: 3rem;
    height: 3rem;
    border-width: .3rem;
    display: inline-block;
    vertical-align: middle;
}
.spinner-border.text-primary {
    color: #007bff;
}
</style>
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
        <?php if ($user->role == 'admin'): ?>
        <a class="btn btn-success my-3" href="#" onclick="openPrintModal()">
            <i class="bi bi-printer-fill"></i> Print
        </a>
        <?php endif; ?>  
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
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tickets as $ticket): ?>
                            <tr>
                                <td><?php echo $ticket->id?></a></td>
                                <td><a href="./ticket-details-view?id=<?php echo $ticket->id ?>"><?php echo htmlspecialchars($ticket->title) ?></a></td>
                                <td><?php echo htmlspecialchars($requester::find($ticket->requester)->name) ?></td>
                                <td><?php echo htmlspecialchars($team::find($ticket->team)->name); ?></td>
                                <td>
                                    <?php
                                    if ($ticket->team_member) {
                                        $agentDetails = $agent::find($ticket->team_member);
                                        echo $agentDetails ? htmlspecialchars($agentDetails->name) : '--';
                                    } else {
                                        echo '--'; 
                                    }
                                    ?>
                                </td>
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
                                                <a href="./ticket-details-view?id=<?php echo $ticket->id ?>" class="dropdown-item">View</a>
                                                <a href="./ticket_summary?id=<?php echo $ticket->id ?>" class="dropdown-item">Print</a>
                                                <?php if ($user->role == 'admin'): ?>
                                                <a class="dropdown-item" onclick="return confirm('Are you sure to delete?')"
                                                    href="?del=<?php echo $ticket->id; ?>">Delete</a>
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

    <div class="modal fade" id="printModal" tabindex="-1" role="dialog" aria-labelledby="printModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printModalLabel">Print Options</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="printOptionsForm">
                        <div class="form-group">
                            <label>Select Print Option:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print-option" id="print-option-user" value="user">
                                <label class="form-check-label" for="print-option-user">Champion</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print-option" id="print-option-user" value="requester">
                                <label class="form-check-label" for="print-option-requester">Requester</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print-option" id="print-option-team" value="team">
                                <label class="form-check-label" for="print-option-team">Team</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print-option" id="print-option-branch" value="branch">
                                <label class="form-check-label" for="print-option-branch">Branch</label>
                            </div>
                             <div class="form-check">
                                <input class="form-check-input" type="radio" name="print-option" id="print-option-stock" value="stock">
                                <label class="form-check-label" for="print-option-stock">Stock</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="print-option" id="print-option-date" value="date">
                                <label class="form-check-label" for="print-option-date">Date</label>
                            </div>
                        </div>

                        <div class="form-group" id="user-group" style="display: none;">
                            <label for="user-select">Select Champion:</label>
                            <select id="user-select" class="form-control"></select>
                        </div>
                          <div class="form-group" id="requester-group" style="display: none;">
                            <label for="requester-select">Select Requester:</label>
                            <select id="requester-select" class="form-control"></select>
                        </div>
                         <div class="form-group" id="stock-group" style="display: none;">
                            <label for="stock-select">Select Stock:</label>
                            <select id="stock-select" class="form-control"></select>
                        </div>

                        <div class="form-group" id="team-group" style="display: none;">
                            <label for="team-select">Select Team:</label>
                            <select id="team-select" class="form-control"></select>
                        </div>

                        <div class="form-group" id="date-group" style="display: none;">
                            <label>Select Date Type:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="date-type" id="date-type-year" value="year">
                                <label class="form-check-label" for="date-type-year">Year</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="date-type" id="date-type-month" value="month">
                                <label class="form-check-label" for="date-type-month">Month</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="date-type" id="date-type-date" value="specific">
                                <label class="form-check-label" for="date-type-date">Date</label>
                            </div>
                            <label for="date-select" class="mt-2">Select Date:</label>
                            <input type="text" class="form-control" id="date-select">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="handlePrintSelection()">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const userGroup = document.getElementById('user-group');
            const stockGroup = document.getElementById('stock-group');
            const requesterGroup = document.getElementById('requester-group');
            const teamGroup = document.getElementById('team-group');
            const dateGroup = document.getElementById('date-group');

            document.querySelectorAll('input[name="print-option"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    const selectedOption = this.value;

                    userGroup.style.display = 'none';
                    requesterGroup.style.display = 'none';
                    stockGroup.style.display = 'none';
                    teamGroup.style.display = 'none';
                    dateGroup.style.display = 'none';

                    if (selectedOption === 'user') {
                        userGroup.style.display = 'block';
                        fetchUsers(); // Fetch users when this option is selected
                    }else if (selectedOption === 'requester') {
                        requesterGroup.style.display = 'block';
                        fetchRequesters(); // Fetch requesters when this option is selected
                    }else if (selectedOption === 'stock') {
                        stockGroup.style.display = 'block';
                        fetchStocks(); // Fetch stocks when this option is selected
                    }else if (selectedOption === 'team') {
                        teamGroup.style.display = 'block';
                        fetchTeams(); // Fetch teams when this option is selected
                    } else if (selectedOption === 'date') {
                        dateGroup.style.display = 'block';
                    }
                });
            });

            // Initialize date input formatting
            $('input[name="date-type"]').change(formatDateInput);
        });

//This function is used to show us all the users in the table to choose one of them and the print the ticket
        function fetchUsers() {
            const userSelect = document.getElementById('user-select');
            userSelect.innerHTML = '<option>Loading...</option>'; // Show loading state

            fetch('./fetch_users.php') // Ensure this endpoint returns users
                .then(response => response.json())
                .then(users => {
                    userSelect.innerHTML = ''; // Clear loading state
                    users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.id;
                        option.textContent = user.name;
                        userSelect.appendChild(option);
                    });
                });
        } 
        function fetchStocks() {
            const stockSelect = document.getElementById('stock-select');
            stockSelect.innerHTML = '<option>Loading...</option>'; // Show loading state

            fetch('./fetch_stocks.php') // Ensure this endpoint returns users
                .then(response => response.json())
                .then(stocks => {
                    stockSelect.innerHTML = ''; // Clear loading state
                    stocks.forEach(stock => {
                        const option = document.createElement('option');
                        option.value = stock.id;
                        option.textContent = stock.name;
                        stockSelect.appendChild(option);
                    });
                });
        }
        //This function same as the previous function instead it is for the requester
        function fetchRequesters() {
            const requesterSelect = document.getElementById('requester-select');
            requesterSelect.innerHTML = '<option>Loading...</option>'; // Show loading state

            fetch('./fetch_requester.php') // Ensure this endpoint returns requesters
                .then(response => response.json())
                .then(requesters => {
                    requesterSelect.innerHTML = ''; // Clear loading state
                    requesters.forEach(requester => {
                        const option = document.createElement('option');
                        option.value = requester.id;
                        option.textContent = requester.name;
                        requesterSelect.appendChild(option);
                    });
                });
        }
//This function same as the previous function instead it is for the team
        function fetchTeams() {
            const teamSelect = document.getElementById('team-select');
            teamSelect.innerHTML = '<option>Loading...</option>'; // Show loading state

            fetch('./fetch_teams.php') // Ensure this endpoint returns teams
                .then(response => response.json())
                .then(teams => {
                    teamSelect.innerHTML = ''; // Clear loading state
                    teams.forEach(team => {
                        const option = document.createElement('option');
                        option.value = team.id;
                        option.textContent = team.name;
                        teamSelect.appendChild(option);
                    });
                });
        }
//This function is when a user select a radio each radio take him to a specific page and it has the id for each radio
        function handlePrintSelection() {
            const selectedPrintOption = document.querySelector('input[name="print-option"]:checked');
            const dateValue = document.getElementById('date-select').value;
            const dateType = document.querySelector('input[name="date-type"]:checked');

            if (!selectedPrintOption) {
                alert("Please select a print option.");
                return;
            }

            let url = '';

            if (selectedPrintOption.value === 'user' || selectedPrintOption.value === 'team' || selectedPrintOption.value === 'requester' ||selectedPrintOption.value === 'stock') {
                const userId = document.getElementById('user-select').value;
                const teamId = document.getElementById('team-select').value;
                const stockId = document.getElementById('stock-select').value;
                const requesterId = document.getElementById('requester-select').value;
        
                if (selectedPrintOption.value === 'user' && userId) {
                    url = `./report-ticket-by-champion?user_id=${userId}`;
                } else if (selectedPrintOption.value === 'team' && teamId) {
                    url = `./report-ticket-by-team?team_id=${teamId}`;
                }else if (selectedPrintOption.value === 'stock' && stockId) {
                    url = `./report-ticket-by-stock?stock_id=${stockId}`;
                }else if (selectedPrintOption.value === 'requester' && requesterId) {
                    url = `./report-ticket-by-requester?requester_id=${requesterId}`;
                } else {
                    alert("Please select a user or team or requester.");
                    return;
                }
            }

            if (selectedPrintOption.value === 'date') {
                if (dateValue && dateType) {
                    url = `./report_by_date?type=date&date_type=${dateType.value}&date_value=${dateValue}`;
                } else {
                    alert("Please enter a valid date.");
                    return;
                }
            }

            window.location.href = url;
        }
//This function is a date that i can select according to the year, month and day
        function formatDateInput() {
            const dateInput = document.getElementById('date-select');
            if (this.value === 'year') {
                dateInput.type = 'number';
                dateInput.placeholder = 'Enter Year';
            } else if (this.value === 'month') {
                dateInput.type = 'month';
            } else if (this.value === 'specific') {
                dateInput.type = 'date';
            }
        }

        function openPrintModal() {
            $('#printModal').modal('show');
        }
        document.getElementById('print-option-branch').addEventListener('change', function() {
            if (this.checked) {
                // Redirect to the report-ticket-by-branch page
                window.location.href = 'report-ticket-by-branch'; // Add query parameters if needed
            }
        });
    </script>
</div>

<?php include './footer.php'; ?>
