<?php
session_start(); // Start the session to access session variables
require_once './src/Database.php'; // Include your database connection file

try {
    // Get the database instance
    $db = Database::getInstance();

    // Prepare the SQL query
    $query = "SELECT id, name FROM stock ORDER BY name ASC";
    $stmt = $db->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare the statement: " . $db->error);
    }

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute the statement: " . $stmt->error);
    }

    // Fetch the results
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to fetch results: " . $stmt->error);
    }

    $stocks = [];
    while ($row = $result->fetch_assoc()) {
        $stocks[] = $row;
    }

    // Close the statement and database connection
    $stmt->close();
    $db->close();

    // Return the results as JSON
    header('Content-Type: application/json');
    echo json_encode($stocks);

} catch (Exception $e) {
    // Return an error response in case of an exception
    http_response_code(400); // Bad request
    echo json_encode(['error' => $e->getMessage()]);
}
?>
