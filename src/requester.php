<?php
require_once './src/Database.php';

class Requester {

    public $id = null;
    public $name = '';
    public $email = '';
    public $phone = '';
    public $branch_id = '';
    
    private $db = null;

    public function __construct($data = null) {
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->email = isset($data['email']) ? $data['email'] : null;
        $this->phone = isset($data['phone']) ? $data['phone'] : null;
         $this->branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;

        $this->db = Database::getInstance(); // Assuming Database is a singleton
    }

    public function save(): Requester {
        $currentDateTime = date('Y-m-d H:i:s');

        $sql = "INSERT INTO requester (name, email, phone, branch_id, created_at, updated_at)
                VALUES ('$this->name', '$this->email', '$this->phone', '$this->branch_id', '$currentDateTime', '$currentDateTime')";

        if ($this->db->query($sql) === false) {
            throw new Exception($this->db->error);
        }

        $id = $this->db->insert_id;
        return self::find($id);
    }

    public static function find($id): Requester {
        $sql = "SELECT * FROM requester WHERE id = '$id'";
        $self = new static;
        $res = $self->db->query($sql);

        if ($res->num_rows < 1) return false;
        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAll($branchId): array {
        $sql = "SELECT * FROM requester WHERE branch_id = '$branchId'";
        $self = new static;
        $res = $self->db->query($sql);

        $requesters = [];
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_object()) {
                $requester = new self();  // Creating new Requester object for each row
                $requester->populateObject($row);
                $requesters[] = $requester;  // Add each requester to the array
            }
        }
        return $requesters;  // Return as an array of Requester objects
    }
     public function populateObject($object): void {
        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }
     public static function findByEmail($email) {
    $self = new static();
    $query = "SELECT a.id, a.name, b.name AS branch_name, a.email, a.phone, a.branch_id 
              FROM requester a
              LEFT JOIN branch b ON b.id = a.branch_id
              WHERE a.email = ?";
    $stmt = $self->db->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the result has data and fetch it
    if ($row = $result->fetch_assoc()) {
        $requester = new requester();
        $requester->id = $row['id'];
        $requester->name = $row['name']; 
        $requester->email = $row['email'];
        $requester->branch_name = $row['branch_name'];
        $requester->phone = $row['phone'];
        
        return $requester;
    }

    return null;
}
public function update() {
   
    // Step 2: Update the user's details, including the branch ID
    $query = "UPDATE requester SET name = ?, email = ?, phone = ?, updated_at = ? 
              WHERE id = ?";
    
    $stmt = $this->db->prepare($query);
    
    if (!$stmt) {
        throw new Exception("User prepare statement failed: " . $this->db->error);
    }
    
    // Get current date and time
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Bind parameters, including the retrieved branch_id
    $stmt->bind_param('ssssi', $this->name, $this->email, $this->phone,
                      $currentDateTime,  $this->id);
    
    // Execute the update query
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("User update statement failed: " . $stmt->error);
    }
    
    // Close the update statement
    $stmt->close();
}
public static function getEmailById($ticketId) {
        $db = Database::getInstance(); // Use your Database instance
        $sql = "
            SELECT r.name, r.email 
            FROM requester r
            JOIN ticket t ON r.id = t.requester
            WHERE t.id = ?";

        if ($stmt = $db->prepare($sql)) {
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                return $result->fetch_object(); // Return an object containing name and email
            }
        }

        return null; // No result found
    }

}
?>