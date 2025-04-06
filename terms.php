<?php
// Include database connection
require_once './src/Database.php';
include './header.php';

// Fetch all terms from the database
$db = Database::getInstance();
$stmt = $db->prepare('SELECT * FROM terms');
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to hold terms data
$terms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $terms[] = $row;
}
?>

<body>
<div id="content-wrapper">
    <div class="container-fluid">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="#">Terms</a>
            </li>
            <li class="breadcrumb-item active">Overview</li>
        </ol>
<div class="card mb-3">
            <div class="card-header">
                <h3>Terms</h3>
            </div>
            <div class="card-body">
        <form id="updateForm" action="update_terms.php" method="POST">
            <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="termSelect" class="col-sm-3 col-form-label">Select Term:</label>
            <div class="col-sm-9">
            <select id="termSelect" name="termSelect" onchange="populateInput()" class="form-control">
                <option value="" disabled selected>Choose a term</option>
                <?php foreach ($terms as $term): ?>
                    <option value="<?= $term['id']; ?>"><?= htmlspecialchars($term['name']); ?></option>
                <?php endforeach; ?>
            </select>
            </div>
            </div>
            <br><br>
            <!-- Input field to edit the selected term name -->
            <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
            <label for="newName" class="col-sm-3 col-form-label">Edit Name:</label>
             <div class="col-sm-9">
            <input type="text" id="newName" name="newName" placeholder="Edit term name" class="form-control" required>
            </div>
            </div>
            <br><br>
            <div class="form-group row col-lg-8 offset-lg-2 col-md-8 offset-md-2 col-sm-12">
                <div class="col-sm-8 offset-sm-2">
                    <button type="button" class="btn btn-primary" onclick="updateName()">Save</button>
                </div>
            </div>
        </form>
        </div>
</div>
    <?php include './footer.php'; ?>
</div>

<script>
    // Populate input field with the selected term's name (check if update_name exists or not)
    function populateInput() {
        const select = document.getElementById('termSelect');
        const selectedTermId = select.value;
        
        // Find the term data by ID
        const termsData = <?php echo json_encode($terms); ?>;
        const selectedTerm = termsData.find(term => term.id == selectedTermId);
        
        // If update_name is not null, set it; otherwise, set the regular name
        document.getElementById('newName').value = selectedTerm.update_name || selectedTerm.name;
    }

    // Update the term name in the database
    function updateName() {
        const termId = document.getElementById('termSelect').value;
        const newName = document.getElementById('newName').value;

        if (!termId) {
            alert('Please select a term to update.');
            return;
        }

        fetch('update_terms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${termId}&name=${encodeURIComponent(newName)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Name updated successfully!');
                // Update the selected option text in the dropdown
                const select = document.getElementById('termSelect');
                select.options[select.selectedIndex].text = newName;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
