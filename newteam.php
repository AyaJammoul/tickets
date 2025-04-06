<?php
  ob_start();
  //Adding new team
  
  include './header.php';
 
  require_once './src/team.php';
  require './src/helper-functions.php';

  $err = '';
  $msg = '';



  if(isset($_POST['submit'])){
    
      $name = $_POST['name'];
  

      if(strlen($name) < 1) {
          $err = "Please enter team name";
     
     
      } else {
        
        try{
           

            $team = new Team([
                'name' => $name
               
            ]);
            $team->save();

            $msg = "Team generated successfully";
        } catch(Exception $e){
            $err = "Failed to generate team";
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
                <a href="#">Members</a>
            </li>
            <li class="breadcrumb-item active">New Member</li>
        </ol>

        <div class="card mb-3">
            <div class="card-header">
                <h3>Create a new member</h3>
            </div>
            <div class="card-body">
                <?php if(strlen($err) > 1) :?>
                <div class="alert alert-danger text-center my-3" role="alert"> <strong>Failed! </strong> <?php echo $err;?></div>
                <?php endif?>

                <?php if(strlen($msg) > 1) :?>
                <div class="alert alert-success text-center my-3" role="alert"> <strong>Success! </strong> <?php echo $msg;?></div>
                <?php endif?>

                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']?>">
                    <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                        <label for="name" class="col-sm-12 col-lg-2 col-md-2 col-form-label">Name <span class="required">*</span></label></label>
                        <div class="col-sm-8">
                            <input type="text" name="name" class="form-control" id="" placeholder="Enter name">
                        </div>
                    </div>
                   
                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-lg btn-primary"> Create</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
    <!-- /.container-fluid -->
<?php
ob_end_flush();
include './footer.php';
?>
   