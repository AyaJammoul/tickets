<?php 
ob_start();
session_start(); 

// Here member or admin or manager information will be obtained according to the person that logged in and they can change their password

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['user_role'];
    
    require_once './src/Database.php';
    $db = Database::getInstance();
    
    // Check in the 'users' table first for members
    if ($user_role == 'member') {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'member'");
        $stmt->bind_param("i", $user_id);
    }
    // Check in the 'manager' table for managers
    elseif ($user_role == 'manager') {
        $stmt = $db->prepare("SELECT * FROM manager WHERE id = ? AND role = 'manager'");
        $stmt->bind_param("i", $user_id);
    }
    // Check in the 'admin' table for admins
    elseif ($user_role == 'admin') {
        $stmt = $db->prepare("SELECT * FROM admin WHERE id = ? AND role = 'admin'");
        $stmt->bind_param("i", $user_id);
    }

    if ($stmt === false) {
        die('Database error: Unable to prepare statement');
    } else {
        $stmt->execute();
        $user = $stmt->get_result()->fetch_object();
        $stmt->close();
        
        // If user is found in respective table, proceed
        if ($user) {
            // Store user info in session or variables
            $_SESSION['user_info'] = $user;
        } else {
            // Redirect if user doesn't exist in any table
            header('Location: ./sign-out');
            exit();
        }
    }
} else {
    // Redirect if session is not set
    header('Location: ./sign_in');
    exit();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>User Profile</title>
    <style>
        body, html {
            height: 100%;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./view-open-tickets" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">User Profile</h5>
        </div>
        <div class="card-body">
            <!-- Full Name -->
            <div class="row mb-3">
                <div class="col-sm-4">
                    <strong>Full Name:</strong>
                </div>
                <div class="col-sm-8">
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user->name); ?>" readonly>
                </div>
            </div>
            <!-- Email -->
            <div class="row mb-3">
                <div class="col-sm-4">
                    <strong>Email Address:</strong>
                </div>
                <div class="col-sm-8">
                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user->email); ?>" readonly>
                </div>
            </div>
            <!-- Mobile Number -->
            <div class="row mb-3">
                <div class="col-sm-4">
                    <strong>Mobile Number:</strong>
                </div>
                <div class="col-sm-8">
                    <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user->phone); ?>" readonly>
                </div>
            </div>
            <!-- Password -->
            <div class="row mb-3">
                <div class="col-sm-4">
                    <strong>Password:</strong>
                </div>
                <div class="col-sm-8">
                    <input class="form-control password" value="******" readonly>
                </div>
            </div>
            <div class="text-end">
                <button class="btn btn-warning mt-2 update-password-btn">Change Password</button>
            </div>
        </div>
    </div>
</section>

<!-- When user or admin wants to change the password w popup will obtain so he must write the old password then the new password after that confirming password so after filling those he can submit and the password will be changed -->

<!-- Modal for Password Change -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="passwordForm">
                    <div class="mb-3">
                        <label for="currentPassword" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="newPassword" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmPassword" required>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
// Password change button functionality
document.querySelector('.update-password-btn').addEventListener('click', () => {
    const passwordModal = new bootstrap.Modal(document.getElementById('passwordModal'));
    passwordModal.show();
});

document.getElementById('passwordForm').addEventListener('submit', event => {
    event.preventDefault();
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        alert('New passwords do not match');
        return;
    }

    fetch('updatePassword.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ currentPassword, newPassword })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password updated successfully');
            window.location.href = 'index.php'; 
        } else {
            alert('Error updating password: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error updating password:', error);
        alert('Error updating password');
    });
});
</script>
</body>
</html>
