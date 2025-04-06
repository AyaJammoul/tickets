<?php
ob_start();
//Here we will have taken stock id according to it we get all information about stock. Then we take the id according to it we make update  to everything 
require_once './src/Database.php';
require_once './src/stock.php';
session_start(); // Ensure the session is started

// Get stock id from query parameters
$stock_id = isset($_GET['id']) ? $_GET['id'] : null;

// Redirect to the stock list page if no id is provided
if (!$stock_id) {
    header('Location: stock-details');
    exit();
}

// Find the stock by id using findByid
$stock = Stock::findById($stock_id);

if (!$stock) {
    echo json_encode(['success' => false, 'error' => 'stock not found']);
    exit();
}

// Log the stock ID for debugging
error_log("stock ID: " . $stock->id); 

// Handle form submission for updating the stock
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Update stock fields with received data
    $stock->name = $data['name'];
    $stock->brand = $data['brand'];
    $stock->t_quantity = $data['t_quantity'];
    $stock->stock = $data['stock'];
    $stock->rack_number = $data['rack_number'];
    $stock->sub_rack = $data['sub_rack'];
  
    try {
        $stock->update(); // Make sure the update method is defined in your stock class
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./stock-details" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Stock</h5>
        </div>
        <div class="card-body">
             <form id="stockForm">
                <!-- Stock Name -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Stock Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($stock->name); ?>" required>
                    </div>
                </div>
                <!-- Brand -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Brand:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="brand" value="<?php echo htmlspecialchars($stock->brand); ?>" >
                    </div>
                </div>
                <!-- Quantity -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Total Quantity:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="t_quantity" value="<?php echo htmlspecialchars($stock->t_quantity); ?>" required>
                    </div>
                </div>
                <!-- Stock -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Stock:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="stock" value="<?php echo htmlspecialchars($stock->stock); ?>" required>
                    </div>
                </div>
                <!-- Rack Number -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Rack Number:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="rack_number" value="<?php echo htmlspecialchars($stock->rack_number); ?>" required>
                    </div>
                </div>
                <!-- Sub Rack -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Sub Rack:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="sub_rack" value="<?php echo htmlspecialchars($stock->sub_rack); ?>" required>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
     document.getElementById('stockForm').addEventListener('submit', event => {
        event.preventDefault();

        // Collect form data
        const formData = new FormData(event.target);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Use stock ID here
        fetch('modify-stock?id=' + encodeURIComponent('<?php echo $stock->id; ?>'), { // Pass id in query string
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Update Successful!');
                window.location.href = 'stock-details'; 
            } else {
                alert('Error updating stock: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating stock:', error);
            alert('Error updating stock');
        });
    });
</script>
</body>
</html>
