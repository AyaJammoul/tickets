<?php
ob_start();
//Here we will have taken manager email according to it we get all information about manager. Then we take the id according to it we make update  to everything 
require_once './src/Database.php';
require_once './src/manager.php';
session_start(); // Ensure the session is started

// Get manager email from query parameters
$manager_email = isset($_GET['email']) ? $_GET['email'] : null;

// Redirect to the manager list page if no email is provided
if (!$manager_email) {
    header('Location: manager-dashboard');
    exit();
}

// Find the manager by email using findByEmail
$manager = manager::findByEmail($manager_email);

if (!$manager) {
    echo json_encode(['success' => false, 'error' => 'manager not found']);
    exit();
}

// Log the manager ID for debugging
error_log("manager ID: " . $manager->id); 

// Handle form submission for updating the manager
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Update manager fields with received data
    $manager->name = $data['name'];
    $manager->role = $data['role'];
    $manager->email = $data['email'];
    $manager->phone = $data['phone'];
    $manager->department = $data['department'];
    $manager->job_title = $data['job_title'];
    $manager->phone_extension = $data['phone_extension'];
    $manager->location = $data['location'];
    $manager->preferred_language = $data['preferred_language'];

    try {
        $manager->update(); // Make sure the update method is defined in your manager class
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
    <title>Edit manager</title>
    <style>
        body, html {
            height: 100%;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./manager-dashboard" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit manager</h5>
        </div>
        <div class="card-body">
            <form id="managerForm">
                <!-- Full Name -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Full Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($manager->name); ?>" required>
                    </div>
                </div>
                
                <!-- Email -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Email Address:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($manager->email); ?>" required>
                    </div>
                </div>
                <!-- Mobile Number -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Mobile Number:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($manager->phone); ?>" required>
                    </div>
                </div>
                <!-- Role -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Role:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="role" value="<?php echo htmlspecialchars($manager->role); ?>" required>
                    </div>
                </div>
                <!-- Department -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Department:</strong>
                    </div>
                    <div class="col-sm-8">
                        <select class="form-control" name="department">
                            <option value="KG" <?php if ($manager->department == 'KG') echo 'selected'; ?>>KG</option>
                            <option value="Elementary" <?php if ($manager->department == 'Elementary') echo 'selected'; ?>>Elementary</option>
                            <option value="Girl's Section" <?php if ($manager->department == "Girl's Section") echo 'selected'; ?>>Girl's Section</option>
                            <option value="Boy's Section" <?php if ($manager->department == "Boy's Section") echo 'selected'; ?>>Boy's Section</option>
                            <option value="manageristration" <?php if ($manager->department == 'manageristration') echo 'selected'; ?>>manageristration</option>
                        </select>
                    </div>
                </div>
                <!-- Job Title -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Job Title:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="job_title" value="<?php echo htmlspecialchars($manager->job_title); ?>">
                    </div>
                </div>
                <!-- Phone Extension -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Phone Extension:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="phone_extension" value="<?php echo htmlspecialchars($manager->phone_extension); ?>">
                    </div>
                </div>
                <!-- Location -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Location:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($manager->location); ?>">
                    </div>
                </div>
                <!-- Preferred Language -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Preferred Language:</strong>
                    </div>
                    <div class="col-sm-8">
                        <select class="form-control" name="preferred_language">
                            <option value="Arabic" <?php if ($manager->preferred_language == 'Arabic') echo 'selected'; ?>>Arabic</option>
                            <option value="English" <?php if ($manager->preferred_language == 'English') echo 'selected'; ?>>English</option>
                            <option value="Urdu" <?php if ($manager->preferred_language == 'Urdu') echo 'selected'; ?>>Urdu</option>
                            <option value="French" <?php if ($manager->preferred_language == 'French') echo 'selected'; ?>>French</option>
                            <option value="Hindi" <?php if ($manager->preferred_language == 'Hindi') echo 'selected'; ?>>Hindi</option>
                        </select>
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
    document.getElementById('managerForm').addEventListener('submit', event => {
        event.preventDefault();

        // Collect form data
        const formData = new FormData(event.target);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Use manager email or manager ID here, replace `email@example.com` with dynamic manager email or ID
        fetch('modify-manager?email=' + encodeURIComponent('<?php echo $manager->email; ?>'), { // Pass email in query string
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Update Successful!');
                window.location.href = 'manager-dashboard'; 
            } else {
                alert('Error updating manager: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating manager:', error);
            alert('Error updating manager');
        });
    });
</script>
</body>
</html>
