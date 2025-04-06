<?php
session_start(); // Start the session to access $_SESSION variables

require_once './src/Database.php'; // Include your database connection file

// Check if the session variable is set
if (!isset($_SESSION['branch_id'])) {
    http_response_code(400); // Bad request
    echo json_encode(['error' => 'Branch ID is not set in session.']);
    exit;
}

$branchId = $_SESSION['branch_id']; // Get the branch ID from session

try {
    $db = Database::getInstance(); // Get the database instance (a mysqli object)

    // Use a prepared statement to prevent SQL injection
    $query = "SELECT id, name FROM requester WHERE branch_id = ? ORDER BY name ASC";
    $stmt = $db->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare the statement: " . $db->error);
    }

    $stmt->bind_param("i", $branchId); // Bind the branch ID as an integer
    $stmt->execute(); // Execute the query
    $result = $stmt->get_result(); // Get the result

    $requesters = [];
    while ($row = $result->fetch_assoc()) { // Fetch each row as an associative array
        $requesters[] = $row;
    }

    $stmt->close(); // Close the statement
    $db->close(); // Close the database connection

    // Return the data as JSON
    header('Content-Type: application/json');
    echo json_encode($requesters);
} catch (Exception $e) {
    http_response_code(500); // Internal server error
    echo json_encode(['error' => $e->getMessage()]);
}
?>
