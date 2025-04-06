<?php
require_once './src/Database.php'; // Include your database connection file

$db = Database::getInstance(); // Get the database instance (a mysqli object)

$query = "SELECT id, name FROM team ORDER BY name ASC";
$result = $db->query($query); // Execute the query

$teams = [];

if ($result) {
    while ($row = $result->fetch_assoc()) { // Fetch each row as an associative array
        $teams[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($teams);
?>