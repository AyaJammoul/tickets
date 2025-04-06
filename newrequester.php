<?php
ob_start();
  //Adding new requester

  include './header.php';

  require_once './src/requester.php';
  require './src/helper-functions.php';

  $err = '';
  $msg = '';
if (isset($_SESSION['branch_id'])) {
$branchId = $_SESSION['branch_id'] ; 

  if (isset($_POST['submit'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    if (strlen($name) < 1) {
        $err = "Please enter requester name";
    } else if (strlen($email) < 1) {
        $err = "Please enter email";
    } else if (!isValidEmail($email)) {
        $err = "Please enter a valid email";
    } else if (strlen($phone) < 1) {
        $err = "Please enter phone number";
    } else if (!isValidPhone($phone)) {
        $err = "Please enter a valid phone number";
    }else {

        try {
            $requester = new Requester([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'branch_id' => $branchId
            ]);

            $requester->save();
            $msg = "Requester created successfully";

        } catch (Exception $e) {
            $err = "Unable to create requester: " . $e->getMessage();
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
            <li class="breadcrumb-item active">Create Requester</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a New Requester</h3>
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

                <form method="POST" action="<?php echo $_SERVER['PHP_SELF'] ?>">
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Name <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="text" name="name" class="form-control" id="name" placeholder="Enter name" required>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="email" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Email <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="email" name="email" class="form-control" id="email" placeholder="Enter email" required>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="phone" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Phone <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="tel" name="phone" class="form-control" id="phone" placeholder="Enter phone number" required>
        </div>
    </div>
    <div class="text-center">
        <button type="submit" name="submit" class="btn btn-lg btn-primary">Create</button>
    </div>
</form>

            </div>
        </div>
    </div>

    <?php ob_end_flush();
    include './footer.php'?>
