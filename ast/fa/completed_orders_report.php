<?php
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php');

session_start();

if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Get completed orders
$completed_query = "SELECT * FROM `orders` WHERE payment_status = 'completed' ORDER BY placed_on DESC";
$completed_stmt = $conn->prepare($completed_query);
$completed_stmt->execute();
$completed_orders = $completed_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total revenue for completed orders
$revenue_query = "SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'completed'";
$revenue_stmt = $conn->prepare($revenue_query);
$revenue_stmt->execute();
$total_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get payment method distribution
$method_query = "SELECT method, COUNT(*) as count, SUM(total_price) as value FROM `orders` 
                WHERE payment_status = 'completed' GROUP BY method";
$method_stmt = $conn->prepare($method_query);
$method_stmt->execute();
$payment_methods = $method_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 15, 'Keens.lk - Completed Orders Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(15);
        
        // Add a line
        $this->Line(10, 25, 200, 25);
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->Cell(0, 10, 'Generated on: ' . date('Y-m-d H:i:s'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Keens.lk');
$pdf->SetAuthor('Financial Auditor');
$pdf->SetTitle('Completed Orders Report');
$pdf->SetSubject('Financial Report');

// Set default header and footer data
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Report Summary Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'REPORT SUMMARY', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Create a summary table
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 10, 'Total Completed Orders:', 1, 0, 'L', 1);
$pdf->Cell(95, 10, count($completed_orders), 1, 1, 'R', 0);

$pdf->Cell(95, 10, 'Total Revenue:', 1, 0, 'L', 1);
$pdf->Cell(95, 10, 'Rs. ' . number_format($total_revenue, 2), 1, 1, 'R', 0);

$pdf->Cell(95, 10, 'Report Generated Date:', 1, 0, 'L', 1);
$pdf->Cell(95, 10, date('Y-m-d H:i:s'), 1, 1, 'R', 0);

$pdf->Ln(10);

// Payment Method Breakdown
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'PAYMENT METHOD BREAKDOWN', 0, 1, 'L');

// Table header for payment methods
$pdf->SetFillColor(51, 122, 183);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(65, 7, 'Payment Method', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Number of Orders', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Total Value (Rs.)', 1, 1, 'C', 1);

// Reset text color
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);

// Table rows for payment methods
$fill = false;
foreach ($payment_methods as $method) {
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(65, 7, ucfirst($method['method']), 1, 0, 'L', $fill);
    $pdf->Cell(65, 7, $method['count'], 1, 0, 'C', $fill);
    $pdf->Cell(65, 7, number_format($method['value'], 2), 1, 1, 'R', $fill);
    $fill = !$fill;
}

$pdf->Ln(10);

// Orders List
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'COMPLETED ORDERS LIST', 0, 1, 'L');

// Table header
$pdf->SetFillColor(51, 122, 183);
$pdf->SetTextColor(255);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(20, 7, 'Order ID', 1, 0, 'C', 1);
$pdf->Cell(40, 7, 'Customer', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Payment Method', 1, 0, 'C', 1);
$pdf->Cell(30, 7, 'Total (Rs.)', 1, 0, 'C', 1);
$pdf->Cell(35, 7, 'Order Date', 1, 0, 'C', 1);
$pdf->Cell(35, 7, 'Status', 1, 1, 'C', 1);

// Reset text color
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 8);

// Table rows
$fill = false;
foreach ($completed_orders as $order) {
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell(20, 7, $order['id'], 1, 0, 'C', $fill);
    
    // Create a multiline cell for customer info
    $customer_info = $order['name'] . "\n" . $order['email'];
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell(40, 7, $customer_info, 1, 'L', $fill);
    $pdf->SetXY($x + 40, $y);
    
    $pdf->Cell(30, 7, ucfirst($order['method']), 1, 0, 'C', $fill);
    $pdf->Cell(30, 7, number_format($order['total_price'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(35, 7, date('M d, Y', strtotime($order['placed_on'])), 1, 0, 'C', $fill);
    
    // Create a styled cell for status
    $pdf->SetFillColor(40, 167, 69);
    $pdf->SetTextColor(255);
    $pdf->Cell(35, 7, 'Completed', 1, 1, 'C', true);
    
    // Reset fill color and text color for next row
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0);
    $fill = !$fill;
}

// Output the PDF
$pdf->Output('completed_orders_report.pdf', 'I');
?>