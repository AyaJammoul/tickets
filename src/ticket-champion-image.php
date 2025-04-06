<?php
include_once 'Database.php';

class ImageChampion {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance();
        // Check for a successful connection
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
//Here we are getting the image path according to ticket id

    public function getImagesByEvent($eventId) {
        // Prepare the SQL statement
        $stmt = $this->conn->prepare("SELECT image_path FROM ticket_champion_images WHERE ticket_id = ?");
        $stmt->bind_param('i', $eventId); // Assuming event_id is an integer
        $stmt->execute();

        // Fetch the results
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
   
//Here we are inserting the image with the ticket id

     public static function create($ticketId, $filename) {
        global $db; // Use the global database connection
        $stmt = $db->prepare("INSERT INTO ticket_champion_images (ticket_id, image_path) VALUES (?, ?)");
        $stmt->bind_param("is", $ticketId, $filename);
        return $stmt->execute();
    }
    //Here we are selecting specific image according to ticket id
    
    public static function getImages($ticketId) {
        global $db; // Use the global database connection
        $sql = "SELECT * FROM ticket_champion_images WHERE ticket_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

}
?>