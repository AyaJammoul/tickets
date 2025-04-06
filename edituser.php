<?php
ob_start();
//Here we will have taken user email according to it we get all information about user. Then we take the id according to it we make update  to everything 
require_once './src/Database.php';
require_once './src/user.php';
session_start(); // Ensure the session is started

// Get user email from query parameters
$user_email = isset($_GET['email']) ? $_GET['email'] : null;

// Redirect to the user list page if no email is provided
if (!$user_email) {
    header('Location: champions-management');
    exit();
}

// Find the user by email using findByEmail
$user = User::findByEmail($user_email);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'user not found']);
    exit();
}

// Log the user ID for debugging
error_log("user ID: " . $user->id); 

// Handle form submission for updating the user
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Update user fields with received data
    $user->name = $data['name'];
    $user->role = $data['role'];
    $user->email = $data['email'];
    $user->phone = $data['phone'];
    $user->phone_extension = $data['phone_extension'];
    $user->location = $data['location'];
    $user->preferred_language = $data['preferred_language'];

    try {
        $user->update(); // Make sure the update method is defined in your user class
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
    <title>Edit user</title>
    <style>
        body, html {
            height: 100%;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./champions-management" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit user</h5>
        </div>
        <div class="card-body">
            <form id="userForm">
                <!-- Full Name -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Full Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($user->name); ?>" required>
                    </div>
                </div>
             
                <!-- Email -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Email Address:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user->email); ?>" required>
                    </div>
                </div>
                <!-- Mobile Number -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Mobile Number:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($user->phone); ?>" required>
                    </div>
                </div>
                <!-- Role -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Role:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="role" value="<?php echo htmlspecialchars($user->role); ?>" required>
                    </div>
                </div>
                <!-- Phone Extension -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Phone Extension:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="phone_extension" value="<?php echo htmlspecialchars($user->phone_extension); ?>">
                    </div>
                </div>
                <!-- Location -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Location:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($user->location); ?>">
                    </div>
                </div>
                <!-- Preferred Language -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Preferred Language:</strong>
                    </div>
                    <div class="col-sm-8">
                        <select class="form-control" name="preferred_language">
                            <option value="Arabic" <?php if ($user->preferred_language == 'Arabic') echo 'selected'; ?>>Arabic</option>
                            <option value="English" <?php if ($user->preferred_language == 'English') echo 'selected'; ?>>English</option>
                            <option value="Urdu" <?php if ($user->preferred_language == 'Urdu') echo 'selected'; ?>>Urdu</option>
                            <option value="French" <?php if ($user->preferred_language == 'French') echo 'selected'; ?>>French</option>
                            <option value="Hindi" <?php if ($user->preferred_language == 'Hindi') echo 'selected'; ?>>Hindi</option>
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
    document.getElementById('userForm').addEventListener('submit', event => {
        event.preventDefault();

        // Collect form data
        const formData = new FormData(event.target);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Use user email or user ID here, replace `email@example.com` with dynamic user email or ID
        fetch('modify-champion?email=' + encodeURIComponent('<?php echo $user->email; ?>'), { // Pass email in query string
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Update Successful!');
                window.location.href = 'champions-management'; 
            } else {
                alert('Error updating user: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating user:', error);
            alert('Error updating user');
        });
    });
</script>
</body>
</html>
