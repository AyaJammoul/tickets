<?php
require_once './src/Database.php'; // Your database connection

$db = Database::getInstance();
if (isset($_GET['id'])) {
    $requesterId = intval($_GET['id']);

    $sql = "SELECT email, phone FROM requester WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $requesterId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Requester not found']);
    }
}
?>
