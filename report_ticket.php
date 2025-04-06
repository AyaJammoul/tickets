<?php
ob_start();
//Here we make print report according to ticket id that we want

// Include your Ticket class and database connection
require_once './src/ticket.php';
require_once './src/Database.php';

// Check if 'id' is present in the URL
if (isset($_GET['id'])) {
    $ticket_id = $_GET['id'];  // Get the ticket ID from the URL

    // Instantiate the Ticket class
    $ticket = new Ticket();

    // Fetch the ticket details using a method from the Ticket class
    $ticket_data = $ticket->getTicketById($ticket_id);

    // Check if the ticket was found
    if (!$ticket_data) {
        die("Ticket not found");
    }

    // Check if the status is 'closed' and calculate the days difference
    $days_difference = null;
    if ($ticket_data['status'] === 'closed') {
        $created_at = new DateTime($ticket_data['created_at']);
        $updated_at = new DateTime($ticket_data['updated_at']);
        $days_difference = $created_at->diff($updated_at)->days; // Calculate days difference
    }
} else {
    die("No ticket ID provided");
}
ob_end_flush();
?>

<!DOCTYPE html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Page level plugin CSS-->
    <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin.css" rel="stylesheet">
    <title>Ticket Report</title>
    <style>
        /* Basic styles for print */
        body {
            font-family: Arial, sans-serif;
        }
        .report {
            width: 60%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
        }
        h2 {
            text-align: center;
        }
        .ticket-details {
            margin-top: 30px;
            width: 100%;
        }
        .ticket-details th, .ticket-details td {
            padding: 10px;
            border: 1px solid #ccc;
        }
        .ticket-details th {
            background-color: #f0f0f0;
        }

        @media print {
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 0;
            }
            .report {
                width: 100%;
                margin: 0;
                padding: 20px;
                border: none; /* Remove border for printing */
                box-shadow: none; /* Remove shadow if any */
            }
            .ticket-details {
                width: 100%;
                border-collapse: collapse;
            }
            .ticket-details th, .ticket-details td {
                padding: 10px;
                border: 1px solid #000; 
            }
            .ticket-details th {
                background-color: #f0f0f0;
            }
            .print-button {
                display: none; 
            }
            .btn{
                display: none;
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<a href="./all-ticket-records" class="btn btn-light position-absolute top-0 start-0 m-3">
    <i class="bi bi-arrow-left"></i>
</a>
<div class="report">
    <h2>Ticket Report</h2>

    <table class="ticket-details">
        <tr>
            <th>Ticket ID</th>
            <td><?php echo htmlspecialchars($ticket_data['id']); ?></td>
        </tr>
        <tr>
            <th>Title</th>
            <td><?php echo htmlspecialchars($ticket_data['title']); ?></td>
        </tr>
        <tr>
            <th>Team</th>
            <td><?php echo htmlspecialchars($ticket_data['team']); ?></td>
        </tr>
        <tr>
            <th>Team Member</th>
            <td><?php echo htmlspecialchars($ticket_data['team_member']); ?></td>
        </tr>
        <tr>
            <th>Description</th>
            <td><?php echo htmlspecialchars($ticket_data['body']); ?></td>
        </tr>
        <tr>
            <th>Stock</th>
            <td><?php echo nl2br(htmlspecialchars($ticket_data['stocks'])); ?></td>
        </tr>
        <th>Quantity</th>
            <td><?php echo nl2br(htmlspecialchars($ticket_data['quantity'])); ?></td>
        </tr>
        <tr>
            <th>Location</th>
            <td><?php echo htmlspecialchars($ticket_data['building']); ?> , <?php echo htmlspecialchars($ticket_data['door_code']); ?></td>
        </tr>
         <tr>
            <th>Created By</th>
            <td><?php echo htmlspecialchars($ticket_data['created_by']); ?></td>
        </tr>
        <tr>
            <th>Date Created</th>
            <td><?php echo htmlspecialchars($ticket_data['created_at']); ?></td>
        </tr>
        <tr>
            <th>Status</th>
            <td><?php echo htmlspecialchars($ticket_data['status']); ?></td>
        </tr>
        <tr>
            <th>Priority</th>
            <td><?php echo htmlspecialchars($ticket_data['priority']); ?></td>
        </tr>
        <tr>
            <th>Comment</th>
            <td><?php echo nl2br(htmlspecialchars($ticket_data['comments'])); ?></td>
        </tr>

        <?php if ($days_difference !== null): ?>
            <tr>
                <th>Days Closed</th>
                <td><?php echo htmlspecialchars($days_difference); ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <div class="print-button" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" class="btn btn-primary my-3">Print Report</button>
        <button id="downloadPDF" class="btn btn-success">Download as PDF</button>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.20/jspdf.plugin.autotable.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const generatePDF = () => {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        // Add a title
        doc.setFontSize(18);
        doc.text( "Ticket Report", 105, 20, { align: "center" });
        
        // Add subtitle
        doc.setFontSize(12);
        doc.text("Generated on: " + new Date().toLocaleDateString(), 105, 30, { align: "center" });

        // Add table
        doc.autoTable({
            html: '.ticket-details', // Select table by class
            startY: 40, // Position to start the table
            theme: 'grid', // Table style (options: 'striped', 'grid', 'plain')
            headStyles: { fillColor: [22, 160, 133], textColor: 255 }, // Header row styles
            bodyStyles: { textColor: 50 }, // Body row styles
            alternateRowStyles: { fillColor: [240, 240, 240] }, // Alternate row color
            margin: { top: 50, left: 10, right: 10 }, // Margins for the table
            styles: { font: "helvetica", fontSize: 10 }, // Font style
        });

        // Add footer
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setFontSize(10);
            doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, {
                align: "center",
            });
        }

        // Save the generated PDF
        doc.save("Ticket Report <?php echo htmlspecialchars($ticket_data['id']); ?>");
    };


    // Attach to the correct button by ID
    document.querySelector("#downloadPDF").addEventListener("click", generatePDF);
});
</script>
<!-- Bootstrap core JavaScript-->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<!-- Core plugin JavaScript-->
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

<!-- Page level plugin JavaScript-->
<script src="vendor/chart.js/Chart.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>

<!-- Custom scripts for all pages-->
<script src="js/sb-admin.min.js"></script>

<!-- Demo scripts for this page-->
<script src="js/demo/datatables-demo.js"></script>
<script src="js/demo/chart-area-demo.js"></script>
</body>
</html>
