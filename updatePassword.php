<?php

// We can check if the user inserts everything correctly from old password to new password. If everything is correct, we update the password.

header('Content-Type: application/json');
session_start();

// Check if the user is logged in
if (!isset($_SESSION['logged-in']) || $_SESSION['logged-in'] !== true) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

// Get the user ID and role from the session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    echo json_encode(['error' => 'User ID or Role not found in session']);
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

require_once './src/Database.php';
$db = Database::getInstance();

// Check if the database connection is successful
if ($db->connect_error) {
    echo json_encode(['error' => 'Database connection failed: ' . $db->connect_error]);
    exit();
}

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['currentPassword']) || !isset($data['newPassword'])) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

$currentPassword = $db->real_escape_string($data['currentPassword']);
$newPassword = $db->real_escape_string($data['newPassword']);

// Fetch the current password hash from the appropriate table based on user role
$table = '';
if ($userRole == 'member') {
    $table = 'users';
} elseif ($userRole == 'manager') {
    $table = 'manager';
} elseif ($userRole == 'admin') {
    $table = 'admin';
} else {
    echo json_encode(['error' => 'Invalid user role']);
    exit();
}

// Prepare the SQL query to fetch the current password from the correct table
$sql = "SELECT password FROM $table WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['error' => 'User not found']);
    exit();
}

$row = $result->fetch_assoc();
$currentPasswordHash = $row['password'];

// Verify the current password
if (!password_verify($currentPassword, $currentPasswordHash)) {
    echo json_encode(['error' => 'Current password is incorrect']);
    exit();
}

// Hash the new password
$newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

// Update the password in the correct table based on user role
$updateSql = "UPDATE $table SET password = ? WHERE id = ?";
$updateStmt = $db->prepare($updateSql);
$updateStmt->bind_param('si', $newPasswordHash, $userId);

if ($updateStmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to update password: ' . $db->error]);
}

// Close connections
$stmt->close();
$updateStmt->close();
$db->close();
?>
