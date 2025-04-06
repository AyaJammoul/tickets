<?php
ob_start();
include './header.php';
require_once './src/user.php';

if (isset($_SESSION['branch_id'])) {
   
$branchId = $_SESSION['branch_id'] ;
//We check all users we have in database
$users = User::finduser($branchId);
}
?>
<style>
        /* Overlay background */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        /* Modal container */
        .modal-container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }

        /* Close button */
        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: transparent;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
    <script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[1, "asc"]] // Orders by the second column (Name) alphabetically
    });
});

</script>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="#">Champions</a>
      </li>
      <li class="breadcrumb-item active">Overview</li>
    </ol>
    
    <!-- Button to create a new user -->
    <a class="btn btn-primary my-3" href="./add-new-champion"><i class="fa fa-plus"></i> Create New Champion</a>
     <button onclick="openModal()" class="btn btn-success my-3"><i class="bi bi-box-arrow-in-down"></i> Import</button>
    <!-- Modal overlay and content -->
    <div id="modal" class="modal-overlay">
        <div class="modal-container">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <h2>Import Excel</h2>
            <form id="importForm" enctype="multipart/form-data">
                <label for="excel_file">Choose Excel file:</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx" required>
               <button id="importButton" type="button" onclick="submitImportForm()" class="btn btn-success my-3 cd-button">
    <span id="importButton-spinner" class="spinner-border spinner-border-sm" style="display: none;" role="status" aria-hidden="true"></span>
    Import
</button>

            </form>
        </div>
    </div>

    <!-- Success Message Popup -->
    <div id="successMessage" class="modal-overlay" style="display:none;">
        <div class="modal-container">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <p>Data imported successfully!</p>
        </div>
    </div>
    <a class="btn btn-warning my-3" href="./export_user_file"><i class="bi bi-upload"></i> Export</a>
    
    <!-- User data table -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Phone Extension</th>
                <th>Location</th>
                <th>Preferred Language</th>
                <th>Created at</th>
                <th>Action</th> 
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $user): ?>
              <tr>
                  <td><?php echo htmlspecialchars($user->id); ?></td>
                <td><?php echo htmlspecialchars($user->name); ?></td>
                <td><?php echo htmlspecialchars($user->email); ?></td>
                <td><?php echo htmlspecialchars($user->phone); ?></td>
                <td><?php echo htmlspecialchars($user->phone_extension); ?></td>
                <td><?php echo htmlspecialchars($user->location); ?></td>
                <td><?php echo htmlspecialchars($user->preferred_language); ?></td>
                <?php $date = new DateTime($user->created_at) ?>
                    <td><?php echo $date->format('d-m-Y H:i:s') ?></td>
                <td>
                  <a href="modify-champion?email=<?php echo urlencode($user->email); ?>" class="btn btn-sm btn-warning">
                    <i class="fa fa-edit"></i> Edit
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <?php ob_end_flush();
  include './footer.php'; ?>
<script>
      function openModal() {
    document.getElementById('modal').style.display = 'block';
}

function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('successMessage').style.display = 'none';
}

function submitImportForm() {
    let importButton = document.getElementById("importButton");
    let spinner = document.getElementById("importButton-spinner");

    // Show spinner and disable button
    spinner.style.display = "inline-block";
    importButton.disabled = true;

    let formData = new FormData(document.getElementById('importForm'));
    
    fetch('import_user_file', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('successMessage').style.display = 'block';
            setTimeout(() => {
                window.location.reload();
            }, 500);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    })
    .finally(() => {
        // Hide spinner and enable button after response
        spinner.style.display = "none";
        importButton.disabled = false;
    });
}
    </script>