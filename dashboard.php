<?php 
ob_start();
require_once './src/Database.php';
include './header.php';

$statusFilter = "";
$dateFilter = "";
$branchFilter = ""; 

//If a member tries to get into the dashboard he cant it will automatically take him to open page
if ($_SESSION['user_role'] == 'member') {
    header('Location: ./view-open-tickets');
    exit();
}elseif ($_SESSION['user_role'] == 'manager'){
    header('Location: ./create-new-ticket-of-manager');
    exit();
}
//We take the status from filter and check what we have tickets in table
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $statusFilter = " AND status = '$status'";
}

$branchId = $_SESSION['branch_id']; 

// Build the branch filter
$branchFilter = ($branchId) ? "AND branch_id = $branchId" : "";

//We have also the start date we take it from filter with the end date and we compare between them if the ticket is in created_at row
if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $dateFilter = " AND created_at BETWEEN '$startDate' AND '$endDate'";
}
//Makes count in each table to create chart and statics definitely we use status and date to use filter
$sql = "SELECT 
    (SELECT COUNT(*) FROM ticket WHERE 1=1 $statusFilter $dateFilter $branchFilter) AS total_tickets,
    (SELECT COUNT(*) FROM ticket WHERE status = 'open' $statusFilter $dateFilter $branchFilter) AS open_tickets,
    (SELECT COUNT(*) FROM ticket WHERE status = 'solved' $statusFilter $branchFilter $dateFilter) AS solved_tickets,
    (SELECT COUNT(*) FROM ticket WHERE status = 'closed' $statusFilter $branchFilter $dateFilter) AS closed_tickets,
    (SELECT COUNT(*) FROM ticket WHERE status = 'pending' $statusFilter $branchFilter $dateFilter) AS pending_tickets,
    (SELECT COUNT(*) FROM ticket WHERE team_member = ' ' $statusFilter $branchFilter $dateFilter) AS unassigned_tickets,
    (SELECT COUNT(*) FROM ticket WHERE 1=1 $statusFilter $dateFilter $branchFilter) AS tickets,
    (SELECT COUNT(*) FROM team) AS teams,
    (SELECT COUNT(*) FROM users WHERE role = 'member' $branchFilter) AS users,
    (SELECT COUNT(*) FROM admin WHERE role = 'admin' $branchFilter) AS adminusers,
    (SELECT COUNT(*) FROM manager WHERE 1=1 $branchFilter) AS managers,
    (SELECT COUNT(*) FROM requester WHERE 1=1 $branchFilter) AS requesters";

$result = $db->query($sql);

if ($result->num_rows > 0) {
  // Fetch the single row
  $row = $result->fetch_assoc();

  // Assign data to variables
  $totalTickets = $row['total_tickets'];
  $openTickets = $row['open_tickets'];
  $solvedTickets = $row['solved_tickets'];
  $closedTickets = $row['closed_tickets'];
  $pendingTickets = $row['pending_tickets'];
  $unassignedTickets = $row['unassigned_tickets'];
  $tickets = $row['tickets'];
  $team = $row['teams'];
  $users = $row['users'];
  $adminusers = $row['adminusers'];
  $managers = $row['managers'];
  $requesters = $row['requesters'];

} else {
  echo "No data found";
}

//Same in status and date but in sql different we check each team how many ticket they have and makes count of them
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $statusFilter = " AND status = '$status'";
}

if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $dateFilter = " AND tc.created_at BETWEEN '$startDate' AND '$endDate'";
}
$sql = "SELECT t.name AS team_name, COUNT(tc.id) AS ticket_count
        FROM team t
        JOIN ticket tc ON tc.team = t.id
        WHERE 1=1 $statusFilter $dateFilter $branchFilter
        GROUP BY t.name";

$result1 = $db->query($sql);

$teams1 = [];
$ticketCounts1 = [];

if ($result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        $teams1[] = $row['team_name'];          
        $ticketCounts1[] = $row['ticket_count'];  
    }
}

//Same in status, date, and sql. The ticket depends on team but the difference is that we are making sum for each team how much he has tickets according to status
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $statusFilter = " AND status = '$status'";
}

if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $dateFilter = " AND tc.created_at BETWEEN '$startDate' AND '$endDate'";
}

$sql = "
    SELECT 
        t.name AS team_name,tc.created_at,
        SUM(tc.status = 'open') AS open_tickets,
        SUM(tc.status = 'solved') AS solved_tickets,
        SUM(tc.status = 'closed') AS closed_tickets,
        SUM(tc.status = 'pending') AS pending_tickets,
        SUM(tc.team_member = '') AS unassigned_tickets
    FROM team t
    LEFT JOIN ticket tc ON t.id = tc.team
    WHERE 1=1 $statusFilter $dateFilter $branchFilter
    GROUP BY t.name
";

$result2 = $db->query($sql);

$teams = [];
$openCounts = [];
$solvedCounts = [];
$closedCounts = [];
$pendingCounts = [];
$unassignedCounts = [];

if ($result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $teams[] = $row['team_name'];
        $openCounts[] = $row['open_tickets'];
        $solvedCounts[] = $row['solved_tickets'];
        $closedCounts[] = $row['closed_tickets'];
        $pendingCounts[] = $row['pending_tickets'];
        $unassignedCounts[] = $row['unassigned_tickets'];
    }
}


//The status and date are the same, but sql uses priority to make count
if (isset($_GET['status']) && $_GET['status'] !== '') {
    $status = $_GET['status'];
    $statusFilter = " AND status = '$status'";
}

if (isset($_GET['start_date']) && !empty($_GET['start_date']) && isset($_GET['end_date']) && !empty($_GET['end_date'])) {
    $startDate = $_GET['start_date'];
    $endDate = $_GET['end_date'];
    $dateFilter = " AND created_at BETWEEN '$startDate' AND '$endDate'";
}
$sqlPriority = "
    SELECT 
         (SELECT COUNT(*) FROM ticket WHERE priority = 'low' $statusFilter $dateFilter $branchFilter) AS low_tickets,
         (SELECT COUNT(*) FROM ticket WHERE priority = 'medium' $statusFilter $dateFilter $branchFilter) AS medium_tickets,
         (SELECT COUNT(*) FROM ticket WHERE priority = 'high' $statusFilter $dateFilter $branchFilter) AS high_tickets";

$resultPriority = $db->query($sqlPriority);

$priorityCounts = [];
if ($resultPriority->num_rows > 0) {
    $row = $resultPriority->fetch_assoc();
    
    $priorityCounts = [
        'low' => $row['low_tickets'],
        'medium' => $row['medium_tickets'],
        'high' => $row['high_tickets']
    ];
} else {
    // Handle case when no data is returned
    $priorityCounts = [
        'low' => 0,
        'medium' => 0,
        'high' => 0
    ];
}
?>
<style>
    a {
        text-decoration: none; 
        color: black; 
    }
    a:hover {
        text-decoration: none; 
        color: black; 
    }
    .row-container {
        display: flex;
        justify-content: space-evenly; 
        align-items: stretch;
        margin-bottom: 0;
    }
    .box-container {
        display: flex;
        flex-wrap: wrap; 
        justify-content: space-evenly; 
        width: 90%; 
        margin: 0; 
        padding: 0; 
    }
    .box {
        width: 48%;
        margin-right: 1%; 
        padding: 0; 
        display: flex;
        flex-direction: column;
        justify-content: center;
        height: 150px;
    }
    .chart-column1 {
        display: flex;
        flex-direction: column;
        align-items: center; 
        width: 90%;
    }
    .chart-container1 {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width:80%; 
        padding: 15px; 
        margin-bottom: 10px; 
    }
    .card-body1 {
        display: flex;
        flex-direction: column;
        align-items: center; 
        justify-content: center;
        width: 100%;
        height: 100%;
    }
    .card-title1 {
        margin-bottom: 10px; 
        text-align: center; 
    }
    .canvas1 {
        width: 100%;
        height: 100%;
        max-width: 500px;
        max-height: 500px;
    }
   
    .box:not(:last-child) {
        margin-bottom: 0;
    }
    @media (max-width: 768px) {
        .box-container, .chart-column {
            width: 100%;
        }
        .box {
            width: 100%; 
        }
        .chart-container {
            width: 100%;
            height: auto;
        }
    }
    .box1:hover {
        background-color: rgba(13, 146, 244, 1);
    }
    .box2:hover {
        background-color: rgba(255, 230, 0, 1);
    }
    .box3:hover {
        background-color: rgba(0, 170, 0, 1);
    }
    .box4:hover {
        background-color: rgba(220, 0, 0, 1);
    }
    .box5:hover {
        background-color: rgba(210, 224, 251, 1);
    }
    .box6:hover {
        background-color: rgba(236, 236, 244, 1);
    }
</style>


<div class="container-fluid">
    <div class="row mt-4">
        <div class="col-md-6 mb-4">
            <br><br>
            <form method="GET" action="">
                <div class="input-group">
                    <!-- This filter is about status, start, and end date. The user fill this options and the filter will works accourding to them -->
                    <select name="status" class="form-select" aria-label="Filter by status"  style= "margin-right: 1%">
                        <option value="">All Statuses</option>
                        <option value="open" <?php echo (isset($_GET['status']) && $_GET['status'] == 'open') ? 'selected' : ''; ?>>Open</option>
                        <option value="solved" <?php echo (isset($_GET['status']) && $_GET['status'] == 'solved') ? 'selected' : ''; ?>>Solved</option>
                        <option value="closed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'closed') ? 'selected' : ''; ?>>Closed</option>
                        <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                    </select>
                    <input type="date" name="start_date" class="form-control" placeholder="Start Date" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
                    <input type="date" name="end_date" class="form-control" placeholder="End Date" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>" style= "margin-right: 1%">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </div>
            </form>
        </div>
        <!-- Php echo will follow the num of counts that we take them from tables -->
         <div class="col-md-3 mb-6" style="margin-left: 1%">
    <div class="card shadow-sm" style="max-width: 80%; background-color:#343a40;">
        <div class="card-body text-center" style="color: white;"> <!-- Set text color to white here -->
            <a href="./all-ticket-records" style="color: white; text-decoration: none;"> <!-- Set link color to white and remove underline -->
                <h5 class="card-title mb-0"><i class="bi bi-ticket" style="color: white;"></i> Overall Tickets</h5>
                <p class="display-4 mb-0"><?php echo $tickets; ?></p>
            </a>
        </div>
    </div>
</div>

            <div class="card" style="border-style:none; margin-top: 3%; margin-left: 2%">
                <img src="BiTS.Logo.png"  width="180" height="50">
            </div>
    </div>

<div class="container-fluid">
    <div class="row-container mt-4">
        <div class="box-container">
            <div class="box card shadow-sm"><div class="box1">
                <div class="card-body text-center">
                    <a href="./view-open-tickets">
                        <h5 class="card-title"><i class="bi bi-ticket-perforated"></i> Open Tickets</h5>
                        <p class="card-text display-4"><?php echo $openTickets; ?></p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="box card shadow-sm"><div class="box2">
                <div class="card-body text-center">
                    <a href="./pending-ticket-list">
                        <h5 class="card-title"><i class="bi bi-ticket-perforated-fill"></i> Pending Tickets</h5>
                        <p class="card-text display-4"><?php echo $pendingTickets; ?></p>
                    </a>
                </div>
            </div>
            </div>
            <!-- Third Box -->
            <div class="box card shadow-sm"><div class="box3">
                <div class="card-body text-center">
                    <a href="./solved-tickets">
                        <h5 class="card-title"><i class="bi bi-ticket-fill"></i> Solved Tickets</h5>
                        <p class="card-text display-4"><?php echo $solvedTickets; ?></p>
                    </a>
                </div>
            </div>
             </div>
            <div class="box card shadow-sm"><div class="box4">
                <div class="card-body text-center">
                    <a href="./view-unsolved-tickets">
                        <h5 class="card-title"><i class="bi bi-ticket-detailed-fill"></i> Unsolved Tickets</h5>
                        <p class="card-text display-4"><?php echo $closedTickets; ?></p>
                    </a>
                </div>
            </div>
             </div>
            <!-- Fifth Box -->
            <div class="box card shadow-sm"><div class="box5">
                <div class="card-body text-center">
                    <a href="./unassigned-tickets">
                        <h5 class="card-title"><i class="bi bi-ticket-detailed"></i> Unassigned Tickets</h5>
                        <p class="card-text display-4"><?php echo $unassignedTickets; ?></p>
                    </a>
                </div>
            </div>
             </div>
            <div class="box card shadow-sm"><div class="box6">
                <div class="card-body text-center">
                    <a href="./team-dashboard">
                        <h5 class="card-title"><i class="bi bi-people-fill"></i> Teams </h5>
                        <p class="card-text display-4"><?php echo  $team;?></p>
                    </a>
                </div>
            </div>
             </div>
            <div class="box card shadow-sm"><div class="box6">
                <div class="card-body text-center">
                    <a href="./admin-dashboard">
                        <h5 class="card-title"><i class="bi bi-person-fill-gear"></i> Admins</h5>
                        <p class="card-text display-4"><?php echo $adminusers; ?></p>
                    </a>
                </div>
            </div>
             </div>
            <div class="box card shadow-sm"><div class="box6">
                <div class="card-body text-center">
                    <a href="./user-management">
                        <h5 class="card-title"><i class="bi bi-person"></i> Champions </h5>
                        <p class="card-text display-4"><?php echo $users; ?></p>
                    </a>
                </div>
            </div>
            </div>
            <div class="box card shadow-sm"><div class="box6">
                <div class="card-body text-center">
                    <a href="./manager-dashboard">
                        <h5 class="card-title"><i class="bi bi-person"></i> Managers </h5>
                        <p class="card-text display-4"><?php echo $managers; ?></p>
                    </a>
                </div>
            </div>
            </div>
            <div class="box card shadow-sm"><div class="box6">
                <div class="card-body text-center">
                    <a href="./client-request">
                        <h5 class="card-title"><i class="bi bi-person"></i> Requesters </h5>
                        <p class="card-text display-4"><?php echo $requesters; ?></p>
                    </a>
                </div>
            </div>
            </div>
        </div>
       <div class="chart-column1">
           <div class="chart-container1 card " style="border-style:none">
        <div class="card-body1">
            <h5 class="card-title1">Tickets Status</h5>
            <canvas class="canvas1" id="ticketCircle"></canvas>
        </div>
    </div>

    <div class="chart-container1 card " style="border-style:none">
        <div class="card-body1">
            <h5 class="card-title1">Priority Tickets</h5>
            <canvas class="canvas1" id="priorityChart"></canvas>
        </div>
    </div>
</div>

    </div>
</div>

<div class="container-fluid">
    <div class="row mt-4">
        <div class="col-md-12 col-lg-10 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Tickets Per Teams</h5>
                    <div class="chart-container" >
                        <canvas id="ticketChart" style="position: relative; height:50vh; width:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="container-fluid"  style="margin-bottom: 10%;">
    <div class="row mt-4">
        <div class="col-md-12 col-lg-10 mb-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Tickets Status Per Teams</h5>
                    <div class="chart-container">
                        <canvas id="ticketChart1" style="position: relative; height:50vh; width:100%;"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>

//In java Script we make a chart and we put link chart.js in header.php to works properly. Surely we use php echo for each one to make a chart and we use colors to We differentiate between them Except php echo different than the other cause each chart has a specific echo


var ctx = document.getElementById('ticketCircle').getContext('2d');
var totalTickets = <?php echo $tickets; ?>;
var ticketChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Open Tickets', 'Solved Tickets', 'Closed Tickets', 'Pending Tickets', 'Unassigned Tickets'],
        datasets: [{
            label: 'Number of Tickets',
            data: [
                <?php echo $openTickets; ?>,
                <?php echo $solvedTickets; ?>,
                <?php echo $closedTickets; ?>,
                <?php echo $pendingTickets; ?>,
                <?php echo $unassignedTickets; ?>
            ],
            backgroundColor: [
                'rgba(13, 146, 244, 1)',
                'rgba(0, 170, 0, 1)',
                'rgba(220, 0, 0, 1)',
                'rgba(255, 230, 0, 1)',
                'rgba(210, 224, 251, 1)'
            ],
            borderColor: [
                'rgba(13, 146, 244, 1)',
                'rgba(0, 170, 0, 1)',
                'rgba(220, 0, 0, 1)',
                'rgba(255, 230, 0, 1)',
                'rgba(210, 224, 251, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        cutout: '50%',
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'left'
            },
            tooltip: {
                enabled: true
            },
        }
    },
});

var ctxBar = document.getElementById('priorityChart').getContext('2d');
var barChart = new Chart(ctxBar, {
    type: 'doughnut',
    data: {
        labels: ['Low', 'Medium', 'High'], // Labels for each priority
        datasets: [
            {
                label: 'Number of Tickets by Priority', // General label for the entire dataset
                data: [
                    <?php echo json_encode($priorityCounts['low']); ?>, 
                    <?php echo json_encode($priorityCounts['medium']); ?>, 
                    <?php echo json_encode($priorityCounts['high']); ?>
                ], // One array with all priority counts
                backgroundColor: [
                    'rgba(0, 170, 0, 1)',  // Green for Low
                    'rgba(255, 230, 0, 1)', // Yellow for Medium
                    'rgba(220, 0, 0, 1)'    // Red for High
                ], // Color array for each bar
                borderColor: [
                    'rgba(0, 170, 0, 1)',  // Border color for Low
                    'rgba(255, 230, 0, 1)', // Border color for Medium
                    'rgba(220, 0, 0, 1)'    // Border color for High
                ],
                borderWidth: 1
            }
        ]
    },
   options: {
        cutout: '50%',
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'left'
            },
            tooltip: {
                enabled: true
            },
        }
    },
});

var ctxBar1 = document.getElementById('ticketChart').getContext('2d');
var ticketBarChart = new Chart(ctxBar1, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($teams1); ?>,
        datasets: [{
            label: 'Tickets Per Team',
            data: <?php echo json_encode($ticketCounts1); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, 
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

var ctxBar2 = document.getElementById('ticketChart1').getContext('2d');
var ticketBarChart1 = new Chart(ctxBar2, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($teams); ?>,
       datasets: [
            {
                label: 'Open Tickets',
                data: <?php echo json_encode($openCounts); ?>,
                backgroundColor: 'rgba(13, 146, 244, 1)',
                borderColor: 'rgba(13, 146, 244, 1)',
                borderWidth: 1
            },
            {
                label: 'Solved Tickets',
                data: <?php echo json_encode($solvedCounts); ?>,
                backgroundColor: 'rgba(0, 170, 0, 1)',
                borderColor: 'rgba(0, 170, 0, 1)',
                borderWidth: 1
            },
            {
                label: 'Closed Tickets',
                data: <?php echo json_encode($closedCounts); ?>,
                backgroundColor: 'rgba(220, 0, 0, 1)',
                borderColor: 'rgba(220, 0, 0, 1)',
                borderWidth: 1
            },
            {
                label: 'Pending Tickets',
                data: <?php echo json_encode($pendingCounts); ?>,
                backgroundColor: 'rgba(255, 230, 0, 1)',
                borderColor: 'rgba(255, 230, 0, 1)',
                borderWidth: 1
            },
            {
             label: 'Unassigned Tickets',
                data: <?php echo json_encode($unassignedCounts); ?>,
                backgroundColor: 'rgba(210, 224, 251, 1)',
                borderColor: 'rgba(210, 224, 251, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false, 
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

</script>

<?php 
ob_end_flush();
include './footer.php';
?> 