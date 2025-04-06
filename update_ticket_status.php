<?php
require_once './src/Database.php';

$data = json_decode(file_get_contents('php://input'), true);
$ticketId = $data['ticketId'];
$status = $data['status'];

$db = Database::getInstance();
$sql = "UPDATE ticketmanager SET status = ? WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->bind_param('si', $status, $ticketId);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>
