<?php
require_once './src/Database.php'; // Include the Database class

class Branch {
    public $id = null;
    public $name = '';
  
    // Store the database connection instance
    private $db;

    public function __construct($data = null) {
        $this->name = isset($data['name']) ?  $data['name'] : null;
        
        // Database connection instance
        $this->db = Database::getInstance();
    }

   public function save()
{
    $currentDateTime = date('Y-m-d H:i:s');

    $sql = "INSERT INTO branch (name, created_at,updated_at)
            VALUES ('$this->name', '$currentDateTime', '$currentDateTime')";

    if ($this->db->query($sql) === false) {
        throw new Exception($this->db->error);
    }

    $id = $this->db->insert_id;
    return self::find($id);
}
  public static function find($id) {
        $self = new static();
        $sql = "SELECT * FROM branch WHERE id = '$id'";
        $res = $self->db->query($sql);

        if ($res->num_rows < 1) return false;

        $self->populateObject($res->fetch_object());
        return $self;
    }
    
//This function is used for branch 
    
     public static function findbranch() {
        $self = new static();
        $sql = "SELECT * FROM branch ORDER BY id DESC";
        $branchs = [];
        $res = $self->db->query($sql);

        if ($res->num_rows < 1) return [];

        while ($row = $res->fetch_object()) {
            $branch = new static();
            $branch->populateObject($row);
            $branchs[] = $branch;
        }

        return $branchs;
    }
     public static function findbranchid($branchId) {
    $self = new static();
    $sql = "SELECT * FROM branch WHERE id = ? ORDER BY id DESC";

    // Use a prepared statement to prevent SQL injection
    $stmt = $self->db->prepare($sql);

    if (!$stmt) {
        die("Failed to prepare statement: " . $self->db->error);
    }

    // Bind the parameter
    $stmt->bind_param('i', $branchId); // 'i' indicates the parameter is an integer

    // Execute the statement
    $stmt->execute();

    // Get the result
    $res = $stmt->get_result();
    $branchs = [];

    // Fetch the result
    while ($row = $res->fetch_object()) {
        $branch = new static();
        $branch->populateObject($row);
        $branchs[] = $branch;
    }

    // Close the statement
    $stmt->close();

    return $branchs;
}

    public function populateObject($object) {
        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }

  public function update()
{
    $query = "UPDATE branch SET name = ?, updated_at = ? WHERE id = ?";
    
    $stmt = $this->db->prepare($query);
    
    // Check if the prepare statement was successful
    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $this->db->error);
    }
    
    // Get current date and time from the server
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Correct the number of parameters and their types, adding updated_at
    $stmt->bind_param('ssi', $this->name, $currentDateTime, $this->id);
    
    $stmt->execute();
    
    // Check for execution errors
    if ($stmt->error) {
        throw new Exception("Execute statement failed: " . $stmt->error);
    }
}

}