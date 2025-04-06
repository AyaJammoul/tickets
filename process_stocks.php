<?php
// Include the Database class
require_once './src/Database.php'; // Adjust the path if needed

// Get the database connection
$conn = Database::getInstance();

// Check if the connection was successful
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate the data
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received.']);
    exit;
}

// Retrieve ticketId and userId from the data
$ticketId = isset($data['ticketId']) ? intval($data['ticketId']) : 0;
$userId = isset($data['userId']) ? intval($data['userId']) : 0;

// Validate that ticketId and userId are present
if ($ticketId === 0 || $userId === 0) {
    echo json_encode(['success' => false, 'message' => 'Missing ticketId or userId.']);
    exit;
}

// Process the selected stocks
foreach ($data['stocks'] as $item) {
    $stockId = intval($item['stockId']); // Sanitize the stock ID
    $quantity = intval($item['quantity']); // Sanitize the quantity

    // Validate that quantity is positive
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity.']);
        exit;
    }

    // Fetch current stock t_quantity to extract numeric value and unit
    $query = "SELECT t_quantity FROM stock WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $stockId);
    $stmt->execute();  // Execute the first query

    // Ensure the result is fetched properly
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($currentQuantity);
        $stmt->fetch();
        
        // Extract numeric value and unit from t_quantity (e.g., 10pcs or 1pkt)
        preg_match('/(\d+)([a-zA-Z]+)/', $currentQuantity, $matches);
        $numericPart = intval($matches[1]);  // Numeric part (e.g., 10)
        $unit = $matches[2];  // Unit (e.g., pcs, pkt)

        // Check if there's enough quantity
        if ($numericPart < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Insufficient stock quantity.']);
            exit;
        }

        // Subtract the quantity and retain the unit
        $newQuantity = ($numericPart - $quantity) . $unit;

        // Query to update the stock quantity
        $updateQuery = "
            UPDATE stock
            SET t_quantity = ?
            WHERE id = ? AND CAST(t_quantity AS UNSIGNED) >= ?";
        $updateStmt = $conn->prepare($updateQuery);
        
        if ($updateStmt === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare SQL query: ' . $conn->error]);
            exit;
        }

        $updateStmt->bind_param('sii', $newQuantity, $stockId, $quantity);

        if (!$updateStmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $updateStmt->error]);
            exit;
        }

// Concatenate quantity and unit
$t_quantity = $quantity . $unit;  // Format it as quantity + unit (e.g., 1pcs)
 $currentDateTime = date('Y-m-d H:i:s');
// Prepare the INSERT statement
$insertQuery = "
    INSERT INTO stock_selections (ticket_id, user_id, stock_id, t_quantity, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?)";

$insertStmt = $conn->prepare($insertQuery);

// Check if the statement preparation was successful
if ($insertStmt === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to prepare INSERT query: ' . $conn->error]);
    exit;
}

// Bind parameters: ticketId, userId, stockId, and t_quantity (as a string)
$insertStmt->bind_param('iiisss', $ticketId, $userId, $stockId, $t_quantity, $currentDateTime,  $currentDateTime );

// Execute the statement
if (!$insertStmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to insert into stock_selections: ' . $insertStmt->error]);
    exit;
}


    } else {
        echo json_encode(['success' => false, 'message' => 'Stock not found for id: ' . $stockId]);
        exit;
    }

    // Free result after use
    $stmt->free_result();
}

// Success message
echo json_encode(['success' => true, 'message' => 'Stocks updated and recorded successfully.']);
?>
