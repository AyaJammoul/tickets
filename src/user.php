<?php
require_once './src/Database.php'; // Include the Database class

class User {
    public $id = null;
    public $name = '';
    public $email = '';
    public $phone = '';
    public $branch_id = ''; // branch_id to match the column in the database
    public $password = '';
    public $role = '';
    public $phone_extension = ''; 
    public $location = '';
    public $preferred_language = ''; 

    // Database connection instance
    private $db;

    public function __construct($data = null) {
        $this->name = isset($data['name']) ?  $data['name'] : null;
        $this->email = isset($data['email']) ? $data['email'] : null;
        $this->phone = isset($data['phone']) ? $data['phone'] : null;
        $this->password = isset($data['password']) ? $data['password'] : null;
        $this->role = isset($data['role']) ? $data['role'] : null;
        $this->branch_id = $data['branch_id'] ?? null;
        $this->phone_extension = isset($data['phone_extension']) ? $data['phone_extension'] : null; 
        $this->location = isset($data['location']) ? $data['location'] : null; 
        $this->preferred_language = isset($data['preferred_language']) ? $data['preferred_language'] : null; 

        // Database connection instance
        $this->db = Database::getInstance();
    }

    public function save()
    {
        $currentDateTime = date('Y-m-d H:i:s');

        $sql = "INSERT INTO users (name, email, phone, password, branch_id, role, phone_extension, location, preferred_language, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $this->db->error);
        }

        $stmt->bind_param(
            'sssssssssss',
            $this->name,
            $this->email,
            $this->phone,
            $this->password,
            $this->branch_id,
            $this->role,
            $this->phone_extension,
            $this->location,
            $this->preferred_language,
            $currentDateTime,
            $currentDateTime
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        $id = $this->db->insert_id;
        return self::find($id);
    }

    public static function find($id) {
        $self = new static();
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $self->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows < 1) return false;

        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAll(): array {
        $sql = "SELECT * FROM users ORDER BY id DESC";
        $self = new static;
        $res = $self->db->query($sql);

        $users = [];
        while ($row = $res->fetch_object()) {
            $user = new self();
            $user->populateObject($row);
            $users[] = $user;
        }
        return $users;
    }

    public function populateObject($object) {
        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }

    public static function finduser($branchId) {
        $self = new static();
        $sql = "SELECT * FROM users WHERE role = 'member' AND branch_id = ? ORDER BY id DESC";
        $stmt = $self->db->prepare($sql);
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $res = $stmt->get_result();

        $users = [];
        while ($row = $res->fetch_object()) {
            $user = new static();
            $user->populateObject($row);
            $users[] = $user;
        }
        return $users;
    }

    public static function findByEmail($email) {
        $self = new static();
        $query = "SELECT a.id, a.name, b.name AS branch_name, a.email, a.role, a.phone,
                         a.phone_extension, a.location, a.preferred_language, a.branch_id 
                  FROM users a
                  LEFT JOIN branch b ON b.id = a.branch_id
                  WHERE a.email = ?";
        $stmt = $self->db->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $user = new User();
            $user->populateObject((object)$row);
            return $user;
        }

        return null;
    }

    public function update() {
        // Step 1: Retrieve branch ID (assuming `$this->branch_id` is known)
        $currentDateTime = date('Y-m-d H:i:s');
        $query = "UPDATE users SET name = ?, email = ?, role = ?, phone = ?, 
                  phone_extension = ?, location = ?, preferred_language = ?, updated_at = ?
                  WHERE id = ? AND role = 'member'";
        
        $stmt = $this->db->prepare($query);
        
        if (!$stmt) {
            throw new Exception("User prepare statement failed: " . $this->db->error);
        }
        
        $stmt->bind_param('sssssssii', $this->name, $this->email, $this->role, $this->phone, 
                          $this->phone_extension, $this->location, $this->preferred_language, 
                          $currentDateTime, $this->id);
        
        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }
        
        $stmt->close();
    }
}
?>
