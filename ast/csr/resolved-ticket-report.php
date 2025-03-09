<?php
// Include necessary files
include '../components/connect.php';
require_once('tcpdf/tcpdf.php'); // Make sure TCPDF is installed in this directory

session_start();

// Check if CSR is logged in
if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Check if the user has permission to generate reports (you can customize this)
// For example, you might want to restrict this to admin CSRs
$check_permission = $conn->prepare("SELECT role FROM customer_support_reps WHERE id = ?");
$check_permission->execute([$csr_id]);
$csr_data = $check_permission->fetch(PDO::FETCH_ASSOC);

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$specific_csr = isset($_GET['specific_csr']) ? $_GET['specific_csr'] : 'all';

// If form is submitted and export button is clicked, generate PDF
if(isset($_GET['export_pdf'])) {
    // Create new PDF document
    class MYPDF extends TCPDF {
        // Page header
        public function Header() {
            // Logo
            $image_file = '../images/logo.png'; // Replace with your logo path
            if(file_exists($image_file)) {
                $this->Image($image_file, 10, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            // Set font
            $this->SetFont('helvetica', 'B', 16);
            // Title
            $this->Cell(0, 15, 'Resolved Support Tickets Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
            // Date range
            $this->Ln(10);
            $this->SetFont('helvetica', '', 10);
            $this->Cell(0, 10, 'Date Range: ' . date('M d, Y', strtotime($_GET['date_from'])) . ' - ' . date('M d, Y', strtotime($_GET['date_to'])), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }

        // Page footer
        public function Footer() {
            // Position at 15 mm from bottom
            $this->SetY(-15);
            // Set font
            $this->SetFont('helvetica', 'I', 8);
            // Page number
            $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
            // Generation date
            $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Keens Support System');
    $pdf->SetTitle('Resolved Tickets Report');
    $pdf->SetSubject('Customer Support Resolved Tickets');
    $pdf->SetKeywords('TCPDF, PDF, tickets, support, resolved');

    // Set default header and footer data
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // Set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP + 10, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // Add a page
    $pdf->AddPage();

    // Query to get resolved tickets
    $query = "
        SELECT 
            t.ticket_id, 
            t.subject, 
            t.description, 
            t.status, 
            t.created_at, 
            t.resolved_at, 
            u.name as user_name, 
            u.email as user_email,
            c.name as csr_name
        FROM 
            customer_support_tickets t
        JOIN 
            users u ON t.user_id = u.id
        JOIN 
            customer_support_reps c ON t.csr_id = c.id
        WHERE 
            t.status = 'resolved'
            AND t.resolved_at BETWEEN ? AND ?
    ";

    // Add CSR filter if specific CSR is selected
    if($specific_csr != 'all') {
        $query .= " AND t.csr_id = ?";
    }

    $query .= " ORDER BY t.resolved_at DESC";

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    
    if($specific_csr != 'all') {
        $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59', $specific_csr]);
    } else {
        $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    }

    // Start with summary statistics
    $total_tickets = $stmt->rowCount();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate average resolution time
    $total_resolution_time = 0;
    $csr_resolution_counts = [];
    
    foreach($tickets as $ticket) {
        $created = new DateTime($ticket['created_at']);
        $resolved = new DateTime($ticket['resolved_at']);
        $interval = $created->diff($resolved);
        $hours = $interval->h + ($interval->days * 24);
        $total_resolution_time += $hours;
        
        // Count tickets per CSR
        if(isset($csr_resolution_counts[$ticket['csr_name']])) {
            $csr_resolution_counts[$ticket['csr_name']]++;
        } else {
            $csr_resolution_counts[$ticket['csr_name']] = 1;
        }
    }
    
    $avg_resolution_time = $total_tickets > 0 ? round($total_resolution_time / $total_tickets, 2) : 0;

    // Set font
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Summary Statistics', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 8, 'Total Resolved Tickets: ' . $total_tickets, 0, 1, 'L');
    $pdf->Cell(0, 8, 'Average Resolution Time: ' . $avg_resolution_time . ' hours', 0, 1, 'L');
    
    // CSR Performance Table
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Ln(5);
    $pdf->Cell(0, 10, 'CSR Performance', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(100, 7, 'CSR Name', 1, 0, 'C', 1);
    $pdf->Cell(80, 7, 'Tickets Resolved', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 12);
    foreach($csr_resolution_counts as $csr_name => $count) {
        $pdf->Cell(100, 7, $csr_name, 1, 0, 'L');
        $pdf->Cell(80, 7, $count, 1, 1, 'C');
    }
    
    // Add tickets table header
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Ln(10);
    $pdf->Cell(0, 10, 'Resolved Tickets Detail', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(20, 7, 'ID', 1, 0, 'C', 1);
    $pdf->Cell(50, 7, 'Subject', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'User', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Resolved By', 1, 0, 'C', 1);
    $pdf->Cell(40, 7, 'Resolution Date', 1, 1, 'C', 1);
    
    // Add data rows
    $pdf->SetFont('helvetica', '', 10);
    foreach($tickets as $ticket) {
        // Check if we need a new page
        if($pdf->getY() > $pdf->getPageHeight() - 30) {
            $pdf->AddPage();
            
            // Reprint the header
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(20, 7, 'ID', 1, 0, 'C', 1);
            $pdf->Cell(50, 7, 'Subject', 1, 0, 'C', 1);
            $pdf->Cell(40, 7, 'User', 1, 0, 'C', 1);
            $pdf->Cell(40, 7, 'Resolved By', 1, 0, 'C', 1);
            $pdf->Cell(40, 7, 'Resolution Date', 1, 1, 'C', 1);
            $pdf->SetFont('helvetica', '', 10);
        }
        
        $pdf->Cell(20, 7, '#' . $ticket['ticket_id'], 1, 0, 'C');
        
        // Handle long subjects
        $subject = $ticket['subject'];
        if(strlen($subject) > 30) {
            $subject = substr($subject, 0, 27) . '...';
        }
        $pdf->Cell(50, 7, $subject, 1, 0, 'L');
        
        $pdf->Cell(40, 7, $ticket['user_name'], 1, 0, 'L');
        $pdf->Cell(40, 7, $ticket['csr_name'], 1, 0, 'L');
        $pdf->Cell(40, 7, date('M d, Y', strtotime($ticket['resolved_at'])), 1, 1, 'C');
    }
    
    // Output the PDF
    $pdf->Output('resolved_tickets_report.pdf', 'I'); // 'I' means send to browser
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Resolved Tickets Report</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="tickets-section mt-5">
    <div class="container">
        <div class="content-card">
            <div class="card-header mb-4">
                <div class="header-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h2 class="header-title">Resolved Tickets Report</h2>
                <p class="text-muted">Generate and export reports for resolved support tickets</p>
            </div>

            <!-- Report Filters -->
            <div class="filters mb-4">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="specific_csr" class="form-label">CSR</label>
                        <select id="specific_csr" name="specific_csr" class="form-select">
                            <option value="all" <?= $specific_csr == 'all' ? 'selected' : '' ?>>All CSRs</option>
                            <?php
                            // Get list of CSRs
                            $csrs = $conn->query("SELECT id, name FROM customer_support_reps ORDER BY name");
                            while($csr = $csrs->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <option value="<?= $csr['id'] ?>" <?= $specific_csr == $csr['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($csr['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="export_pdf" class="btn btn-primary w-100">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Preview Section -->
            <div class="preview-section">
                <h4 class="mb-3">Report Preview</h4>
                
                <?php
                // Query to get resolved tickets (preview)
                $query = "
                    SELECT 
                        t.ticket_id, 
                        t.subject, 
                        t.status, 
                        t.created_at, 
                        t.resolved_at, 
                        u.name as user_name, 
                        c.name as csr_name
                    FROM 
                        customer_support_tickets t
                    JOIN 
                        users u ON t.user_id = u.id
                    JOIN 
                        customer_support_reps c ON t.csr_id = c.id
                    WHERE 
                        t.status = 'resolved'
                        AND t.resolved_at BETWEEN ? AND ?
                ";

                // Add CSR filter if specific CSR is selected
                if($specific_csr != 'all') {
                    $query .= " AND t.csr_id = ?";
                }

                $query .= " ORDER BY t.resolved_at DESC LIMIT 10"; // Just show 10 for preview

                // Prepare and execute query
                $stmt = $conn->prepare($query);
                
                if($specific_csr != 'all') {
                    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59', $specific_csr]);
                } else {
                    $stmt->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
                }
                
                $total_tickets = $stmt->rowCount();
                ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    This is a preview of the first 10 tickets that will be included in your report.
                    <strong>Total tickets found: <?= $total_tickets ?></strong>
                </div>

                <!-- Preview Table -->
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Resolved By</th>
                                <th>Created</th>
                                <th>Resolved</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($total_tickets > 0): ?>
                                <?php while($ticket = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                    <tr>
                                        <td><span class="id-badge">#<?= $ticket['ticket_id'] ?></span></td>
                                        <td><?= htmlspecialchars($ticket['subject']) ?></td>
                                        <td><?= htmlspecialchars($ticket['user_name']) ?></td>
                                        <td><?= htmlspecialchars($ticket['csr_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($ticket['created_at'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($ticket['resolved_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No resolved tickets found for the selected criteria</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if($total_tickets > 0): ?>
                <div class="d-grid mt-4">
                    <button type="submit" form="report-form" name="export_pdf" class="btn btn-lg btn-success">
                        <i class="fas fa-file-pdf me-2"></i>Generate Complete PDF Report
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

.modern-table {
    width: 100%;
    border-collapse: collapse;
}

.modern-table th {
    padding: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
    background-color: #f8f9fa;
    border-bottom: 2px solid #e9ecef;
}

.modern-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

.id-badge {
    background: #e8f3ff;
    color: var(--primary-color);
    padding: 0.3rem 0.6rem;
    border-radius: 6px;
    font-size: 0.9rem;
}

.preview-section {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .content-card {
        padding: 1rem;
    }
    
    .filters form {
        flex-direction: column;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>