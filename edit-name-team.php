<?php
ob_start();
require_once './src/team.php'; 
include './header.php';

$msg = ''; // Initialize message variable

//This php code is to change the name of the team according to the id. By the id we check in the  table the name of the team, then when the admin post, the name of the team will change according to id

if (!isset($_GET['team-id']) || strlen($_GET['team-id']) < 1 || !ctype_digit($_GET['team-id'])) {
    echo '<script> history.back(); </script>';
    exit();
}

$teamId = $_GET['team-id'];
$teamData = Team::findById($teamId); 
$currentTeamName = $teamData['name'] ?? 'Unknown Team';


// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_name']) && !empty(trim($_POST['team_name']))) {
    $newTeamName = trim($_POST['team_name']);
    
    // Call a method to update the team name in the database
    if (Team::updateName($teamId, $newTeamName)) {
        $msg = "Team name updated successfully."; // Set success message
        header("Location: ./edit-team-name?team-id=" . urlencode($teamId) . "&msg=" . urlencode($msg)); 
        exit();
    } else {
        echo "Error: Failed to update the team name.";
    }
}

// Check if there is a message in the URL to display
if (isset($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg']); // Get message from URL
}
?>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Edit Team Name</li>
        </ol>

        <?php if (!empty($msg)): ?> <!-- Display success message if set -->
            <div class="alert alert-success text-center my-3" role="alert">
                <strong>Success!</strong> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Edit Team: <?php echo htmlspecialchars($currentTeamName); ?></h3> <!-- Display name team -->
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="team_name">New Team Name:</label>
                        <input type="text" class="form-control" id="team_name" name="team_name" value="<?php echo htmlspecialchars($currentTeamName); ?>" required> <!-- Display name team -->
                    </div>
                    <button type="submit" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
include './footer.php';
?>
