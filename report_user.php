<?php
ob_start();
//Here we make print report according to how many ticket the user have

require_once './src/Database.php';

// Start session
session_start();

// Get user ID from URL
$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    require_once './src/ticket.php';
    require_once './src/user.php';

    $ticket = new Ticket();
    $user = new User();

    // Fetch user details
    $userDetails = $user->find($user_id);
    
    // Fetch the tickets for this user
    $userTickets = $ticket->getTicketsByUser($user_id);

    // Display tickets if found
    ob_end_flush();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!-- Custom fonts for this template-->
        <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <!-- Page level plugin CSS-->
        <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
        <!-- Custom styles for this template-->
        <link href="css/sb-admin.css" rel="stylesheet">
        <title>User Tickets</title>
        <style>
            /* Basic styles for print */
        body {
            font-family: Arial, sans-serif;
        }
        .report {
            width: 80%;
            margin: 0 auto;
            padding: 10px;
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
            <h2>All Tickets for User: <?php echo htmlspecialchars($userDetails->name); ?></h2>
            <table class="ticket-details">
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Title</th>
                        <th>Team</th>
                        <th>Team Member</th>
                        <th>Description</th>
                        <th>Stock</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>Created By</th>
                        <th>Date Created</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($userTickets)): ?>
                    <?php foreach ($userTickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['id']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['team']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['team_member']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['body']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($ticket['stocks'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($ticket['quantity'])); ?></td>
                            <td><?php echo htmlspecialchars($ticket['building']); ?> , <?php echo htmlspecialchars($ticket['door_code']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['created_by']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['priority']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($ticket['comments'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">No tickets found for this user.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
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
        const doc = new jsPDF('landscape');

        // Add a title
        doc.setFontSize(18);
        doc.text( "All Tickets for User: <?php echo htmlspecialchars($userDetails->name); ?>", 105, 20, { align: "center" });
        
        doc.setFontSize(12);
        doc.text("ID User: <?php echo htmlspecialchars($userDetails->id); ?>", 105, 30, { align: "center" });
        
        // Add subtitle
        doc.setFontSize(12);
        doc.text("Generated on: " + new Date().toLocaleDateString(), 105, 40, { align: "center" });

        // Add table
        doc.autoTable({
            html: '.ticket-details', // Select table by class
            startY: 50, // Position to start the table
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
        doc.save("<?php echo htmlspecialchars($userDetails->name); ?>");
    };


    // Attach to the correct button by ID
    document.querySelector("#downloadPDF").addEventListener("click", generatePDF);
});
</script>



    </body>
    </html>
    <?php
} else {
    echo "No user ID provided.";
}
?>
