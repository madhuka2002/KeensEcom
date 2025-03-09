<?php
// Include necessary files
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php');

session_start();

// Check if CSR is logged in
if(!isset($_SESSION['csr_id'])) {
   header('location:index.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// If export button is clicked, generate PDF
if(isset($_GET['export_pdf'])) {
    // Create PDF document
    class MYPDF extends TCPDF {
        // Page header
        public function Header() {
            // Set font
            $this->SetFont('helvetica', 'B', 16);
            // Title
            $this->Cell(0, 15, 'CSR Performance and Ticket Analytics Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
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
            $this->Cell(0, 10, 'Generated: ' . date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
        }
    }

    // Create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Keens Support System');
    $pdf->SetAuthor('Management Report');
    $pdf->SetTitle('CSR Performance Analytics');
    $pdf->SetSubject('Support Ticket Analytics');
    $pdf->SetKeywords('tickets, support, analytics, performance, CSR');

    // Set margins
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Add a page
    $pdf->AddPage();

    // ----------------- EXECUTIVE SUMMARY -----------------
    
    // Get overall ticket statistics for the period
    $overall_stats = $conn->prepare("
        SELECT 
            COUNT(*) as total_tickets,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
            SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets
        FROM customer_support_tickets 
        WHERE created_at BETWEEN ? AND ?
    ");
    
    $overall_stats->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $overall = $overall_stats->fetch(PDO::FETCH_ASSOC);
    
    // Calculate average response and resolution times
    $time_stats = $conn->prepare("
        SELECT 
            AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_resolution_time
        FROM customer_support_tickets 
        WHERE status IN ('resolved', 'closed') 
        AND created_at BETWEEN ? AND ?
    ");
    
    $time_stats->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
    $times = $time_stats->fetch(PDO::FETCH_ASSOC);
    
    // Executive Summary Section
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Executive Summary', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    
    $period_days = ceil((strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24));
    $avg_tickets_per_day = round($overall['total_tickets'] / $period_days, 1);
    $resolution_rate = ($overall['total_tickets'] > 0) ? 
        round((($overall['resolved_tickets'] + $overall['closed_tickets']) / $overall['total_tickets']) * 100, 1) : 0;
    
    $pdf->MultiCell(0, 10, 
        "This report provides analytics on customer support ticket handling during the selected period. " .
        "In summary, the support team handled a total of {$overall['total_tickets']} tickets, averaging $avg_tickets_per_day tickets per day. " .
        "The ticket resolution rate was $resolution_rate%, with an average resolution time of " . 
        round($times['avg_resolution_time'], 1) . " hours.", 0, 'L', 0, 1);
    
    // Key Metrics Section
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Key Metrics Overview', 0, 1, 'L');
    
    // Create 2x2 metrics table
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    
    // Row 1
    $pdf->Cell(85, 10, 'Total Tickets', 1, 0, 'L', 1);
    $pdf->Cell(85, 10, 'Average Resolution Time', 1, 1, 'L', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(85, 15, $overall['total_tickets'], 1, 0, 'C');
    $pdf->Cell(85, 15, round($times['avg_resolution_time'], 1) . ' hours', 1, 1, 'C');
    
    // Row 2
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(85, 10, 'Resolution Rate', 1, 0, 'L', 1);
    $pdf->Cell(85, 10, 'Tickets Per Day', 1, 1, 'L', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(85, 15, $resolution_rate . '%', 1, 0, 'C');
    $pdf->Cell(85, 15, $avg_tickets_per_day, 1, 1, 'C');
    
    $pdf->Ln(5);
    
    // Current Status Breakdown
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Current Ticket Status Breakdown', 0, 1, 'L');
    
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(42, 10, 'Pending', 1, 0, 'C', 1);
    $pdf->Cell(42, 10, 'In Progress', 1, 0, 'C', 1);
    $pdf->Cell(42, 10, 'Resolved', 1, 0, 'C', 1);
    $pdf->Cell(42, 10, 'Closed', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(42, 10, $overall['pending_tickets'], 1, 0, 'C');
    $pdf->Cell(42, 10, $overall['in_progress_tickets'], 1, 0, 'C');
    $pdf->Cell(42, 10, $overall['resolved_tickets'], 1, 0, 'C');
    $pdf->Cell(42, 10, $overall['closed_tickets'], 1, 1, 'C');
    
    // ----------------- CSR PERFORMANCE SECTION -----------------
    
    // Add a page for CSR performance
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'CSR Performance Metrics', 0, 1, 'L');
    
    // ----------------- TICKET CATEGORY ANALYSIS -----------------
    
    // Add a page for category analysis
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Ticket Category Analysis', 0, 1, 'L');
    
    // Get category distribution
    $categories = $conn->prepare("
        SELECT 
            COALESCE(category, 'Uncategorized') as category_name,
            COUNT(*) as ticket_count,
            ROUND(AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)), 1) as avg_resolution_time
        FROM 
            customer_support_tickets
        WHERE 
            created_at BETWEEN ? AND ?
        GROUP BY 
            category
        ORDER BY 
            ticket_count DESC
    ");
    
    
    // Category distribution table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell(80, 10, 'Category', 1, 0, 'L', 1);
    $pdf->Cell(35, 10, 'Ticket Count', 1, 0, 'C', 1);
    $pdf->Cell(35, 10, '% of Total', 1, 0, 'C', 1);
    $pdf->Cell(0, 10, 'Avg. Resolution Time', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 10);
    
    while($category = $categories->fetch(PDO::FETCH_ASSOC)) {
        $percentage = round(($category['ticket_count'] / $overall['total_tickets']) * 100, 1);
        $resolution_time = (!empty($category['avg_resolution_time'])) ? 
            $category['avg_resolution_time'] . ' hours' : 'N/A';
            
        $pdf->Cell(80, 10, $category['category_name'], 1, 0, 'L');
        $pdf->Cell(35, 10, $category['ticket_count'], 1, 0, 'C');
        $pdf->Cell(35, 10, $percentage . '%', 1, 0, 'C');
        $pdf->Cell(0, 10, $resolution_time, 1, 1, 'C');
    }
    
    // ----------------- RECOMMENDATIONS SECTION -----------------
    
    // Add a page for recommendations
    $pdf->AddPage();
    
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Analysis & Recommendations', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 11);
    
    // Calculate some insight metrics for recommendations
    $backlog_percentage = ($overall['total_tickets'] > 0) ? 
        round((($overall['pending_tickets'] + $overall['in_progress_tickets']) / $overall['total_tickets']) * 100, 1) : 0;
}

// Get initial summary stats for preview
$preview_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tickets,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tickets,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets
    FROM customer_support_tickets 
    WHERE created_at BETWEEN ? AND ?
");

$preview_stats->execute([$date_from . ' 00:00:00', $date_to . ' 23:59:59']);
$preview = $preview_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Keens | Management Analytics Report</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="analytics-section mt-5">
    <div class="container">
        <div class="content-card">
            <div class="card-header mb-4">
                <div class="header-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h2 class="header-title">Management Analytics Report</h2>
                <p class="text-muted">Comprehensive analytics for support team performance and ticket metrics</p>
            </div>

            <!-- Report Filters -->
            <div class="filters mb-4">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-5">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" 
                               value="<?= htmlspecialchars($date_from) ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" 
                               value="<?= htmlspecialchars($date_to) ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Report Preview -->
            <div class="report-preview">
                <h4 class="mb-4">Report Preview</h4>
                
                <!-- Summary Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card total">
                            <div class="stat-value"><?= $preview['total_tickets'] ?></div>
                            <div class="stat-label">Total Tickets</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card pending">
                            <div class="stat-value"><?= $preview['pending_tickets'] + $preview['in_progress_tickets'] ?></div>
                            <div class="stat-label">Open Tickets</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card resolved">
                            <div class="stat-value"><?= $preview['resolved_tickets'] ?></div>
                            <div class="stat-label">Resolved Tickets</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card closed">
                            <div class="stat-value"><?= $preview['closed_tickets'] ?></div>
                            <div class="stat-label">Closed Tickets</div>
                        </div>
                    </div>
                </div>
                
                <!-- Report Sections Preview -->
                <div class="report-sections">
                    <div class="report-section">
                        <div class="section-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="section-content">
                            <h5>CSR Performance Metrics</h5>
                            <p>Detailed analysis of individual CSR performance metrics including tickets resolved, resolution time, and SLA compliance.</p>
                        </div>
                    </div>
                    
                    <div class="report-section">
                        <div class="section-icon">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="section-content">
                            <h5>Ticket Category Analysis</h5>
                            <p>Breakdown of tickets by category to identify most common customer issues and resolution times by issue type.</p>
                        </div>
                    </div>
                    
                    <div class="report-section">
                        <div class="section-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="section-content">
                            <h5>SLA Compliance</h5>
                            <p>Analysis of service level agreement metrics, showing percentage of tickets resolved within target timeframes.</p>
                        </div>
                    </div>
                    
                    <div class="report-section">
                        <div class="section-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="section-content">
                            <h5>Recommendations</h5>
                            <p>Data-driven recommendations for improving team efficiency, resource allocation, and customer satisfaction.</p>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid mt-5">
                    <form action="" method="GET">
                        <input type="hidden" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
                        <input type="hidden" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #2ecc71;
    --warning-color: #f39c12;
    --info-color: #3498db;
    --pending-color: #e67e22;
}

.content-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
}

.header-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4361ee, #4895ef);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin: 0 auto 1rem;
}

/* Stats Cards */
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    height: 100%;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
}

.stat-card.total::before {
    background-color: var(--primary-color);
}

.stat-card.pending::before {
    background-color: var(--warning-color);
}

.stat-card.resolved::before {
    background-color: var(--success-color);
}

.stat-card.closed::before {
    background-color: var(--secondary-color);
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* Report Sections Preview */
.report-sections {
    margin-top: 2rem;
}

.report-section {
    display: flex;
    align-items: center;
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.report-section:hover {
    background-color: #eaecef;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(67, 97, 238, 0.1), rgba(72, 149, 239, 0.1));
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1.5rem;
    flex-shrink: 0;
}

.section-content h5 {
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.section-content p {
    color: #6c757d;
    margin-bottom: 0;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-card {
        padding: 1.5rem;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .report-section {
        flex-direction: column;
        text-align: center;
    }
    
    .section-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}