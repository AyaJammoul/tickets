<?php
ob_start();
include './header.php';
require_once './src/building.php';
require './src/helper-functions.php';

$err = '';
$msg = '';

if (isset($_SESSION['branch_id'])) {
    $branchId = $_SESSION['branch_id'];

    // Fetch managers from the database
    try {
        $db = Database::getInstance();
        $managerQuery = $db->query("SELECT id, name FROM manager");
        $managers = [];
        while ($row = $managerQuery->fetch_assoc()) {
            $managers[] = $row;
        }
    } catch (Exception $e) {
        $err = "Error fetching managers: " . $e->getMessage();
    }

    if (isset($_POST['submit'])) {
        $manager_id = $_POST['manager_id'];
        $building = $_POST['building'];
        $door_code = $_POST['door_code'];

        if (empty($manager_id)) {
            $err = "Please select a manager";
        } elseif (empty($building)) {
            $err = "Please enter building";
        } elseif (empty($door_code)) {
            $err = "Please enter door code";
        } else {
            try {
                $building = new building([
                    'manager_id' => $manager_id,
                    'branch_id' => $branchId,
                    'building' => $building,
                    'door_code' => $door_code,
                ]);

                $building->save();
                $msg = "Building created successfully";

            } catch (Exception $e) {
                $err = "Unable to create building: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
    .required {
        color: red;
    }
</style>

<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Create Building</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a New Building</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($err)) : ?>
                    <div class="alert alert-danger text-center my-3" role="alert">
                        <strong>Failed! </strong> <?php echo $err; ?>
                    </div>
                <?php endif ?>

                <?php if (!empty($msg)) : ?>
                    <div class="alert alert-success text-center my-3" role="alert">
                        <strong>Success! </strong> <?php echo $msg; ?>
                    </div>
                <?php endif ?>

                <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="manager_id" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Manager <span class="required">*</span></label>
                        <div class="col-sm-8">
                            <select name="manager_id" class="form-control" id="manager_id" required>
                                <option value="">Select manager</option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?php echo $manager['id']; ?>"><?php echo $manager['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="building" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Building <span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="building" class="form-control" id="building" placeholder="Enter building name" required>
                        </div>
                    </div>

                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="door_code" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Door Code <span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="door_code" class="form-control" id="door_code" placeholder="Enter door code" required>
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

<?php ob_end_flush();
include './footer.php' ?>
