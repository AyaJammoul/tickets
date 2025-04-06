<?php
require './src/spout/src/Spout/Autoloader/autoload.php'; // Adjust the path accordingly
use Box\Spout\Reader\Common\Creator\ReaderEntityFactory;

require './src/Database.php'; // Ensure this file establishes a database connection
$db = Database::getInstance(); // Get the database instance

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Validate file type
    if ($_FILES['excel_file']['type'] !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only .xlsx files are allowed.']);
        exit;
    }

    // Load the Excel file
    $reader = ReaderEntityFactory::createXLSXReader();
    $reader->open($file);

    // Prepare SQL insert and update statements
    $insertStmt = $db->prepare("INSERT INTO requester (name, email, phone, created_at, updated_at, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
    $updateStmt = $db->prepare("UPDATE requester SET name = ?, email = ?, phone = ?, updated_at = ?, branch_id = ? WHERE id = ?");

    if (!$insertStmt || !$updateStmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare database statements.']);
        exit;
    }

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex == 1) continue; // Skip header row

            $cells = $row->getCells();
            $requesterId = (string)$cells[0];
            $name = (string)$cells[1];
            $email = (string)$cells[2];
            $phone = (string)$cells[3];
            $branchName = (string)$cells[4];
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');

            // Fetch branch ID
            $branchStmt = $db->prepare("SELECT id FROM branch WHERE name = ?");
            $branchStmt->bind_param("s", $branchName);
            $branchStmt->execute();
            $branchStmt->store_result();

            if ($branchStmt->num_rows > 0) {
                $branchStmt->bind_result($branchId);
                $branchStmt->fetch();
            } else {
                echo json_encode(['status' => 'error', 'message' => "Branch '$branchName' not found!"]);
                exit;
            }
            $branchStmt->close();

            // Check if the requester exists
            $checkStmt = $db->prepare("SELECT id FROM requester WHERE id = ?");
            $checkStmt->bind_param("s", $requesterId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // Update existing requester
                $updateStmt->bind_param("ssssss", $name, $email, $phone, $updated_at, $branchId, $requesterId);
                if (!$updateStmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => "Error updating row $rowIndex: " . $updateStmt->error]);
                    exit;
                }
            } else {
                // Insert new requester
                $insertStmt->bind_param("ssssss", $name, $email, $phone, $created_at, $updated_at, $branchId);
                if (!$insertStmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => "Error inserting row $rowIndex: " . $insertStmt->error]);
                    exit;
                }
            }

            $checkStmt->close();
        }
    }

    $insertStmt->close();
    $updateStmt->close();
    $reader->close();

    echo json_encode(['status' => 'success', 'message' => 'Data imported successfully!']);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded.']);
    exit;
}
?>
