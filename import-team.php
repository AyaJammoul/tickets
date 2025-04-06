<?php
require './src/spout/src/Spout/Autoloader/autoload.php'; // Adjust the path accordingly
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

require './src/Database.php'; // Ensure this file establishes a database connection
$db = Database::getInstance(); // Get the database instance

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Load the Excel file
    $reader = ReaderEntityFactory::createXLSXReader();
    $reader->open($file);

    // Prepare SQL insert and update statements
    $insertStmt = $db->prepare("INSERT INTO team (name, created_at, updated_at) VALUES (?, ?, ?)");
    $updateStmt = $db->prepare("UPDATE team SET name = ?, updated_at = ? WHERE id = ?");

    if (!$insertStmt || !$updateStmt) {
        die("Failed to prepare statements: " . $db->error);
    }

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex == 1) continue; // Skip header row

            $cells = $row->getCells();

            // Extract values from each cell
            $teamId = (string)$cells[0]; // Get the ID from the first cell
            $name = (string)$cells[1];
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            
            // Check if the team already exists using the ID
            $checkStmt = $db->prepare("SELECT id FROM team WHERE id = ?");
            $checkStmt->bind_param("s", $teamId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // Team exists, update the record
                $updateStmt->bind_param("sss", $name, $updated_at, $teamId);
                if (!$updateStmt->execute()) {
                    echo "Error updating row $rowIndex: " . $updateStmt->error . "<br>";
                }
            } else {
                // New team, insert the record
                $insertStmt->bind_param("sss", $name, $created_at, $updated_at);
                if (!$insertStmt->execute()) {
                    echo "Error inserting row $rowIndex: " . $insertStmt->error . "<br>";
                }
            }

            $checkStmt->close();
        }
    }
    
    // Close statements and reader
    $insertStmt->close();
    $updateStmt->close();
    $reader->close();
    echo json_encode(['status' => 'success', 'message' => 'Data imported successfully!']);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Please upload a valid Excel file.']);
    exit;
}
?>
