<?php
ob_start();
include './header.php';

if (isset($_SESSION['branch_id'])) {
    $branchId = $_SESSION['branch_id'];
    
    // Check if the team ID is valid
    if (!isset($_GET['team-id']) || strlen($_GET['team-id']) < 1 || !ctype_digit($_GET['team-id'])) {
        echo '<script>history.back()</script>';
        exit();
    }

    require_once './src/user.php';
    require_once './src/team-member.php';

    // Retrieve all users for the selected branch
    $users = new User();
    $allusers = $users::finduser($branchId);

    $err = '';
    $msg = '';

  if (isset($_POST['submit'])) {
    $user = $_POST['id'];
    $teamid = $_GET['team-id'];

    if ($user == 'none') {
        $err = "Please select a user";
    } elseif (empty($user)) {
        $err = "User ID is required";
    } else {
        try {
            // Create a new TeamMember instance and include branch_id from session
            $team_mem = new TeamMember([
                'id' => $user,
                'team-id' => $teamid,
                'branch_id' => $branchId
            ]);

            // Save the new team member
            $saveteam = $team_mem->save();
            $msg = "Member added successfully";
        } catch (Exception $e) {
            $err = "Failed to add member: " . $e->getMessage();
        }
    }
}
}
ob_end_flush();
?>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Add New Champion</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a new champion</h3>
            </div>
            <div class="card-body">
                <?php if (strlen($err) > 1) : ?>
                    <div class="alert alert-danger text-center my-3" role="alert">
                        <strong>Failed! </strong> <?php echo $err; ?>
                    </div>
                <?php endif ?>

                <?php if (strlen($msg) > 1) : ?>
                    <div class="alert alert-success text-center my-3" role="alert">
                        <strong>Success! </strong> <?php echo $msg; ?>
                    </div>
                <?php endif ?>

                <form method="POST" action="">
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Champion</label>
                        <div class="col-sm-8">
                            <select name="id" class="form-control">
                                <option value="none">--select--</option>
                                <?php foreach ($allusers as $user) : ?>
                                    <option value="<?php echo $user->id; ?>"> <?php echo $user->name; ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-lg btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include './footer.php';
?>

