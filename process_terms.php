<?php
// Assuming you have already set up the database connection
require_once './src/Database.php'; // Include your database connection file

// Get the raw POST data (JSON format)
$data = json_decode(file_get_contents('php://input'), true);
$conn = Database::getInstance();
// Check if terms are provided in the request
if (isset($data['terms'])) {
    $terms_to_check = $data['terms']; // Get the array of terms
    
    $updated_terms = [];

    foreach ($terms_to_check as $term) {
        // Prepare the query to check if the term exists in the terms table
        $sql = "SELECT update_name FROM terms WHERE name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $term);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if a result is returned
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // If update_name is NULL, keep the original term, otherwise use update_name
            if ($row['update_name'] === NULL) {
                $updated_terms[] = $term;
            } else {
                $updated_terms[] = $row['update_name'];
            }
        } else {
            // If term is not found, keep the original term
            $updated_terms[] = $term;
        }

        $stmt->close();
    }

    // Return the updated terms as JSON
    echo json_encode(['updated_terms' => $updated_terms]);
} else {
    // If no terms are provided, return an error
    echo json_encode(['error' => 'No terms provided']);
}
?>
