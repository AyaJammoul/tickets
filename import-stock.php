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
    $insertStmt = $db->prepare("INSERT INTO stock (name, brand, t_quantity, stock, rack_number, sub_rack, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $updateStmt = $db->prepare("UPDATE stock SET name = ?, brand = ?, t_quantity = ?, stock = ?, rack_number = ?, sub_rack = ?, updated_at = ?
        WHERE id = ?");

    if (!$insertStmt || !$updateStmt) {
        die("Failed to prepare statements: " . $db->error);
    }

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex == 1) continue; // Skip header row

            $cells = $row->getCells();

            // Extract values from each cell
            $stockId = (string)$cells[0]; // Get the ID from the first cell
            $name = (string)$cells[1];
            $brand = (string)$cells[2];
            $t_quantity = (string)$cells[3];
            $stock = (string)$cells[4];
            $rack_number = (string)$cells[5];
            $sub_rack = (string)$cells[6];
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');

            // Check if the stock already exists using the ID
            $checkStmt = $db->prepare("SELECT id FROM stock WHERE id = ?");
            $checkStmt->bind_param("s", $stockId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // stock exists, update the record
                $updateStmt->bind_param("ssssssss", $name, $brand, $t_quantity, $stock, $rack_number, $sub_rack, $updated_at, $stockId);
                if (!$updateStmt->execute()) {
                    echo "Error updating row $rowIndex: " . $updateStmt->error . "<br>";
                }
            } else {
                // New stock, insert the record
                $insertStmt->bind_param("ssssssss", $name, $brand, $t_quantity, $stock, $rack_number, $sub_rack, $created_at, $updated_at);
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
