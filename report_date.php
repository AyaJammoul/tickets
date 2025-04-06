<?php
ob_start();
//Here we get the date from all ticket page to make print. we select the date to make report. according to team we check team row in ticket table how many ticket we have to make print

require_once './src/Database.php';

// Start session
session_start();

// Get date type and value from URL
$date_type = $_GET['date_type'] ?? null;
$date_value = $_GET['date_value'] ?? null;
$branchId = $_SESSION['branch_id'];
if ($date_type && $date_value) {
    require_once './src/ticket.php';

    $ticket = new Ticket();

    // Fetch tickets based on date type
    if ($date_type === 'specific') {
        $tickets = $ticket->getTicketsByDate($date_value,$branchId); // For a specific date
    } elseif ($date_type === 'month') {
        $tickets = $ticket->getTicketsByMonth($date_value,$branchId); // For a specific month
    } elseif ($date_type === 'year') {
        $tickets = $ticket->getTicketsByYear($date_value,$branchId); // For a specific year
    } else {
        $tickets = []; // Invalid date type
    }

    // Display tickets if found
    ob_end_flush();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
        <link href="css/sb-admin.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
        <title>Date-Based Tickets</title>
        <style>
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
            .print-button {
                text-align: center;
                margin-top: 20px;
            }
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
                .report {
                    width: 100%;
                    margin: 0;
                    padding: 20px;
                    border: none;
                    box-shadow: none;
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
                .btn {
                    display: none;
                }
            }
        </style>
    </head>
    <body>
        <a href="./all-ticket-records" class="btn btn-light position-absolute top-0 start-0 m-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div class="report">
            <h2>Tickets for Date Type: <?php echo htmlspecialchars($date_type); ?> - Value: <?php echo htmlspecialchars($date_value); ?></h2>
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
                        <th>Created By</th>
                        <th>Date Created</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Comment</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($tickets)): ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ticket['id']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['team']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['team_member']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['body']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($ticket['stocks'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($ticket['quantity'])); ?></td>
                            <td><?php echo htmlspecialchars($ticket['created_by']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['status']); ?></td>
                            <td><?php echo htmlspecialchars($ticket['priority']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($ticket['comments'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10">No tickets found for this date criteria.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
            <div class="print-button">
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
        doc.text( "Tickets for Date Type: <?php echo htmlspecialchars($date_type); ?> - Value: <?php echo htmlspecialchars($date_value); ?>", 105, 20, { align: "center" });
    
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
        doc.save("Tickets for Date Type: <?php echo htmlspecialchars($date_type); ?> - Value: <?php echo htmlspecialchars($date_value); ?>");
    };


    // Attach to the correct button by ID
    document.querySelector("#downloadPDF").addEventListener("click", generatePDF);
});
</script>
    </body>
    </html>
    <?php
} else {
    echo "No date type or value provided.";
}
?>
