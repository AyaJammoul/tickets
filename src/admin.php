<?php
require_once './src/Database.php';

class Admin {
    public $id = null;
    public $name = '';
    public $email = '';
    public $phone = '';
    public $branch_id = '';
    public $branch_name = ''; 
    public $password = '';
    public $role = '';
    public $department = '';
    public $job_title = '';
    public $phone_extension = '';
    public $location = '';
    public $preferred_language = '';

    private $db;

    public function __construct($data = null) {
        $this->name = $data['name'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->branch_id = $data['branch_id'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->role = $data['role'] ?? null;
        $this->department = $data['department'] ?? null;
        $this->job_title = $data['job_title'] ?? null;
        $this->phone_extension = $data['phone_extension'] ?? null;
        $this->location = $data['location'] ?? null;
        $this->preferred_language = $data['preferred_language'] ?? null;

        $this->db = Database::getInstance();
    }

    public function save() {
        $currentDateTime = date('Y-m-d H:i:s');

        $sql = "INSERT INTO admin (name, email, phone, branch_id, password, role, department, job_title, phone_extension, location, preferred_language, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sssssssssssss',
            $this->name,
            $this->email,
            $this->phone,
            $this->branch_id,
            $this->password,
            $this->role,
            $this->department,
            $this->job_title,
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
        $sql = "SELECT * FROM admin WHERE id = ?";
        $stmt = $self->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows < 1) return false;

        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAdmin($branchId) {
        $self = new static();
        $sql = "SELECT * FROM admin WHERE branch_id = ? ORDER BY id DESC";
        $stmt = $self->db->prepare($sql);
        $stmt->bind_param('s', $branchId);
        $stmt->execute();
        $res = $stmt->get_result();

        $admins = [];
        while ($row = $res->fetch_object()) {
            $admin = new static();
            $admin->populateObject($row);
            $admins[] = $admin;
        }

        return $admins;
    }

   public static function findByEmail($email) {
    $self = new static();
    $query = "SELECT a.id, a.name, b.name AS branch_name, a.email, a.role, a.phone, a.department, 
                     a.job_title, a.phone_extension, a.location, a.preferred_language, a.branch_id 
              FROM admin a
              LEFT JOIN branch b ON b.id = a.branch_id
              WHERE a.email = ?";
    $stmt = $self->db->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the result has data and fetch it
    if ($row = $result->fetch_assoc()) {
        $admin = new Admin();
        $admin->id = $row['id'];
        $admin->name = $row['name']; // Ensure name is populated here
        $admin->email = $row['email'];
        $admin->branch_name = $row['branch_name'];
        $admin->role = $row['role'];
        $admin->phone = $row['phone'];
        $admin->department = $row['department'];
        $admin->job_title = $row['job_title'];
        $admin->phone_extension = $row['phone_extension'];
        $admin->location = $row['location'];
        $admin->preferred_language = $row['preferred_language'];

        return $admin;
    }

    return null;
}
public function update()
{
    $query = "UPDATE admin SET name = ?, email = ?, role = ?, phone = ?, department = ?, job_title = ?, 
              phone_extension = ?, location = ?, preferred_language = ?, updated_at = ?
              WHERE id = ? AND role = 'admin'";
    
    $stmt = $this->db->prepare($query);
    
    if (!$stmt) {
        throw new Exception("User prepare statement failed: " . $this->db->error);
    }
    
    // Get current date and time
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Bind parameters, including the retrieved branch_id
    $stmt->bind_param('ssssssssssi', $this->name, $this->email, $this->role, $this->phone, $this->department,
                      $this->job_title, $this->phone_extension, $this->location, $this->preferred_language, 
                      $currentDateTime, $this->id);
    
    // Execute the update query
    $stmt->execute();
    
    if ($stmt->error) {
        throw new Exception("User update statement failed: " . $stmt->error);
    }
    
    // Close the update statement
    $stmt->close();
}


    private function populateObject($object) {
        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }
     public static function findByTicket($id) : array 
    {
        $sql = "SELECT * FROM ticket WHERE id = '$id'";
        $events = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return [];

        while($row = $res->fetch_object()){
            $event = new static;
            $event->populateObject($row);
            $events[] = $event;
        }

        return $events;
    }
}
