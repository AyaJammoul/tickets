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
    $insertStmt = $db->prepare("INSERT INTO location (manager_id, branch_id, building, door_code, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
    $updateStmt = $db->prepare("UPDATE location SET manager_id = ?, branch_id = ?, building = ?, door_code = ?, updated_at = ? WHERE id = ?");

    if (!$insertStmt || !$updateStmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare database statements.']);
        exit;
    }

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex == 1) continue; // Skip header row

            $cells = $row->getCells();
            $locationId = (string)$cells[0]; 
            $managerName = (string)$cells[1];
            $branchName = (string)$cells[2]; 
            $building = (string)$cells[3];
            $door_code = (string)$cells[4];
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');

            // Fetch manager ID based on manager name
            $managerStmt = $db->prepare("SELECT id FROM manager WHERE name = ?");
            $managerStmt->bind_param("s", $managerName);
            $managerStmt->execute();
            $managerStmt->store_result();

            if ($managerStmt->num_rows > 0) {
                $managerStmt->bind_result($managerId);
                $managerStmt->fetch();
            } else {
                echo json_encode(['status' => 'error', 'message' => "Manager '$managerName' not found!"]);
                exit;
            }
            $managerStmt->close();

            // Fetch branch ID based on branch name
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

            // Check if the location exists
            $checkStmt = $db->prepare("SELECT id FROM location WHERE id = ?");
            $checkStmt->bind_param("s", $locationId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // Update existing location
                $updatedAt = date('Y-m-d H:i:s');
                $updateStmt->bind_param("ssssss", $managerId, $branchId, $building, $door_code, $updatedAt, $locationId);
                if (!$updateStmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => "Error updating row $rowIndex: " . $updateStmt->error]);
                    exit;
                }
            } else {
                // Insert new location
                $createdAt = date('Y-m-d H:i:s');
                $updatedAt = $createdAt; // Initial updated_at should be the same as created_at
                $insertStmt->bind_param("ssssss", $managerId, $branchId, $building, $door_code, $createdAt, $updatedAt);
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
