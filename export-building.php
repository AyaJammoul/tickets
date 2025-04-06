<?php
require './src/Database.php'; // Adjust the path to your database connection
require './src/spout/src/Spout/Autoloader/autoload.php'; // Adjust the path to Spout's autoloader

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;

try {
    // Get the database connection
    $db = Database::getInstance();
    
    // Check if the connection was successful
    if ($db->connect_error) {
        throw new Exception("Connection failed: " . $db->connect_error);
    }

    // Fetch data from the location table along with manager and branch (adjust table names and fields)
    // Assuming you have a "managers" table and "branches" table that you want to join with.
    $sql = "SELECT 
                l.id,
                m.name AS manager_name, 
                b.name AS branch_name,
                l.building,
                l.door_code,
                l.created_at, 
                l.updated_at
            FROM 
                location l
            LEFT JOIN 
                manager m ON l.manager_id = m.id
            LEFT JOIN 
                branch b ON l.branch_id = b.id";

    $result = $db->query($sql); // Execute the query

    // Check if the query was successful
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }

    // Create a writer for XLSX format
    $writer = WriterEntityFactory::createXLSXWriter();
    $writer->openToBrowser("building_export.xlsx"); // Name of the exported file

    // Add headers to the first row
    $headers = [
        WriterEntityFactory::createCell("ID"),
        WriterEntityFactory::createCell("Manager Name"),
        WriterEntityFactory::createCell("Branch Name"),
        WriterEntityFactory::createCell("Building"),
        WriterEntityFactory::createCell("Door Code"),
        WriterEntityFactory::createCell("Created At"),
        WriterEntityFactory::createCell("Updated At")
    ];
    $writer->addRow(WriterEntityFactory::createRow($headers));

    // Add data rows from the database query
    while ($record = $result->fetch_assoc()) {
        $cells = [
            WriterEntityFactory::createCell($record['id']),
            WriterEntityFactory::createCell($record['manager_name']),
            WriterEntityFactory::createCell($record['branch_name']),
            WriterEntityFactory::createCell($record['building']),
            WriterEntityFactory::createCell($record['door_code']),
            WriterEntityFactory::createCell($record['created_at']),
            WriterEntityFactory::createCell($record['updated_at'])
        ];
        $writer->addRow(WriterEntityFactory::createRow($cells)); // Add the row to the Excel file
    }

    // Close the writer and finalize the file
    $writer->close();

    // Free the result set from memory
    $result->free();
    
} catch (Exception $e) {
    // Handle any errors that occurred during the process
    echo "Error: " . $e->getMessage();
}
?>
