<?php
ob_start();
//Here we will have taken requester email according to it we get all information about requester. Then we take the id according to it we make update  to everything 
require_once './src/Database.php';
require_once './src/requester.php';
session_start(); // Ensure the session is started

// Get requester email from query parameters
$requester_email = isset($_GET['email']) ? $_GET['email'] : null;

// Redirect to the requester list page if no email is provided
if (!$requester_email) {
    header('Location: requester-dashboard');
    exit();
}

// Find the requester by email using findByEmail
$requester = requester::findByEmail($requester_email);

if (!$requester) {
    echo json_encode(['success' => false, 'error' => 'requester not found']);
    exit();
}

// Log the requester ID for debugging
error_log("requester ID: " . $requester->id); 

// Handle form submission for updating the requester
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Update requester fields with received data
    $requester->name = $data['name'];
    $requester->email = $data['email'];
    $requester->phone = $data['phone'];

    try {
        $requester->update(); // Make sure the update method is defined in your requester class
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
$db = Database::getInstance();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Edit Requester</title>
    <style>
        body, html {
            height: 100%;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./client-request" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Requester</h5>
        </div>
        <div class="card-body">
            <form id="requesterForm">
                <!-- Full Name -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Full Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($requester->name); ?>" required>
                    </div>
                </div>
               
                <!-- Email -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Email Address:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($requester->email); ?>" required>
                    </div>
                </div>
                <!-- Mobile Number -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Mobile Number:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($requester->phone); ?>" required>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    document.getElementById('requesterForm').addEventListener('submit', event => {
        event.preventDefault();

        // Collect form data
        const formData = new FormData(event.target);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Use requester email or requester ID here, replace `email@example.com` with dynamic requester email or ID
        fetch('modify-requester?email=' + encodeURIComponent('<?php echo $requester->email; ?>'), { // Pass email in query string
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Update Successful!');
                window.location.href = 'client-request'; 
            } else {
                alert('Error updating requester: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating requester:', error);
            alert('Error updating requester');
        });
    });
</script>
</body>
</html>
