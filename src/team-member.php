<?php
class TeamMember{
    
    public $id = null;
    
    public $user = '';

    public $team = '';
    
    public $branch_id = ''; 


    public function __construct($data = null) //u have to pass data when obj create,initially null
    {
        $this->user = isset($data['id']) ? $data['id'] : null ;
        $this->team = isset($data['team-id']) ? $data['team-id'] : null;
        $this->branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;
        $this->db = Database::getInstance(); //creating singleton obj,because it is static functn

        return $this;
    }
public function save(): TeamMember
{
    // Get current date and time from the server
    $currentDateTime = date('Y-m-d H:i:s');

    $sql = "INSERT INTO team_member (user, team,branch_id, created_at,updated_at)
            VALUES ('$this->user', '$this->team', '$this->branch_id', '$currentDateTime','$currentDateTime')";
    
    if($this->db->query($sql) === false) {
        throw new Exception($this->db->error);
    }

    $id = $this->db->insert_id;
    return self::find($id); 
}

    public static function find($id) : TeamMember
    {
        $sql ="SELECT * FROM team_member WHERE id = '$id'";
        $self = new static; //ceate an obj, u dont need to create the obj 
        $res = $self->db->query($sql);
        if($res->num_rows < 1) return false;
        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findByTeam($id) : array
    {
        $sql = "SELECT * FROM team_member WHERE status = 'active' AND team = '$id' ORDER BY id DESC";
        $members = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return [];

        while($row = $res->fetch_object()){
            $member = new static;
            $member->populateObject($row);
            $members[] = $member;
        }

        return $members;
    }

    public static function findAll() : array
    {
        $sql = "SELECT * FROM team_member ORDER BY id DESC";
        $members = [];
        $self = new static;
        $res = $self->db->query($sql);
        
        if($res->num_rows < 1) return new static;

        while($row = $res->fetch_object()){
            $member = new static;
            $member->populateObject($row);
            $members[] = $member;
        }

        return $members;
    } 

    public static function getName($id): string 
{
    $sql = "SELECT name FROM users WHERE id = ?";
    $self = new static;
    $stmt = $self->db->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row ? $row['name'] : 'Unknown User'; // Return 'Unknown User' if not found
    } else {
        error_log("Database execute error: " . $stmt->error);
        return 'Unknown User';
    }
}

     public static function getNameadmin($id) : string 
    {
        $sql = "SELECT * FROM admin WHERE id = '$id'";
        //print_r($sql);die;
        $self = new static;
        $res = $self->db->query($sql);
        return $res->fetch_object()->name;
    }

    public function populateObject($object) : void 
    {

        foreach($object as $key => $property){
            $this->$key = $property;
        }
    }   
    
    //Here we are getting member email to send him email
  public static function getEmailById($id) {
    // Define the SQL query to join team_members with users to get the email
    $query = "
        SELECT u.email 
        FROM users u
        JOIN team_member tm ON tm.user = u.id
        WHERE tm.id = ?
    ";

    // Prepare and execute the statement
    $stmt = Database::getInstance()->prepare($query);
    if (!$stmt) {
        // Log or handle the error appropriately
        error_log("Database prepare error: " . Database::getInstance()->error);
        return null;
    }

    // Bind the parameter
    $stmt->bind_param('i', $id); // 'i' indicates that $id is an integer

    // Execute the statement
    if ($stmt->execute()) {
        // Fetch the result
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['email'] : null;
    } else {
        // Log the execution error
        error_log("Database execute error: " . $stmt->error);
        return null;
    }
}

//When we have a ticket having a member and we need to change it we use this function to get the old member email to send him email that he is no longer working on this  ticket

public static function getoldEmailById($userId) {
    $sql = "SELECT email FROM users WHERE id = ?";
    $stmt = Database::getInstance()->prepare($sql);

    if (!$stmt) {
        error_log("Database prepare error: " . Database::getInstance()->error);
        return null;
    }

    $stmt->bind_param('i', $userId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row && isset($row['email'])) {  // Check if row exists and has 'email'
            return $row['email'];
        } else {
            error_log("No email found for user ID: " . $userId);
            return null;
        }
    } else {
        error_log("Database execute error: " . $stmt->error);
        return null;
    }
}


//This function is also for email, we used it to send email closed

public static function getEmailById2($id) {
    // Define the SQL query to join team_members with users to get the email
    $query = "
        SELECT u.email 
        FROM users u
        JOIN team_member tm ON tm.user = u.id
        WHERE tm.user = ?
    ";

    // Prepare and execute the statement
    $stmt = Database::getInstance()->prepare($query);
    if (!$stmt) {
        // Log or handle the error appropriately
        error_log("Database prepare error: " . Database::getInstance()->error);
        return null;
    }

    // Bind the parameter
    $stmt->bind_param('i', $id); // 'i' indicates that $id is an integer

    // Execute the statement
    if ($stmt->execute()) {
        // Fetch the result
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['email'] : null;
    } else {
        // Log the execution error
        error_log("Database execute error: " . $stmt->error);
        return null;
    }
}

//This function is to find each member according to his team. This function is used to stop the member

public static function findByTeam2($id): array
{
    // SQL query to fetch team members associated with the given team ID
    $sql = "SELECT tm.created_at, tm.status, tm.updated_at, u.name as member_name, u.id as member_id, t.name as team_name
            FROM team_member tm
            LEFT JOIN users u ON tm.user = u.id  
            LEFT JOIN team t ON tm.team = t.id
            WHERE tm.team = ? 
            ORDER BY u.id DESC";
    
    $members = [];
    $self = new static;
    
    // Prepare and execute the query
    $stmt = $self->db->prepare($sql);
    $stmt->bind_param('i', $id); // Assuming $id is an integer
    $stmt->execute();
    $res = $stmt->get_result();
    
    // Check if any members were found
    if ($res->num_rows < 1) {
        // If no members, fetch the team name from the team table
        $sql = "SELECT name FROM team WHERE id = ?";
        $stmt = $self->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows < 1) {
            return []; // No team or members found
        }

        // Fetch team name
        $team_row = $res->fetch_object();
        return ['members' => [], 'team_name' => $team_row->name]; // Return team name only
    }

    $team_name = null;

    // Populate members array
    while ($row = $res->fetch_object()) {
        // Check if the member_id exists in the result
        if (!isset($row->member_id)) {
            // Debugging message
            error_log("Row fetched does not contain member_id: " . print_r($row, true));
            continue; // Skip this iteration
        }

        $member = new static;
        $member->populateObject2($row);
        $members[] = $member;

        // Set team name if not already set
        if (!$team_name) {
            $team_name = $row->team_name;
        }
    }

    return ['members' => $members, 'team_name' => $team_name];
}


    public function populateObject2($row)
    {
        $this->id = (int)$row->member_id;
        $this->name = htmlspecialchars($row->member_name);
        $this->created_at = date('d-m-Y H:i:s', strtotime($row->created_at));
        $this->status =  htmlspecialchars($row->status);
        $this->updated_at = htmlspecialchars($row->updated_at);
    }
    
  //This function is to make update for the status of team member. Either he is active or stopped
  
  public function updateStatus($memberId, $teamId, $newStatus) {
   
    $currentDateTime = date('Y-m-d H:i:s');
    $stmt = $this->db->prepare("UPDATE team_member SET status = ?, updated_at = ? WHERE user = ? AND team = ?");
    return $stmt->execute([$newStatus, $currentDateTime, $memberId, $teamId]);
    
}

//This function is to find every one active in the team to used it when we change any member

    public function findActiveMembers($teamId) {
        $stmt = $this->db->prepare("SELECT tm.*, u.name AS user_name 
        FROM team_member tm 
        JOIN users u ON tm.user = u.id 
        WHERE tm.team = ? AND tm.status = 'active'");
            $stmt->bind_param('i', $teamId); 
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
    }
    
    
}