<?php
class TicketUser
{
    public $id;
    public $title;
    public $body;
    public $branch_id;
    public $user_id;
    public $location_id;
    public $priority;
    public $created_at; // Add this property
    public $updated_at; // Add this property

    private $db;

    public function __construct($data = null)
    {
        if ($data) {
            $this->id = isset($data['id']) ? $data['id'] : null;
            $this->title = isset($data['title']) ? $data['title'] : null;
            $this->body = isset($data['body']) ? $data['body'] : null;
            $this->branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;
            $this->user_id = isset($data['user_id']) ? $data['user_id'] : null;
            $this->location_id = isset($data['location_id']) ? $data['location_id'] : null;
            $this->priority = isset($data['priority']) ? $data['priority'] : 'low';
            $this->created_at = isset($data['created_at']) ? $data['created_at'] : null; // Initialize
            $this->updated_at = isset($data['updated_at']) ? $data['updated_at'] : null; // Initialize
        }

        $this->db = Database::getInstance();
    }

    public function save(): TicketUser
    {
        $currentDateTime = date('Y-m-d H:i:s');

        // Use prepared statements to prevent SQL injection
        $stmt = $this->db->prepare("
            INSERT INTO ticketuser (title, body, branch_id, user_id, location_id, priority, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssiiisss",
            $this->title,
            $this->body,
            $this->branch_id,
            $this->user_id,
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

    public static function find($id): TicketUser
    {
        $self = new static();

        // Use prepared statements
        $stmt = $self->db->prepare("SELECT * FROM ticketuser WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows < 1) {
            throw new Exception("Ticket with ID $id not found.");
        }

        $data = $result->fetch_assoc();
        return new static($data);
    }

    public static function findAll($branchId,$userId): array
    {
        $self = new static();
        $tickets = [];

        // Use prepared statements to prevent SQL injection
        $stmt = $self->db->prepare("SELECT * FROM ticketuser WHERE branch_id = ? AND status = 'waiting' AND user_id = '$userId'
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
        $stmt = $self->db->prepare("SELECT * FROM ticketuser WHERE branch_id = ? AND status = 'waiting'
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
    $stmt = $db->prepare("DELETE FROM ticketuser WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
    }
    public function update2($id)
{
    $currentDateTime = date('Y-m-d H:i:s'); // Get the current date and time

    // Modify the SQL to include updated_at
    $sql = "UPDATE ticketuser SET title = ?, body = ?, location_id = ?, priority = ?, updated_at = ? WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $this->db->error);
    }

    // Bind the parameters, including the updated_at timestamp
    $stmt->bind_param('sssssi', $this->title, $this->body, $this->location_id, $this->priority, $currentDateTime, $id);

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    $stmt->close();
}
}
?>
