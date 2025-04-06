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

    // Fetch data from the database
    $result = $db->query("SELECT * FROM stock");

    // Check if the query was successful
    if (!$result) {
        throw new Exception("Query failed: " . $db->error);
    }

    // Create a writer for XLSX format
    $writer = WriterEntityFactory::createXLSXWriter();
    $writer->openToBrowser("stocks_export.xlsx");

    // Add headers to the first row
    $headers = [];
    while ($fieldInfo = $result->fetch_field()) {
        $headers[] = WriterEntityFactory::createCell($fieldInfo->name);
    }
    $writer->addRow(WriterEntityFactory::createRow($headers));

    // Add data rows
    while ($record = $result->fetch_assoc()) {
        $cells = [];
        foreach ($record as $cellValue) {
            $cells[] = WriterEntityFactory::createCell($cellValue);
        }
        $writer->addRow(WriterEntityFactory::createRow($cells));
    }

    // Close the writer
    $writer->close();

    // Free the result set
    $result->free();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
