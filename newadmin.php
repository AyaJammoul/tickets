<?php
ob_start();
//Here we will add new admin

  include './header.php';

  require_once './src/admin.php';
  require './src/helper-functions.php';

  $err = '';
  $msg = '';
if (isset($_SESSION['branch_id'])) {
$branchId = $_SESSION['branch_id'] ; 

  if (isset($_POST['submit'])) {

    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_pass = $_POST['confirm-password'];
    $department = $_POST['department'];
    $job_title = $_POST['job-title'];
    $phone_extension = $_POST['phone-extension'];
    $location = $_POST['location'];
    $preferred_language = $_POST['preferred-language'];

    if (strlen($name) < 1) {
        $err = "Please enter admin name";
    } else if (strlen($email) < 1) {
        $err = "Please enter email";
    } else if (!isValidEmail($email)) {
        $err = "Please enter a valid email";
    } else if (strlen($phone) < 1) {
        $err = "Please enter phone number";
    } else if (!isValidPhone($phone)) {
        $err = "Please enter a valid phone number";
    } else if (strlen($password) < 1) {
        $err = "Please enter a password";
    } else if (strlen($password) < 8) {
        $err = "Password should be at least 8 characters";
    } else if ($password != $confirm_pass) {
        $err = "Passwords do not match";
    } else if (strlen($department) < 1) {
        $err = "Please select a department";
    } else if (strlen($job_title) < 1) {
        $err = "Please enter job title";
    } else if (strlen($phone_extension) < 1) {
        $err = "Please enter phone extension";
    } else if (strlen($location) < 1) {
        $err = "Please enter location";
    } else if (strlen($preferred_language) < 1) {
        $err = "Please select a preferred language";
    } else {

        try {
            $admin = new Admin([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'branch_id' => $branchId,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
                'department' => $department,
                'job_title' => $job_title,
                'phone_extension' => $phone_extension,
                'location' => $location,
                'preferred_language' => $preferred_language
            ]);

            $admin->save();
            $msg = "Admin created successfully";

        } catch (Exception $e) {
            $err = "Unable to create admin: " . $e->getMessage();
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
            <li class="breadcrumb-item active">Create Admin</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a New Admin</h3>
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
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="department" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Department <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <select name="department" class="form-control" id="department" required>
                <option value="">Select department</option>
                <option value="KG">KG</option>
                <option value="Elementary">Elementary</option>
                <option value="Girl's Section">Girl's Section</option>
                <option value="Boy's Section">Boy's Section</option>
                <option value="Administration">Administration</option>
            </select>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="job-title" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Job Title <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="text" name="job-title" class="form-control" id="job-title" placeholder="Enter job title" required>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="phone-extension" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Phone Extension <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="text" name="phone-extension" class="form-control" id="phone-extension" placeholder="Enter phone extension" required>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="location" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Location <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="text" name="location" class="form-control" id="location" placeholder="Enter location" required>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="preferred-language" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Preferred Language <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <select name="preferred-language" class="form-control" id="preferred-language" required>
                <option value="">Select language</option>
                <option value="Arabic">Arabic</option>
                <option value="English">English</option>
                <option value="Urdu">Urdu</option>
                <option value="French">French</option>
                <option value="Hindi">Hindi</option>
            </select>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="password" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Password <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="password" name="password" class="form-control" id="password" placeholder="Enter password" required>
        </div>
    </div>
    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
        <label for="confirm-password" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Confirm Password <span class="required">*</span></label></label>
        <div class="col-sm-8">
            <input type="password" name="confirm-password" class="form-control" id="confirm-password" placeholder="Enter confirm password" required>
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
