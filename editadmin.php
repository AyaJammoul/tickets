<?php

ob_start();
require_once './src/Database.php';
require_once './src/admin.php';
session_start(); // Ensure the session is started

// Get admin email from query parameters
$admin_email = isset($_GET['email']) ? $_GET['email'] : null;

// Redirect to the admin list page if no email is provided
if (!$admin_email) {
    header('Location: admin-dashboard');
    exit();
}

// Get the logged-in user's ID from the session
$session_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Fetch the admin
$admin = Admin::findByEmail($admin_email);
if (!$admin) {
    error_log("Admin with email $admin_email not found.");
    echo json_encode(['success' => false, 'error' => 'Admin not found']);
    exit();
}

// Update admin on POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        error_log("Invalid JSON input.");
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit();
    }

    error_log("Received POST data: " . json_encode($data));

    // Update fields
    $admin->name = $data['name'];
    $admin->role = $data['role'];
    $admin->email = $data['email'];
    $admin->phone = $data['phone'];
    $admin->department = $data['department'];
    $admin->job_title = $data['job_title'];
    $admin->phone_extension = $data['phone_extension'];
    $admin->location = $data['location'];
    $admin->preferred_language = $data['preferred_language'];

    try {
        $admin->update();

        // Redirect if the user's email changes
        if ($session_user_id == $admin->id && $admin_email !== $data['email']) {
            echo json_encode([
                'success' => true,
                'redirect' => './sign_in',
                'message' => 'Email updated. Please sign in again.',
            ]);
        } else {
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        error_log("Update failed: " . $e->getMessage());
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
    <title>Edit Admin</title>
    <style>
        body, html {
            height: 100%;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./admin-dashboard" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit Admin</h5>
        </div>
        <div class="card-body">
            <form id="adminForm">
                <!-- Full Name -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Full Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($admin->name); ?>" required>
                    </div>
                </div>

                <!-- Email -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Email Address:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin->email); ?>" required>
                    </div>
                </div>
                <!-- Mobile Number -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Mobile Number:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin->phone); ?>" required>
                    </div>
                </div>
                <!-- Role -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Role:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="role" value="<?php echo htmlspecialchars($admin->role); ?>" required>
                    </div>
                </div>
                <!-- Department -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Department:</strong>
                    </div>
                    <div class="col-sm-8">
                        <select class="form-control" name="department">
                            <option value="KG" <?php if ($admin->department == 'KG') echo 'selected'; ?>>KG</option>
                            <option value="Elementary" <?php if ($admin->department == 'Elementary') echo 'selected'; ?>>Elementary</option>
                            <option value="Girl's Section" <?php if ($admin->department == "Girl's Section") echo 'selected'; ?>>Girl's Section</option>
                            <option value="Boy's Section" <?php if ($admin->department == "Boy's Section") echo 'selected'; ?>>Boy's Section</option>
                            <option value="Administration" <?php if ($admin->department == 'Administration') echo 'selected'; ?>>Administration</option>
                        </select>
                    </div>
                </div>
                <!-- Job Title -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Job Title:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="job_title" value="<?php echo htmlspecialchars($admin->job_title); ?>">
                    </div>
                </div>
                <!-- Phone Extension -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Phone Extension:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="phone_extension" value="<?php echo htmlspecialchars($admin->phone_extension); ?>">
                    </div>
                </div>
                <!-- Location -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Location:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($admin->location); ?>">
                    </div>
                </div>
                <!-- Preferred Language -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Preferred Language:</strong>
                    </div>
                    <div class="col-sm-8">
                        <select class="form-control" name="preferred_language">
                            <option value="Arabic" <?php if ($admin->preferred_language == 'Arabic') echo 'selected'; ?>>Arabic</option>
                            <option value="English" <?php if ($admin->preferred_language == 'English') echo 'selected'; ?>>English</option>
                            <option value="Urdu" <?php if ($admin->preferred_language == 'Urdu') echo 'selected'; ?>>Urdu</option>
                            <option value="French" <?php if ($admin->preferred_language == 'French') echo 'selected'; ?>>French</option>
                            <option value="Hindi" <?php if ($admin->preferred_language == 'Hindi') echo 'selected'; ?>>Hindi</option>
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
    document.getElementById('adminForm').addEventListener('submit', event => {
    event.preventDefault();

    const formData = new FormData(event.target);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });

    fetch('<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?email=' + encodeURIComponent('<?php echo $admin_email; ?>'), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Update Successful!');
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.href = 'admin-dashboard';
            }
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating admin');
    });
});

</script>
</body>
</html>
