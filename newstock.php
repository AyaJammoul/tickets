<?php
ob_start();
include './header.php';
require_once './src/stock.php';
require './src/helper-functions.php';

$err = '';
$msg = '';

if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $t_quantity = $_POST['t_quantity'];
    $stock = $_POST['stock'];
    $rack_number = $_POST['rack_number'];
    $sub_rack = $_POST['sub_rack'];

    if (strlen($name) < 1) {
        $err = "Please enter stock name";
    } else if (strlen($t_quantity) < 1) {
        $err = "Please enter quantity";
    } else if (strlen($stock) < 1) {
        $err = "Please enter stock status";
    } else if (strlen($rack_number) < 1) {
        $err = "Please enter rack number";
    } else if (strlen($sub_rack) < 1) {
        $err = "Please enter sub rack";
    } else {
        try {
            $stock = new Stock([
                'name' => $name,
                'brand' => $brand,
                't_quantity' => $t_quantity,
                'stock' => $stock,
                'rack_number' => $rack_number,
                'sub_rack' => $sub_rack
            ]);

            $stock->save();
            $msg = "Stock created successfully";
        } catch (Exception $e) {
            $err = "Unable to create stock: " . $e->getMessage();
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
            <li class="breadcrumb-item active">Create New Stock</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a New Stock</h3>
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
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Name <span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="name" class="form-control" id="name" placeholder="Enter stock name" required>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="brand" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Brand </label>
                        <div class="col-sm-8">
                            <input type="text" name="brand" class="form-control" id="brand" placeholder="Enter brand">
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="t_quantity" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Total Quantity <span class="required">*</span></label>
                        <div class="col-sm-8">
                           <input type="text" name="t_quantity" class="form-control" id="t_quantity" placeholder="Enter total quantity" required>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="stock" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Stock Status <span class="required">*</span></label>
                        <div class="col-sm-8">
                            <input type="text" name="stock" class="form-control" id="stock" placeholder="Enter stock number" required>
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="rack_number" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Rack Number </label>
                        <div class="col-sm-8">
                            <input type="text" name="rack_number" class="form-control" id="rack_number" placeholder="Enter rack number">
                        </div>
                    </div>
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="sub_rack" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Sub Rack </label>
                        <div class="col-sm-8">
                            <input type="text" name="sub_rack" class="form-control" id="sub_rack" placeholder="Enter sub rack" >
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
include './footer.php' ?>
