<?php
class TicketManager
{
    public $id;
    public $title;
    public $body;
    public $branch_id;
    public $manager_id;
    public $location_id;
    public $priority;
    public $created_at; // Add this property
    public $updated_at; // Add this property
    public $requester_id;

    private $db;

    public function __construct($data = null)
    {
        if ($data) {
            $this->id = isset($data['id']) ? $data['id'] : null;
            $this->title = isset($data['title']) ? $data['title'] : null;
            $this->body = isset($data['body']) ? $data['body'] : null;
            $this->branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;
            $this->manager_id = isset($data['manager_id']) ? $data['manager_id'] : null;
            $this->location_id = isset($data['location_id']) ? $data['location_id'] : null;
            $this->priority = isset($data['priority']) ? $data['priority'] : 'low';
            $this->created_at = isset($data['created_at']) ? $data['created_at'] : null; // Initialize
            $this->updated_at = isset($data['updated_at']) ? $data['updated_at'] : null; // Initialize
            $this->requester_id = isset($data['requester_id']) ? $data['requester_id'] : null;
        }

        $this->db = Database::getInstance();
    }

    public function save(): TicketManager
    {
        $currentDateTime = date('Y-m-d H:i:s');
         $requesterId = isset($_POST['requester']) && $_POST['requester'] != '' ? $_POST['requester'] : 0;

        // Use prepared statements to prevent SQL injection
        $stmt = $this->db->prepare("
            INSERT INTO ticketmanager (title, body, branch_id, manager_id, requester_id, location_id, priority, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)
        ");
        $stmt->bind_param(
            "ssiiiisss",
            $this->title,
            $this->body,
            $this->branch_id,
            $this->manager_id,
            $requesterId,
            $this->location_id,
            $this->priority,
            $currentDateTime,
            $currentDateTime
        );

        if (!$stmt->execute()) {
            throw new Exception("Error saving ticket: " . $stmt->error);
        }

        $this->id = $this->db->insert_id;
        return self::find($this->id);
    }

    public static function find($id): TicketManager
    {
        $self = new static();

        // Use prepared statements
        $stmt = $self->db->prepare("SELECT * FROM ticketmanager WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows < 1) {
            throw new Exception("Ticket with ID $id not found.");
        }

        $data = $result->fetch_assoc();
        return new static($data);
    }

    public static function findAll($branchId,$managerId): array
    {
        $self = new static();
        $tickets = [];

        // Use prepared statements to prevent SQL injection
        $stmt = $self->db->prepare("SELECT * FROM ticketmanager WHERE branch_id = ? AND status = 'waiting' AND manager_id = '$managerId'
        ORDER BY id DESC");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Initialize a new ticket using the constructor
            $tickets[] = new static($row);
        }

        return $tickets;
    }
     public static function findAll2($branchId): array
    {
        $self = new static();
        $tickets = [];

        // Use prepared statements to prevent SQL injection
        $stmt = $self->db->prepare("SELECT * FROM ticketmanager WHERE branch_id = ? AND status = 'waiting'
        ORDER BY id DESC");
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Initialize a new ticket using the constructor
            $tickets[] = new static($row);
        }

        return $tickets;
    }
    public static function delete($id) {
    $db = Database::getInstance();
    $stmt = $db->prepare("DELETE FROM ticketmanager WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
    }
   public function update2($id)
{
    $currentDateTime = date('Y-m-d H:i:s'); // Get the current date and time

    // Modify the SQL to include updated_at
    $sql = "UPDATE ticketmanager SET title = ?, body = ?, location_id = ? , requester_id = ?, priority = ?, updated_at = ? WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $this->db->error);
    }

    // Bind the parameters, including the updated_at timestamp
    $stmt->bind_param('ssssssi', $this->title, $this->body, $this->location_id, $this->requester_id, $this->priority, $currentDateTime, $id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $stmt->close();
}
}
?>
