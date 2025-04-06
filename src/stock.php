<?php
require_once './src/Database.php'; // Include the Database class

class Stock {
    public $id = null;
    public $name = ''; 
    public $brand = '';
    public $t_quantity = '';
    public $stock = '';
    public $rack_number = '';
    public $sub_rack = '';
    public $manager_id = null;
    public $branch_id = null;
    public $building = '';
    public $door_code = '';

    // Store the database connection instance
    private $db;

    public function __construct($data = null) {
        // Set properties if data is provided
        if ($data) {
            $this->name = isset($data['name']) ? $data['name'] : '';
            $this->brand = isset($data['brand']) ? $data['brand'] : '';
            $this->t_quantity = isset($data['t_quantity']) ? $data['t_quantity'] : '';
            $this->stock = isset($data['stock']) ? $data['stock'] : '';
            $this->rack_number = isset($data['rack_number']) ? $data['rack_number'] : '';
            $this->sub_rack = isset($data['sub_rack']) ? $data['sub_rack'] : '';
        }

        // Database connection instance
        $this->db = Database::getInstance();
    }

    public function save() {
        $currentDateTime = date('Y-m-d H:i:s');

        $sql = "INSERT INTO stock (name, brand, t_quantity, stock, rack_number, sub_rack, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare statement failed: " . $this->db->error);
        }

        $stmt->bind_param(
            'ssssssss',
            $this->name,
            $this->brand,
            $this->t_quantity,
            $this->stock,
            $this->rack_number,
            $this->sub_rack,
            $currentDateTime,
            $currentDateTime
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute statement failed: " . $stmt->error);
        }

        $this->id = $stmt->insert_id;
        
        // Call find() immediately after saving to retrieve the full record
        return $this->find($this->id);
    }

    public static function find($id) {
        $self = new static();
        $sql = "SELECT * FROM stock WHERE id = ?";
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


    public function populateObject($object) {
        foreach ($object as $key => $property) {
            $this->$key = $property;
        }
    }
   public static function getStocks() {
    // Access the database instance directly in a static context
    $db = Database::getInstance();
    $sql = "SELECT * FROM stock ORDER BY name ASC";
    $stmt = $db->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement: " . $db->error);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $stocks = [];
    while ($stock = $res->fetch_object()) {
        $stocks[] = $stock;
    }

    return $stocks;
}
 public static function findById($id) {
        $self = new static();
        $query = "SELECT *
                  FROM stock
                  WHERE id = ?";
        $stmt = $self->db->prepare($query);
        $stmt->bind_param('s', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $stock = new Stock();
            $stock->populateObject((object)$row);
            return $stock;
        }

        return null;
    }

public function update() {
    $db = Database::getInstance();
    
    try {
        $currentDateTime = date('Y-m-d H:i:s');
        // Prepare the SQL statement
        $stmt = $db->prepare("UPDATE stock SET name = ?, brand = ?, t_quantity = ?, stock = ?, rack_number = ?, sub_rack = ?, updated_at = ? WHERE id = ?");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $db->error);
            return false;
        }

        // Bind the parameters
        $stmt->bind_param("sssssssi", $this->name, $this->brand, $this->t_quantity, $this->stock, $this->rack_number, $this->sub_rack, $currentDateTime, $this->id);
        
        // Execute the statement
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return false;
        }

        // Check if any row was affected
        if ($stmt->affected_rows > 0) {
            return true;
        } else {
            error_log("No rows updated. ID might not exist or data is unchanged.");
            return false;
        }

    } catch (Exception $e) {
        error_log("Exception updating stock: " . $e->getMessage());
        return false;
    } finally {
        // Close the statement
        $stmt->close();
    }
}

}
?>
