<?php
ob_start();
include './header.php';
require_once './src/branch.php';


//We check all branchs we have in database
$branchId = $_SESSION ['branch_id'];
$branchs = Branch::findbranchid($branchId);
ob_end_flush();
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
        .modal-container1 {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 600px;
            text-align: center;
        }
         .close-btn1 {
            position: absolute;
            top: -1px;
            right: 3px;
            background: transparent;
            border: none;
            font-size: 25px;
            cursor: pointer;
        }
    </style>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="#">Branchs</a>
      </li>
      <li class="breadcrumb-item active">Overview</li>
    </ol>
    
   
    <!-- Branch data table -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Created at</th>
                <th>Action</th> 
              </tr>
            </thead>
            <tbody>
              <?php foreach ($branchs as $branch): ?>
              <tr>
                <td><?php echo htmlspecialchars($branch->id); ?></td>
                <td><?php echo htmlspecialchars($branch->name); ?></td>
                <?php $date = new DateTime($branch->created_at) ?>
                    <td><?php echo $date->format('d-m-Y H:i:s') ?></td>
                <td>
                  <a href="javascript:void(0);" onclick="openEditModal('<?php echo htmlspecialchars($branch->id); ?>', '<?php echo htmlspecialchars($branch->name); ?>')" class="btn btn-sm btn-warning">
                      <i class="fa fa-edit"></i> Edit
                    </a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Edit Branch Modal -->

<div id="editBranchModal" class="modal-overlay">
    <div class="modal-container1">
        <button class="close-btn1" onclick="closeEditModal()">&times;</button>
        <br>
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Branch</h5>
        </div>
        <form id="editBranchForm">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" name="name" id="edit_branch_name" class="form-control" required>
                    </div>
                </div>
            </div>
            <input type="hidden" id="edit_branch_id">
            <button type="button" onclick="submitEditForm()" class="btn btn-primary my-3">
                <span id="editButton-spinner" class="spinner-border spinner-border-sm" style="display: none;" role="status" aria-hidden="true"></span>
                Update
            </button>
        </form>
    </div>
</div>

    </div>
  </div>

  <?php include './footer.php'; ?>
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
    
    fetch('import_branch_file', {
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
// Open the edit modal with branch data
function openEditModal(branchId, branchName) {
    document.getElementById('edit_branch_id').value = branchId;
    document.getElementById('edit_branch_name').value = branchName;
    document.getElementById('editBranchModal').style.display = 'block';
}

// Close the edit modal
function closeEditModal() {
    document.getElementById('editBranchModal').style.display = 'none';
}

// Submit the edit form
function submitEditForm() {
    let editButton = document.querySelector("#editBranchModal button[type='button']");
    let spinner = document.getElementById("editButton-spinner");

    spinner.style.display = "inline-block";
    editButton.disabled = true;

    const branchId = document.getElementById('edit_branch_id').value;
    const branchName = document.getElementById('edit_branch_name').value;

    fetch('modify-branch?id=' + encodeURIComponent(branchId), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: branchName })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Branch updated successfully!");
            window.location.reload();
        } else {
            alert("Error updating branch: " + data.error);
        }
    })
    .catch(error => console.error('Error:', error))
    .finally(() => {
        spinner.style.display = "none";
        editButton.disabled = false;
    });
}

    </script>