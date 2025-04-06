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
    $result = $db->query("SELECT a.id, a.name, a.email, a.phone,  
                                b.name AS branch_name, a.created_at, a.updated_at
                          FROM requester a
                          LEFT JOIN branch b ON b.id = a.branch_id");

    // Check if the query was successful
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }

    // Create a writer for XLSX format
    $writer = WriterEntityFactory::createXLSXWriter();
    $writer->openToBrowser("requesters_export.xlsx");

    // Add headers to the first row
    $headers = [
        "ID", "Name", "Email", "Phone","Branch Name", "Created At", "Updated At"
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
            $record['branch_name'], 
            $record['created_at'], 
            $record['updated_at']
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
