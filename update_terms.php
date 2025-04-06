<?php
// Include database connection
require_once './src/Database.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure the 'id' and 'name' data is provided
    if (isset($_POST['id']) && isset($_POST['name'])) {
        $termId = $_POST['id'];
        $newName = $_POST['name'];

        // Get the database connection
        $conn = Database::getInstance();

        if ($conn) {
            // Prepare and execute the update query
            $stmt = $conn->prepare("UPDATE terms SET update_name = ? WHERE id = ?");
            $stmt->bind_param("si", $newName, $termId);  // 's' for string, 'i' for integer

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating terms: ' . $stmt->error]);
            }

            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No terms data provided.']);
    }
}
?>
