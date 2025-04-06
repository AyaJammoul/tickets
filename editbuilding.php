<?php

// Here we will fetch manager name, building, and door code.
ob_start();
require_once './src/Database.php';
require_once './src/building.php';
session_start(); // Ensure the session is started

// Get building id from query parameters
$building_id = isset($_GET['id']) ? $_GET['id'] : null;

// Redirect to the building list page if no id is provided
if (!$building_id) {
    header('Location: building-dashboard');
    exit();
}

// Find the building by id using findByID
$building = building::findbyID($building_id);

if (!$building) {
    echo json_encode(['success' => false, 'error' => 'building not found']);
    exit();
}

// Handle form submission for updating the building
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get JSON data from the request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $building->manager_id = $data['manager_id'];
    $building->building = $data['building'];
    $building->door_code = $data['door_code']; 

    try {
        $building->update(); // Ensure the update method is defined in your building class
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

$managers = [];
$db = Database::getInstance();

try {
    // Prepare the SQL statement to fetch manager names (assuming managers are in the 'users' table)
    $stmt = $db->prepare("SELECT id, name FROM manager");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $managers[] = $row;
    }

} catch (Exception $e) {
    echo "Error fetching data: " . $e->getMessage();
    exit();
}
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Edit building</title>
    <style>
        body, html {
            height: 100%;
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<section style="background-color: #eee; height: 100vh; display: flex; justify-content: center; align-items: center;">
    <a href="./building-details" class="btn btn-light position-absolute top-0 start-0 m-3">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div class="card mb-4 shadow-sm" style="max-width: 600px; width: 100%;">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Edit building</h5>
        </div>
        <div class="card-body">
            <form id="buildingForm">
                
                <!-- Manager Name -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Manager Name:</strong>
                    </div>
                    <div class="col-sm-8">
                        <select class="form-control" name="manager_id" required>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo htmlspecialchars($manager['id']); ?>" 
                                    <?php if ($manager['id'] === $building->manager_id) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($manager['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Building -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Building:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="building" value="<?php echo htmlspecialchars($building->building); ?>" required>
                    </div>
                </div>
                
                <!-- Door Code -->
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong>Door Code:</strong>
                    </div>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="door_code" value="<?php echo htmlspecialchars($building->door_code); ?>" required>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    document.getElementById('buildingForm').addEventListener('submit', event => {
        event.preventDefault();

        // Collect form data
        const formData = new FormData(event.target);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        // Use building email or building ID here, replace `email@example.com` with dynamic building email or ID
        fetch('modify-building?id=' + encodeURIComponent('<?php echo $building->id; ?>'), { // Pass building ID in query string
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Update Successful!');
                window.location.href = 'building-details'; 
            } else {
                alert('Error updating building: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating building:', error);
            alert('Error updating building');
        });
    });
</script>
</body>
</html>
