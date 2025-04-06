<?php
Class Team{
    
    public $id = null;
    
    public $name = '';


    public function __construct($data = null) 
    {
        $this->name = isset($data['name']) ? $data['name'] : null;
        
        $this->db = Database::getInstance();

        return $this;
    }
      public function save(): Team
{
    $currentDateTime = date('Y-m-d H:i:s');

    $sql = "INSERT INTO team (name, created_at, updated_at)
            VALUES ('$this->name', '$currentDateTime' , '$currentDateTime')";
    
    if($this->db->query($sql) === false) {
        throw new Exception($this->db->error);
    }

    $id = $this->db->insert_id;
    return self::find($id);
}


    public static function find($id) : Team
    {
        $sql ="SELECT * FROM team WHERE id = '$id'";
        $self = new static;
        $res = $self->db->query($sql);
        if($res->num_rows < 1) return false;
        $self->populateObject($res->fetch_object());
        return $self;
    }

     public static function findAll(): array {
        $sql = "SELECT * FROM team ORDER BY id DESC";
        $self = new static;
        $res = $self->db->query($sql);

        $teams = [];
        if ($res->num_rows > 0) {
            while ($row = $res->fetch_object()) {
                $team = new self();  // Creating new team object for each row
                $team->populateObject($row);
                $teams[] = $team;  // Add each team to the array
            }
        }
        return $teams;  // Return as an array of team objects
    }
     public function populateObject($object) : void 
    {

        foreach($object as $key => $property){
            $this->$key = $property;
        }
    }
     public static function findById($id) {
        global $db;
        $sql = "SELECT * FROM team WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id); 
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc(); 
        }
        
        return null; 
}
//This function is to make update for the team

    public static function updateName($id, $name) {
        global $db; 
        $currentDateTime = date('Y-m-d H:i:s');
        $sql = "UPDATE team SET name = ? , updated_at = ?  WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("ssi", $name, $currentDateTime, $id);  
        return $stmt->execute(); 
    }

//This function is used to get the name of the team according to the id

  public static function getNameById($teamId) {
      global $db;
    $stmt = $db->prepare("SELECT name FROM team WHERE id = ?");
    $stmt->bind_param("i", $teamId); 
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Fetch the row
        $row = $result->fetch_assoc();
        return $row['name'];
    } else {
        return null;
    }
}
}
?>