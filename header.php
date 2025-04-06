<?php
ob_start(); // Start output buffering
session_start(); // Start the session
$link = 'index.php';

if (empty($_SESSION['user_id'])) {
    // Redirect to the sign-in page if the user is not authenticated
    header("Location: ./sign_in");
    exit; // Stop further script execution after the redirect
}
// Check if the user is logged in and set the appropriate navigation link based on their role
if (isset($_SESSION['logged-in']) && $_SESSION['logged-in'] === true) {
    if (isset($_SESSION['user_role'])) {
        $role = $_SESSION['user_role'];
        // Set link based on role
        if ($role === 'admin') {
            $link = 'mydash-board';
        } elseif ($role === 'member') {
            $link = 'view-open-tickets';
        } elseif ($role === 'manager') {
            $link = 'all-ticket-manager';
        }
    }
} else {
    $link = 'sign_in'; // Redirect to login if not logged in
}

require_once './src/Database.php';

if (isset($_SESSION['logged-in']) && $_SESSION['logged-in'] === true) {
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];  // Use user_id from session
        $user_role = $_SESSION['user_role'];
        $db = Database::getInstance();

        // Prepare query based on user role
        if ($user_role === 'admin') {
            $stmt = $db->prepare("SELECT * FROM admin WHERE id = ?");
        } elseif ($user_role === 'manager') {
            $stmt = $db->prepare("SELECT * FROM manager WHERE id = ?");
        } elseif ($user_role === 'member') {
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        } else {
            $user = null; // Ensure $user is null for invalid roles
            echo "Invalid role specified.";
        }

        if (isset($stmt)) {
            $stmt->bind_param("s", $user_id);  // Bind the user_id parameter
            $stmt->execute();
            $user = $stmt->get_result()->fetch_object();
            $stmt->close();
        }
    } else {
        // Redirect to login if no user session found
        header("Location: sign_in");
        exit();
    }
}
$branchId = $_SESSION['branch_id'];
ob_end_flush(); // End output buffering
?>
 
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">
  <title>Helpdesk - Dashboard</title>

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <!-- Page level plugin CSS-->
  <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
  <!-- Custom styles for this template-->
  <link href="css/sb-admin.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  
  <style>
      .navbar .notification {
        display: flex;
        align-items: center;
        justify-content: center;
        color:white;
        margin: 0 15px; 
      }
      .sidebar .nav-item .nav-link:hover,
      .sidebar .nav-item .nav-link:hover i {
          color: #007bff;
      }
      .sidebar .nav-item.active > .nav-link > i#sub1-caret {
          transition: transform 0.3s ease, color 0.3s ease;
      }
      #sub1 .nav-link:hover {
          color: #007bff;
      }
      #sub1 .nav-link i {
          color: inherit;
      }
      .count{
         color: black;
         background-color: yellow; /* Change this to the color you want */
         font-size: 10px; /* Example size, adjust as needed */
         padding: 2px 4px; /* Adjust padding for better spacing */
         border-radius: 12px;
      }
      
  </style>
</head>
<?php
// Assuming $db connection is already established
$waitingTicketCountUser = 0;
$waitingTicketCountManager = 0;
$user_role = 'admin'; // Set the role appropriately, or fetch it from session or database.

// Fetch the count of waiting tickets for Tickets User
if ($user_role === 'admin') {
    $stmt = $db->prepare("SELECT COUNT(*) as ticket_count FROM ticketuser WHERE status = 'waiting' AND branch_id = $branchId");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $waitingTicketCountUser = $row['ticket_count'];
        }
        $stmt->close();
    }
}

// Fetch the count of waiting tickets for Tickets Manager
if ($user_role === 'admin') {
    $stmt = $db->prepare("SELECT COUNT(*) as ticket_count FROM ticketmanager WHERE status = 'waiting' AND branch_id = $branchId");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $waitingTicketCountManager = $row['ticket_count'];
        }
        $stmt->close();
    }
}
?>

<script>
    // Function to collect span text values and send them to the server
    function checkAndUpdateSpans() {
        // Collect all span text content
        let spans = document.querySelectorAll('span');
        let terms = [];
        
        spans.forEach(span => {
            terms.push(span.innerText.trim());
        });

        // Send the terms array to the server using AJAX (Fetch API)
        fetch('process_terms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ terms: terms }),
        })
        .then(response => response.json())
        .then(data => {
            // Update the spans with the data returned from the server
            data.updated_terms.forEach((updated_term, index) => {
                spans[index].innerText = updated_term; // Replace span text with updated term
            });
        })
        .catch(error => console.error('Error:', error));
    }
    
    // Call the function on page load or when you need to trigger the update
    window.onload = checkAndUpdateSpans;
</script>

<body id="page-top">

<nav class="navbar navbar-expand navbar-dark bg-dark static-top">
   <a class="navbar-brand mr-1" href="<?php echo $link; ?>">Helpdesk Ticketing System</a>
    <button class="btn btn-link btn-sm text-white order-1 order-sm-0" id="sidebarToggle" href="#">
        <i class="fas fa-bars"></i>
    </button>

    <div class="ml-auto"></div>
    
    <a class="navbar-brand mr-1" href="https://bitsleb.com">BiT<sup>2</sup>S</a>
    
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-user-circle fa-fw"></i> <?php echo htmlspecialchars($user->name); ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="./my-profile">My Profile</a>
                <a class="dropdown-item" href="./sign-out" data-toggle="modal" data-target="#logoutModal">Logout</a>
            </div>
        </li>
        <?php if ($user && $user->role == 'admin'): ?>
        <li class="nav-item notification mx-auto" onclick="window.open('https://accounts.google.com/AccountChooser?Email=helpdeskbitss@gmail.com', '_blank');">
            <i class="bi bi-bell" style="font-size: 1.2rem; color: white;"></i>
            <span class="badge" id="notificationCount" style="display: none;"></span>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<div id="wrapper">
  <ul class="sidebar navbar-nav">
    <?php if ($user && $user->role == 'admin'): ?>
    <li class="nav-item active">
        <a class="nav-link" href="./mydash-board">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span> Dashboard</span>
        </a>
    </li>
    <li class="nav-item active">
        <a class="nav-link" href="./create-new-ticket">
            <i class="fa fa-plus"></i>
            <span> New tickets</span>
        </a>
    </li>
<li class="nav-item active">
    <a class="nav-link" href="./all-ticket-manager_a">
        <i class="bi bi-file-earmark-plus-fill"></i>
        <span id="ticketsManager"> Tickets Manager</span>
            <?php if ($waitingTicketCountManager > 0): ?>
        <i class="count" id="ticketCountManager"><?php echo $waitingTicketCountManager; ?></i>
    <?php endif; ?>
    </a>
</li>

    
    <li class="nav-item active">
    <a class="nav-link" href="./all-ticket-user_a">
        <i class="bi bi-file-earmark-plus"></i>
        <span id="ticketsUser"> Tickets User</span>
             <?php if ($waitingTicketCountUser > 0): ?>
        <i class="count" id="ticketCountUser"><?php echo $waitingTicketCountUser; ?></i>
    <?php endif; ?>
    </a>
</li>

    <?php endif; ?>
    <?php if ($user && $user->role == 'manager'): ?>
    <li class="nav-item active">
        <a class="nav-link" href="./create-new-ticket-of-manager">
            <i class="fa fa-plus"></i>
            <span> New tickets</span>
        </a>
    </li>
     <li class="nav-item active">
        <a class="nav-link" href="./all-ticket-manager">
             <i class="bi bi-ticket"></i>
            <span>All ticket</span>
        </a>
    </li>
    <?php endif; ?>
    <?php if ($user && $user->role == 'admin'): ?>
    <li class="nav-item active">
        <a class="nav-link" href="#" data-toggle="collapse" data-target="#sub2">
            <span>Manage Tickets</span>
            <i class="bi bi-caret-right-fill" id="sub2-caret"></i>
        </a>
        <ul class="nav collapse" id="sub2">
            <?php endif; ?>
            <?php if ($user && $user->role == 'admin' || $user && $user->role == 'member'): ?>
            <li class="nav-item active">
                <a class="nav-link" href="./view-open-tickets">
                    <i class="fas fa-fw fa-lock-open"></i>
                    <span> Open</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="./pending-ticket-list">
                    <i class="fa fa-fw fa-adjust"></i>
                    <span> Pending</span>
                </a>
            </li>
            <?php if ($user && $user->role == 'member'): ?>
            <li class="nav-item active">
                <a class="nav-link" href="./create-new-ticket-of-user">
                    <i class="fa fa-plus"></i>
                    <span> New tickets</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="./all-ticket-user">
                    <i class="bi bi-ticket"></i>
                    <span>Edit ticket</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if ($user && $user->role == 'admin'): ?>
            <li class="nav-item active">
                <a class="nav-link" href="./unassigned-tickets">
                    <i class="fa fa-fw fa-at"></i>
                    <span> Unassigned</span>
                </a>
            </li>
            <?php endif; ?>
        <li class="nav-item active">
        <a class="nav-link" href="./solved-tickets">
            <i class="fa fa-fw fa-anchor"></i>
            <span> Solved</span>
        </a>
    </li>
    <li class="nav-item active">
        <a class="nav-link" href="./view-unsolved-tickets">
            <i class="fa fa-fw fa-times-circle"></i>
            <span> Unsolved</span>
        </a>
    </li>
    <li class="nav-item active">
        <a class="nav-link" href="./all-ticket-records">
            <i class="bi bi-ticket"></i>
            <span> All tickets</span>
        </a>
    </li>
    <?php endif; ?>
    <?php if ($user && $user->role == 'member'): ?>
    <li class="nav-item active">
        <a class="nav-link" href="./user-tickets">
            <i class="fa fa-fw fa-award"></i>
            <span> My tickets</span>
        </a>
    </li>
    <?php endif; ?>
    </ul></li>
    <?php if ($user && $user->role == 'admin'): ?>
    <li class="nav-item active">
        <a class="nav-link" href="#" data-toggle="collapse" data-target="#sub1">
            <span>Manage Members</span>
            <i class="bi bi-caret-right-fill" id="sub1-caret"></i>
        </a>
        <ul class="nav collapse" id="sub1">
            <li class="nav-item active">
                <a class="nav-link" href="./team-dashboard">
                    <i class="fa fa-fw fa-users"></i>
                    <span> Teams</span>
                </a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="./admin-dashboard">
                    <i class="bi bi-person-fill-gear"></i>
                    <span> Admins</span>
                </a>
            </li>
            <li class="nav-item active">
          <a class="nav-link" href="./manager-dashboard">
              <i class="bi bi-person-fill"></i>
              <span>Manager</span>
          </a>
      </li>
      <li class="nav-item active">
          <a class="nav-link" href="./champions-management">
              <i class="fa fa-fw fa-users"></i>
              <span> Champions</span>
          </a>
      </li>
       <li class="nav-item active">
          <a class="nav-link" href="./client-request">
              <i class="bi bi-people-fill"></i>
              <span> Requester</span>
          </a>
      </li>
        </ul>
    </li>
   <li class="nav-item active">
        <a class="nav-link" href="./branch-details">
         <i class="bi bi-buildings-fill"></i>
          <span> Branch</span>
        </a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="./building-details">
         <i class="bi bi-building-fill"></i>
          <span> Building</span>
        </a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="./stock-details">
         <i class="bi bi-boxes"></i>
          <span> Stock</span>
        </a>
      </li>
      <li class="nav-item active">
        <a class="nav-link" href="./terms-details">
         <i class="bi bi-journals"></i>
          <span> Terms</span>
        </a>
      </li>
    <?php endif; ?>  
    </ul>
 