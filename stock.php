<?php
ob_start();
include './header.php';
require_once './src/stock.php';
if (isset($_SESSION['logged-in']) && $_SESSION['logged-in'] === true){
    $stocks = Stock::getStocks();
} else {
        // Redirect to login if no user session found
        header("location: sign_in");
        exit();
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
    <script>

//We are working on descending order to let the newst id obtain first

    $(document).ready(function() {
    $('#dataTable').DataTable({
        "order": [[1, "asc"]], // Orders by the second column (Name) in ascending order
        "pageLength": 10,       // Set the initial page length, adjust as needed
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disable ordering on the last column (Action buttons)
        ]
    });
});
</script>
<div id="content-wrapper">
  <div class="container-fluid">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a href="#">Stock</a>
      </li>
      <li class="breadcrumb-item active">Overview</li>
    </ol>
    
    <!-- Button to create a new building -->
    <a class="btn btn-primary my-3" href="./add-new-stock"><i class="fa fa-plus"></i> Create New Stock</a>
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
    <a class="btn btn-warning my-3" href="./export_stock_file"><i class="bi bi-upload"></i> Export</a>
    
    <!-- building data table -->
    <div class="card mb-3">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-sm" id="dataTable" width="100%" cellspacing="0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Brand</th> 
                <th>T_Quantity</th> 
                <th>Stock</th>
                <th>Rack_Number</th>
                <th>Sub_Rack</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($stocks as $stock): ?>
              <tr>
                  <td><?php echo htmlspecialchars($stock->id); ?></td>  <!-- Use -> instead of [] -->
      <td><?php echo htmlspecialchars($stock->name); ?></td>
      <td><?php echo htmlspecialchars($stock->brand); ?></td>
      <td><?php echo htmlspecialchars($stock->t_quantity); ?></td>
      <td><?php echo htmlspecialchars($stock->stock); ?></td>
      <td><?php echo htmlspecialchars($stock->rack_number); ?></td>
      <td><?php echo htmlspecialchars($stock->sub_rack); ?></td>
                  <td>
                  <a href="modify-stock?id=<?php echo urlencode($stock->id); ?>" class="btn btn-sm btn-warning">
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
    
    fetch('import_stock_file', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('successMessage').style.display = 'block';
            setTimeout(() => {
                window.stock.reload();
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