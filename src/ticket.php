<?php
class Ticket
{
    public $title = '';
    public $body = '';
    public $requester = null;
    public $team = null;
    public $team_member = null;
    public $status = '';
    public $branch_id = '';
    public $location_id = '';
    public $admin_id = '';
    public $priority = '';
    public $rating = '';

    private $db = null;

    public function __construct($data = null)
    {
        $this->title =  isset($data['title']) ? $data['title'] : null;
        $this->body = isset($data['body']) ? $data['body'] : null;
        $this->branch_id = isset($data['branch_id']) ? $data['branch_id'] : null;
        $this->location_id = isset($data['location_id']) ? $data['location_id'] : null;
        $this->admin_id = isset($data['admin_id']) ? $data['admin_id'] : null;
        $this->requester = isset($data['requester']) ? $data['requester'] : null;
        $this->team = isset($data['team']) ? $data['team'] : null;
        $this->team_member = isset($data['team_member']) ? $data['team_member'] : null;
        $this->status = isset($data['status']) ? $data['status'] : 'open';
        $this->priority = isset($data['priority']) ? $data['priority'] : 'low';

        $this->db = Database::getInstance();
    }

  public function save(): Ticket
{
    $teamMember = $this->fetchTeamMember();

    // Get current date and time from the server
    $currentDateTime = date('Y-m-d H:i:s');

    $sql = "INSERT INTO ticket (title, body, requester,branch_id, admin_id,location_id, team, team_member, status, priority, created_at, updated_at)
            VALUES ('$this->title', '$this->body', '$this->requester','$this->branch_id', '$this->admin_id','$this->location_id', '$this->team', '$this->team_member', '$this->status', '$this->priority', '$currentDateTime','$currentDateTime')";

    if ($this->db->query($sql) === false) {
        throw new Exception($this->db->error);
    }

    $id = $this->db->insert_id;
    return self::find($id);
}

    public static function find($id): Ticket
    {
        $sql = "SELECT * FROM ticket WHERE id = '$id'";
        $self = new static;
        $res = $self->db->query($sql);
        if ($res->num_rows < 1) {
            return false;
        }

        $self->populateObject($res->fetch_object());
        return $self;
    }

    public static function findAll($branchId): array
    {
        $sql = "SELECT * FROM ticket where branch_id = '$branchId' ORDER BY id DESC";
        $tickets = [];
        $self = new static;
        $res = $self->db->query($sql);

        if ($res->num_rows < 1) {
            return [];
        }

        while ($row = $res->fetch_object()) {
            $ticket = new static;
            $ticket->populateObject($row);
            $tickets[] = $ticket;
        }

        return $tickets;
    }
public function populateObject($object): void
{
    foreach ($object as $key => $property) {
        $this->$key = $property;
    }

    // Fetch and set the team member's name if the ID is set
    if ($this->team_member) {
        $this->team_member = $this->fetchTeamMember();
    }
    
}
   public function fetchTeamMember(): string
{

    $sql = "SELECT user FROM team_member WHERE id = '$this->team_member' LIMIT 1";
    
    $result = $this->db->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_object();
        return $row->user;  // return the team member's name
    }

    return 'Unknown';  // Return default if no match found
}
//This function is to find all tickets that the member has
    public static function findByMember($member): array
{
    $self = new static;

    $sql = "SELECT ticket.*, team_member.user 
            FROM ticket
            JOIN team_member ON ticket.team_member = team_member.id
            WHERE team_member.user = '$member'
            ORDER BY ticket.id DESC";

    $tickets = [];
    $res = $self->db->query($sql);

    while ($row = $res->fetch_object()) {
        $ticket = new static;
        $ticket->populateObject($row);
        $tickets[] = $ticket;
    }

    return $tickets;
}
public static function findByStatus($status, $branchId): array
{
    // Ensure $status and $branchId are properly escaped and sanitized
    $sql = "SELECT * FROM ticket WHERE status = ? AND branch_id = ? ORDER BY id DESC";

    // Get database connection, assuming Database::getConnection() returns a PDO connection
    $db = Database::getInstance(); 

    // Prepare the SQL statement
    $stmt = $db->prepare($sql);

    // Bind parameters to avoid SQL injection
    $stmt->bind_param("si", $status, $branchId); // "si" means status is a string and branch_id is an integer

    // Execute the query
    $stmt->execute();

    // Fetch results
    $result = $stmt->get_result();
    $tickets = [];

    // Check if there are any results
    if ($result->num_rows > 0) {
        // Loop through each result and populate ticket objects
        while ($row = $result->fetch_object()) {
            $ticket = new static; // Create a new instance of the Ticket class
            $ticket->populateObject($row); // Populate ticket object with data from database
            $tickets[] = $ticket; // Add ticket to the array
        }
    }

    // Return the list of tickets
    return $tickets;
}


//This function is used acoording to status and member id to check the tickets

public static function findByStatusAndUser($status, $user_id): array
{
    $self = new static;
    $tickets = [];

    // Prepare SQL query to join ticket and team_member, filtering by user and status
    $stmt = $self->db->prepare("
        SELECT ticket.*, team_member.user 
        FROM ticket
        JOIN team_member ON ticket.team_member = team_member.id
        WHERE team_member.user = ? AND ticket.status = ?
        ORDER BY ticket.id DESC
    ");
    
    // Bind parameters (user ID and status)
    $stmt->bind_param('is', $user_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the tickets and populate them
    while ($row = $result->fetch_object()) {
        $ticket = new static;
        $ticket->populateObject($row);
        $tickets[] = $ticket;
    }

    return $tickets;
}
 public function unassigned($branchId): array
    {
        $sql = "SELECT * FROM ticket WHERE team_member = '' AND branch_id = '$branchId' ORDER BY id DESC";
        $self = new static;
        $tickets = [];
        $res = $self->db->query($sql);

        while ($row = $res->fetch_object()) {
            $tickets[] = $row;
        }

        return $tickets;
    }
  public function update2($id)
{
    // Update query with 7 placeholders
    $sql = "UPDATE ticket 
            SET title = ?, body = ?, team = ?, location_id = ?, requester = ?, priority = ? 
            WHERE id = ?";
    
    // Prepare the statement
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $this->db->error);
    }
    
    // Bind parameters (7 parameters: title, body, team, location_id, requester, priority, id)
    if (!$stmt->bind_param('sssiisi', 
        $this->title,     
        $this->body,      
        $this->team,      
        $this->location_id,
        $this->requester,  
        $this->priority,  
        $id                
    )) {
        throw new Exception("Failed to bind parameters: " . $stmt->error);
    }
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }
    
    // Close the statement
    $stmt->close();
}

     public function displayStatusBadge(): string
    {
        $badgeType = '';
        if ($this->status == 'open') {
            $badgeType = 'danger';
        } else if ($this->status == 'pending') {
            $badgeType = 'warning';
        } else if ($this->status == 'solved') {
            $badgeType = 'success';
        } else if ($this->status == 'closed') {
            $badgeType = 'info';
        }

        return '<div class="badge badge-' . $badgeType . '" role="badge"> ' . ucfirst($this->status) . '</div>';
    }
    //This function is used to find the email of the one who create a ticket and send him email

   public function findCreateByTicket($id): array
    {
        $self = new static;
        $events = [];
        
        // Use JOIN to get the email of the user who created the ticket
        $stmt = $self->db->prepare("SELECT a.email FROM ticket 
                                    JOIN admin a ON a.id = ticket.admin_id
                                    WHERE ticket.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows < 1) return [];

        while ($row = $result->fetch_object()) {
            $event = new static;
            $event->populateObject($row);
            $events[] = $event;
        }

        return $events;
    }
    public function update($id): Ticket
    {
        $teamMember = $this->fetchTeamMember();

        $sql = "UPDATE ticket SET `team_member` = '$this->team_member', `title` = '$this->title', `body` = '$this->body',
         `requester`='$this->requester', `team`= '$this->team', `status`= '$this->status', `priority`='$this->priority'
         WHERE id = '$id'";

        if ($this->db->query($sql) === false) {
            throw new Exception($this->db->error);
        }

        return self::find($id);
    }
public static function delete($id) {
    $db = Database::getInstance();
    $stmt = $db->prepare("DELETE FROM ticket WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}
 //This function used to count tickets that has status open or pending to check if we can stop the team member or not
    
    public function hasOpenOrPendingTickets($memberId, $teamId) {
        $sql = "
            SELECT COUNT(*) AS ticket_count
            FROM ticket t
            JOIN team_member tm ON t.team_member = tm.id
            WHERE tm.user = ? AND tm.team = ? AND t.status IN ('open', 'pending')
        ";

        if ($stmt = $this->db->prepare($sql)) {
            $stmt->bind_param('ii', $memberId, $teamId);
            $stmt->execute();
            $stmt->bind_result($ticketCount);
            $stmt->fetch();
            $stmt->close();
            return $ticketCount > 0;
        }
        return false;
    }
 //This function used to get informations of tickets that have status open or pending
    
    public function getOpenPendingTickets($userId, $teamId) {
        
        $query = "
            SELECT t.id, t.title 
            FROM ticket t
            JOIN team_member tm ON t.team_member = tm.id 
            WHERE tm.user = ? AND tm.team = ? 
            AND (t.status = 'open' OR t.status = 'pending')
        ";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $userId, $teamId);
        $stmt->execute();
        $result = $stmt->get_result();

        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row; 
        }
        $stmt->close();
        return $tickets; 
    }
    
    //This function used to update the team member when we transfer tickets from stopped team member to active team member
    
    public function transferTicket($ticketId, $newMemberId) {
        $db = Database::getInstance();
        // Use ? as placeholders instead of named parameters
        $stmt = $db->prepare("UPDATE ticket SET team_member = ? WHERE id = ?");
        
        // Bind the parameters in the correct order
        $stmt->bind_param('ii', $newMemberId, $ticketId); // 'ii' specifies that both parameters are integers
        
        return $stmt->execute();
    }
    //This function we are getting all informations about the ticket and we are joining them to have everything for the ticket report

public function getTicketById($id) {
    $id = mysqli_real_escape_string($this->db, $id);

    $query = "SELECT 
            t.id, 
            t.title, 
            t.body, 
            t.requester, 
            t.created_at, 
            t.status, 
            t.priority, 
            t.updated_at,
            u.name AS team_member, 
            team.name AS team,
            GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
            GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
            GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Ensure unique comments
            a.name AS created_by,
            l.building, l.door_code
        FROM ticket t
        LEFT JOIN team_member tm ON t.team_member = tm.id 
        LEFT JOIN users u ON tm.user = u.id
        LEFT JOIN team  ON t.team = team.id
        LEFT JOIN comments c ON t.id = c.ticket
        LEFT JOIN admin a ON t.admin_id = a.id
        LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
        LEFT JOIN location l ON l.id  = t.location_id
        WHERE t.id = '$id'
        GROUP BY 
            t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";


    $result = $this->db->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}
public function getTicketByStock($id) {
    // Prepare the SQL query using prepared statements
    $query = "
        SELECT 
            t.id, 
            t.title, 
            t.body, 
            t.requester, 
            t.created_at, 
            t.status, 
            t.priority, 
            t.updated_at,
            u.name AS team_member, 
            team.name AS team,
            GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
            a.name AS created_by,
            s.t_quantity,
            st.name AS stock_name,
            l.building, 
            l.door_code
        FROM stock_selections s
        LEFT JOIN ticket t ON s.ticket_id = t.id
        LEFT JOIN team_member tm ON t.team_member = tm.id 
        LEFT JOIN users u ON tm.user = u.id
        LEFT JOIN team ON t.team = team.id
        LEFT JOIN comments c ON t.id = c.ticket
        LEFT JOIN admin a ON t.admin_id = a.id
        LEFT JOIN stock st ON st.id = s.stock_id
        LEFT JOIN location l ON l.id = t.location_id
        WHERE s.stock_id = ?
        GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    // Prepare the statement
    $stmt = $this->db->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $this->db->error);
    }

    // Bind parameters and execute
    $stmt->bind_param("i", $id); // Assuming $id is an integer
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute statement: " . $stmt->error);
    }

    // Fetch results
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = $row;
        }
        $stmt->close();
        return $tickets; // Return all tickets as an array
    } else {
        $stmt->close();
        return false; // No tickets found
    }
}


public function getTicketsBybranch($branchId) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   u.name AS team_member, 
                   team.name AS team,GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                   GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
                   GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate distinct comments
                   a.name AS created_by,
                   l.building, l.door_code
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
            LEFT JOIN admin a ON t.admin_id = a.id
            LEFT JOIN stock_selections s ON s.ticket_id = t.id
            LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN location l ON l.id = t.location_id
            WHERE t.branch_id = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
                     t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('i', $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);

        // Debugging: print the branchId and number of tickets found
        echo "Branch ID: " . htmlspecialchars($branchId) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}

//This function we are getting all information of the user tickets specified by the id, and we ade joining them to have everything for the user report

public function getTicketsByUser($user_id) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                   u.name AS team_member, 
                   team.name AS team,
                    GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks,
                    GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
                   a.name AS created_by,
                   l.building, l.door_code
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
             LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN admin a ON t.admin_id = a.id
            LEFT JOIN location l ON l.id  = t.location_id
            WHERE tm.user = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);

        // Debugging: print the user_id and number of tickets found
        echo "User ID: " . htmlspecialchars($user_id) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}

//This function we are getting all information of the requester tickets specified by the id, and we ade joining them to have everything for the requester report

public function getTicketsByRequester($requester_id) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   u.name AS team_member, 
                   team.name AS team,
                   GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                   GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
                   GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
                   a.name AS created_by,
                   l.building, l.door_code
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
             LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN admin a ON t.admin_id = a.id
            LEFT JOIN location l ON l.id  = t.location_id
            WHERE requester = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('i', $requester_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);

        // Debugging: print the requester_id and number of tickets found
        echo "Requester ID: " . htmlspecialchars($requester_id) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}

//This function we are getting all information of the team tickets specified by the id, and we are joining them to have everything for the team report

public function getTicketsByTeam($team_id , $branchId) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   u.name AS team_member, 
                   team.name AS team,
                   GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                   GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
                  GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
                   a.name AS created_by,
                   l.building, l.door_code
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
             LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN admin a ON t.admin_id = a.id
            LEFT JOIN location l ON l.id  = t.location_id
            WHERE team.id = ? AND t.branch_id = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('ii', $team_id , $branchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);
        
        echo "Team ID: " . htmlspecialchars($team_id) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}

//In this function we are getting all informations according to days (created_at) and we are joining them to have everything in date report

public function getTicketsByDate($date_value,$branchId) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   u.name AS team_member, 
                   team.name AS team,
                   GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
                   GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                  GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
                   l.building, l.door_code,
                   a.name AS created_by
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
             LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN admin a ON t.admin_id = a.id
            LEFT JOIN location l ON l.id  = t.location_id
            WHERE DATE(t.created_at) = ? AND t.branch_id = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('ss', $date_value,$branchId); 
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);
        
        echo "Date: " . htmlspecialchars($date_value) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}

//In this function we are getting all informations according to date (created_at) and we are joining them to have everything in date report

public function getTicketsByMonth($date_value,$branchId) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   u.name AS team_member, 
                   team.name AS team,
                   GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                   GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
                   GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
                   l.building, l.door_code,
                   a.name AS created_by
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
            LEFT JOIN admin a ON t.admin_id = a.id
             LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN location l ON l.id  = t.location_id
            WHERE DATE_FORMAT(t.created_at, '%Y-%m') = ? AND t.branch_id = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('ss', $date_value,$branchId); 
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);
        
        echo "Month: " . htmlspecialchars($date_value) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}

//In this function we are getting all tickets informations according on years(created_at) and we are joining them for the date report

public function getTicketsByYear($date_value,$branchId) {
    $sql = "SELECT t.id, 
                   t.title, 
                   t.body, 
                   t.requester, 
                   t.created_at, 
                   t.status, 
                   t.priority, 
                   u.name AS team_member, 
                   team.name AS team,
                   GROUP_CONCAT(DISTINCT s.t_quantity SEPARATOR '\n') AS quantity,
                   GROUP_CONCAT(DISTINCT st.name SEPARATOR '\n') AS stocks, -- Ensure unique stock names
                   GROUP_CONCAT(DISTINCT c.body SEPARATOR '\n') AS comments, -- Aggregate comments
                   l.building, l.door_code,
                   a.name AS created_by
            FROM ticket t
            LEFT JOIN team_member tm ON t.team_member = tm.id 
            LEFT JOIN users u ON tm.user = u.id
            LEFT JOIN team ON t.team = team.id
            LEFT JOIN comments c ON t.id = c.ticket
            LEFT JOIN admin a ON t.admin_id = a.id
             LEFT JOIN stock_selections s ON s.ticket_id = t.id
        LEFT JOIN stock st ON st.id = s.stock_id
            LEFT JOIN location l ON l.id  = t.location_id
            WHERE YEAR(t.created_at) = ? AND t.branch_id = ?
            GROUP BY t.id, t.title, t.body, t.requester, t.created_at, t.status, t.priority, 
            t.updated_at, u.name, team.name, a.name, l.building, l.door_code";

    if ($stmt = $this->db->prepare($sql)) {
        $stmt->bind_param('ss', $date_value,$branchId); // Use 's' for string
        $stmt->execute();
        $result = $stmt->get_result();
        $tickets = $result->fetch_all(MYSQLI_ASSOC);
        
        echo "Year: " . htmlspecialchars($date_value) . "<br>";
        echo "Number of tickets found: " . count($tickets) . "<br>";

        $stmt->close();
        return $tickets;
    } else {
        error_log('Failed to prepare statement: ' . $this->db->error);
        return [];
    }
}
}
?>