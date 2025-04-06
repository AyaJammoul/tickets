<?php
session_start(); // Start the session to access session variables
require_once './src/Database.php'; // Include your database connection file

try {
    // Get the branch_id from session or request
    if (isset($_SESSION['branch_id'])) {
        $branchId = $_SESSION['branch_id'];
    } elseif (isset($_GET['branch_id'])) { // Optional: Allow branch_id via GET
        $branchId = intval($_GET['branch_id']); // Sanitize input
    } else {
        throw new Exception("Branch ID is not provided.");
    }

    // Get the database instance (a mysqli object)
    $db = Database::getInstance();

    // Prepare the query with a placeholder for branch_id
    $query = "SELECT id, name FROM users WHERE branch_id = ? ORDER BY name ASC";
    $stmt = $db->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare the statement: " . $db->error);
    }

    // Bind the branch_id parameter and execute the statement
    $stmt->bind_param("i", $branchId);
    $stmt->execute();

    // Fetch the results
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    // Close the statement and database connection
    $stmt->close();
    $db->close();

    // Return the results as JSON
    header('Content-Type: application/json');
    echo json_encode($users);

} catch (Exception $e) {
    // Return an error response in case of an exception
    http_response_code(400); // Bad request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
