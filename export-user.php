<?php
require './src/Database.php'; // Adjust the path to your database connection
require './src/spout/src/Spout/Autoloader/autoload.php'; // Adjust the path to Spout's autoloader

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;

try {
    // Get the database connection
    $db = Database::getInstance();
    
    // Check if the connection was successful
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch data from the database
    $result = $db->query("SELECT a.id, a.name, a.email, a.phone, a.password, 
                                b.name AS branch_name, a.role, a.created_at, a.updated_at, a.phone_extension, a.location, a.preferred_language 
                          FROM users a
                          LEFT JOIN branch b ON b.id = a.branch_id");

    // Check if the query was successful
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }

    // Create a writer for XLSX format
    $writer = WriterEntityFactory::createXLSXWriter();
    $writer->openToBrowser("users_export.xlsx");

    // Add headers to the first row
    $headers = [
        "ID", "Name", "Email", "Phone", "Password", 
        "Branch Name", "Role", "Created At", "Updated At", "Phone Extension", "Location", "Preferred Language"
    ];
    $headerRow = WriterEntityFactory::createRowFromArray($headers);
    $writer->addRow($headerRow);

    // Add data rows
    while ($record = $result->fetch_assoc()) {
        $rowData = [
            $record['id'], 
            $record['name'], 
            $record['email'], 
            $record['phone'], 
            $record['password'], 
            $record['branch_name'], 
            $record['role'],
            $record['created_at'], 
            $record['updated_at'],
            $record['phone_extension'], 
            $record['location'], 
            $record['preferred_language']
        ];
        $writer->addRow(WriterEntityFactory::createRowFromArray($rowData));
    }

    // Close the writer
    $writer->close();

    // Free the result set
    $result->free();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
