<?php
require_once './src/Database.php'; // Include the Database class

class Building {
    public $id = null;
    public $manager_id = ''; // Corrected typo from `manger_id` to `manager_id`
    public $branch_id = '';
    public $building = '';
    public $door_code = '';

    // Store the database connection instance
    private $db;

    public function __construct($data = null) {
        $this->manager_id = isset($data['manager_id']) ? $data['manager_id'] : null;
        $this->branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;
        $this->building = isset($data['building']) ? $data['building'] : null;
        $this->door_code = isset($data['door_code']) ? $data['door_code'] : null;

        // Database connection instance
        $this->db = Database::getInstance();
    }

    public function save() {
        $currentDateTime = date('Y-m-d H:i:s');

        // Insert into `building` table with correct columns
        $sql = "INSERT INTO location (manager_id, branch_id, building, door_code, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $this->db->error);
        }

        // Bind parameters to the statement and execute
        $stmt->bind_param(
            'iissss',
            $this->manager_id,
            $this->branch_id,
            $this->building,
            $this->door_code,
            $currentDateTime,
            $currentDateTime
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $this->id = $stmt->insert_id;
        return $this->find($this->id);
    }

    public static function find($id) {
        $self = new static();
        $sql = "SELECT * FROM location WHERE id = ?";
        $stmt = $self->db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $self->db->error);
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows < 1) return false;

        $self->populateObject($result->fetch_object());
        return $self;
    }

    // Retrieve all buildings
   // Inside the Building class
public static function findBuilding($branch_id) {
    $self = new static();
    
    // SQL with WHERE clause before ORDER BY
    $sql = "SELECT location.*, manager.name AS manager_name, branch.name AS branch_name
            FROM location
            LEFT JOIN manager ON location.manager_id = manager.id
            LEFT JOIN branch ON location.branch_id = branch.id
            WHERE location.branch_id = ? 
            ORDER BY location.id DESC";
    
    $locations = [];
    $stmt = $self->db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement: " . $self->db->error);
    }

    // Bind the branch_id parameter to the prepared statement
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows < 1) return [];

    while ($row = $res->fetch_object()) {
        $location = new static();
        $location->populateObject($row);
        $locations[] = $location;
    }

    return $locations;
}

    public function populateObject($object) {
        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }

    public function update() {
        $query = "UPDATE location SET manager_id = ?, building = ?, door_code = ?, updated_at = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);

        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $this->db->error);
        }

        $currentDateTime = date('Y-m-d H:i:s');
        $stmt->bind_param(
            'isssi',
            $this->manager_id,
            $this->building,
            $this->door_code,
            $currentDateTime,
            $this->id
        );

        $stmt->execute();

        if ($stmt->error) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }
    }
   public static function findbyId($id) {
    $self = new static();
    // Assuming you have 'managers' and 'branches' tables and they are related to the location table
    $sql = "SELECT location.*, manager.name AS manager_name, branch.name AS branch_name
            FROM location
            LEFT JOIN manager ON location.manager_id = manager.id
            LEFT JOIN branch ON location.branch_id = branch.id
            WHERE location.id = ?"; 

    $stmt = $self->db->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare statement failed: " . $self->db->error);
    }

    $stmt->bind_param('i', $id); // Bind the ID parameter

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows < 1) {
        return false; // Return false if no record is found
    }

    $row = $result->fetch_object();
    $self->populateObject($row);

    return $self; // Return the populated object
}

}
