<?php
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php');

session_start();

if(!isset($_SESSION['auditor_id'])){
   header('location:index.php');
   exit();
}

$auditor_id = $_SESSION['auditor_id'];

// Get pending orders
$pending_query = "SELECT * FROM `orders` WHERE payment_status = 'pending' ORDER BY placed_on DESC";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->execute();
$pending_orders = $pending_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total value of pending orders
$pending_value_query = "SELECT SUM(total_price) as total FROM `orders` WHERE payment_status = 'pending'";
$pending_stmt = $conn->prepare($pending_value_query);
$pending_stmt->execute();
$pending_value = $pending_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Get payment method distribution for pending orders
$method_query = "SELECT method, COUNT(*) as count, SUM(total_price) as value FROM `orders` 
                WHERE payment_status = 'pending' GROUP BY method";
$method_stmt = $conn->prepare($method_query);
$method_stmt->execute();
$payment_methods = $method_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get aging information (how long orders have been pending)
$aging_query = "SELECT 
                  CASE 
                    WHEN DATEDIFF(NOW(), placed_on) <= 2 THEN '0-2 days'
                    WHEN DATEDIFF(NOW(), placed_on) <= 7 THEN '3-7 days'
                    WHEN DATEDIFF(NOW(), placed_on) <= 14 THEN '8-14 days'
                    ELSE '15+ days'
                  END as age_group,
                  COUNT(*) as count,
                  SUM(total_price) as value
                FROM `orders` 
                WHERE payment_status = 'pending'
                GROUP BY age_group
                ORDER BY 
                  CASE age_group
                    WHEN '0-2 days' THEN 1
                    WHEN '3-7 days' THEN 2
                    WHEN '8-14 days' THEN 3
                    WHEN '15+ days' THEN 4
                  END";
$aging_stmt = $conn->prepare($aging_query);
$aging_stmt->execute();
$aging_data = $aging_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create new PDF document
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 15, 'Keens.lk - Pending Orders Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
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
$pdf->SetTitle('Pending Orders Report');
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

// Alert Banner for high-value pending orders
if ($pending_value > 10000) {
    $pdf->SetFillColor(255, 193, 7); // Warning yellow
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'ATTENTION: High Value Pending Orders', 1, 1, 'C', 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 10, 'There are ' . count($pending_orders) . ' pending orders with a total value of USD ' . 
                   number_format($pending_value, 2) . '. Please review and process these orders promptly.', 1, 'L', 1);
    $pdf->Ln(5);
}

// Report Summary Section
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'REPORT SUMMARY', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

// Create a summary table
$pdf->SetFillColor(240, 240, 240);
$pdf->Cell(95, 10, 'Total Pending Orders:', 1, 0, 'L', 1);
$pdf->Cell(95, 10, count($pending_orders), 1, 1, 'R', 0);

$pdf->Cell(95, 10, 'Total Pending Value:', 1, 0, 'L', 1);
$pdf->Cell(95, 10, 'USD ' . number_format($pending_value, 2), 1, 1, 'R', 0);

$pdf->Cell(95, 10, 'Report Generated Date:', 1, 0, 'L', 1);
$pdf->Cell(95, 10, date('Y-m-d H:i:s'), 1, 1, 'R', 0);

$pdf->Ln(10);

// Aging Analysis
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'AGING ANALYSIS', 0, 1, 'L');

// Table header for aging
$pdf->SetFillColor(255, 193, 7); // Warning yellow
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(65, 7, 'Age Group', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Number of Orders', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Total Value (USD)', 1, 1, 'C', 1);

// Reset text color
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 10);

// Table rows for aging
$fill = false;
foreach ($aging_data as $age) {
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(65, 7, $age['age_group'], 1, 0, 'L', $fill);
    $pdf->Cell(65, 7, $age['count'], 1, 0, 'C', $fill);
    $pdf->Cell(65, 7, number_format($age['value'], 2), 1, 1, 'R', $fill);
    $fill = !$fill;
}

$pdf->Ln(10);

// Payment Method Breakdown
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 10, 'PAYMENT METHOD BREAKDOWN', 0, 1, 'L');

// Table header for payment methods
$pdf->SetFillColor(255, 193, 7); // Warning yellow
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(65, 7, 'Payment Method', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Number of Orders', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Total Value (USD)', 1, 1, 'C', 1);

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
$pdf->Cell(0, 10, 'PENDING ORDERS LIST', 0, 1, 'L');

// Table header
$pdf->SetFillColor(255, 193, 7); // Warning yellow
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(20, 7, 'Order ID', 1, 0, 'C', 1);
$pdf->Cell(40, 7, 'Customer', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Payment Method', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Total (USD)', 1, 0, 'C', 1);
$pdf->Cell(35, 7, 'Order Date', 1, 0, 'C', 1);
$pdf->Cell(25, 7, 'Days Pending', 1, 0, 'C', 1);
$pdf->Cell(20, 7, 'Action', 1, 1, 'C', 1);

// Reset text color
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', '', 8);

// Table rows
$fill = false;
foreach ($pending_orders as $order) {
    $pdf->SetFillColor(240, 240, 240);
    
    // Calculate days pending
    $order_date = new DateTime($order['placed_on']);
    $today = new DateTime();
    $days_pending = $today->diff($order_date)->days;
    
    $pdf->Cell(20, 7, $order['id'], 1, 0, 'C', $fill);
    
    // Create a multiline cell for customer info
    $customer_info = $order['name'] . "\n" . $order['email'];
    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell(40, 7, $customer_info, 1, 'L', $fill);
    $pdf->SetXY($x + 40, $y);
    
    $pdf->Cell(25, 7, ucfirst($order['method']), 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, number_format($order['total_price'], 2), 1, 0, 'R', $fill);
    $pdf->Cell(35, 7, date('M d, Y', strtotime($order['placed_on'])), 1, 0, 'C', $fill);
    
    // Highlight old pending orders in red
    if ($days_pending > 7) {
        $pdf->SetTextColor(255, 0, 0);
    }
    $pdf->Cell(25, 7, $days_pending . ' days', 1, 0, 'C', $fill);
    $pdf->SetTextColor(0); // Reset text color
    
    // Action button cell
    $pdf->SetFillColor(255, 193, 7);
    $pdf->Cell(20, 7, 'Follow Up', 1, 1, 'C', true);
    
    // Reset fill color for next row
    $pdf->SetFillColor(240, 240, 240);
    $fill = !$fill;
}

// Recommendations section
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'RECOMMENDATIONS', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);

$pdf->WriteHTML('<p><b>1. Follow Up on Aging Orders</b>: Orders pending for more than 7 days require immediate attention. Contact customers to confirm their intent to complete the purchase.</p>');

$pdf->WriteHTML('<p><b>2. Review Payment Methods</b>: Consider simplifying payment processes for methods with high pending rates.</p>');

$pdf->WriteHTML('<p><b>3. Payment Reminders</b>: Implement an automated reminder system for orders pending more than 3 days.</p>');

$pdf->WriteHTML('<p><b>4. Order Cancellation Policy</b>: Consider implementing an automatic cancellation policy for orders pending more than 14 days without customer response.</p>');

$pdf->Ln(10);

// Action plan
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'ACTION PLAN', 0, 1, 'L');

// Table header for action plan
$pdf->SetFillColor(255, 193, 7);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(65, 7, 'Action Item', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Responsibility', 1, 0, 'C', 1);
$pdf->Cell(65, 7, 'Target Date', 1, 1, 'C', 1);

// Reset text color
$pdf->SetFont('helvetica', '', 10);

// Table rows for action plan
$pdf->Cell(65, 7, 'Contact customers with 7+ day pending orders', 1, 0, 'L');
$pdf->Cell(65, 7, 'Customer Service Team', 1, 0, 'C');
$pdf->Cell(65, 7, date('Y-m-d', strtotime('+1 day')), 1, 1, 'C');

$pdf->Cell(65, 7, 'Review and optimize payment processing', 1, 0, 'L');
$pdf->Cell(65, 7, 'Finance Department', 1, 0, 'C');
$pdf->Cell(65, 7, date('Y-m-d', strtotime('+7 days')), 1, 1, 'C');

$pdf->Cell(65, 7, 'Implement payment reminder system', 1, 0, 'L');
$pdf->Cell(65, 7, 'IT Department', 1, 0, 'C');
$pdf->Cell(65, 7, date('Y-m-d', strtotime('+14 days')), 1, 1, 'C');

// Output the PDF
$pdf->Output('pending_orders_report.pdf', 'I');
?>