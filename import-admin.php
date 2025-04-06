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
    $insertStmt = $db->prepare("INSERT INTO admin (name, email, phone, password, role, created_at, updated_at, department, job_title, phone_extension, location, preferred_language, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $updateStmt = $db->prepare("UPDATE admin SET name = ?, email = ?, phone = ?, password = ?, role = ?, updated_at = ?, department = ?, job_title = ?, phone_extension = ?, location = ?, preferred_language = ?, branch_id = ? WHERE id = ?");

    if (!$insertStmt || !$updateStmt) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to prepare database statements.']);
        exit;
    }

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex == 1) continue; // Skip header row

            $cells = $row->getCells();
            $adminId = (string)$cells[0];
            $name = (string)$cells[1];
            $email = (string)$cells[2];
            $phone = (string)$cells[3];
            $password = (string)$cells[4];
            if (!preg_match('/^\$2y\$/', $password) || strlen($password) !== 60) {
                $password = password_hash($password, PASSWORD_BCRYPT);
            }
            $branchName = (string)$cells[5];
            $role = strtolower((string)$cells[6]);
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
            $department = strtolower((string)$cells[9]);
            $job_title = (string)$cells[10];
            $phone_extension = (string)$cells[11];
            $location = (string)$cells[12];
            $preferred_language = ucfirst(strtolower((string)$cells[13]));

            if (strtolower($department) === "kg") {
                $department = strtoupper($department); // "KG" in uppercase if input is "kg
            } else {
                $department = ucfirst(strtolower($department)); // Capitalize first letter for other inputs
            }

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

            // Check if the admin exists
            $checkStmt = $db->prepare("SELECT id FROM admin WHERE id = ?");
            $checkStmt->bind_param("s", $adminId);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                // Update existing admin
                $updateStmt->bind_param("sssssssssssss", $name, $email, $phone, $password, $role, $updated_at, $department, $job_title, $phone_extension, $location, $preferred_language, $branchId, $adminId);
                if (!$updateStmt->execute()) {
                    echo json_encode(['status' => 'error', 'message' => "Error updating row $rowIndex: " . $updateStmt->error]);
                    exit;
                }
            } else {
                // Insert new admin
                $insertStmt->bind_param("sssssssssssss", $name, $email, $phone, $password, $role, $created_at, $updated_at, $department, $job_title, $phone_extension, $location, $preferred_language, $branchId);
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
