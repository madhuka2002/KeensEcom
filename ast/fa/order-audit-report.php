<?php
// Add error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include required files
include '../components/connect.php';
require_once('../tcpdf/tcpdf.php');

session_start();

// Simple authentication check
if(!isset($_SESSION['staff_id']) && !isset($_SESSION['admin_id']) && !isset($_SESSION['auditor_id'])){
   die('Unauthorized access');
}

// Get date range for filtering (default: last 30 days)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

// Allow overriding default date range
if(isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $start_date = $_GET['date_from'];
}
if(isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $end_date = $_GET['date_to'];
}

// Create a custom PDF class for order management report
class OrderManagementReportPDF extends TCPDF {
    public function Header() {
        // Set background color
        $this->SetFillColor(67, 97, 238); // Primary color from CSS
        $this->Rect(0, 0, $this->getPageWidth(), 20, 'F');
        
        // Title
        $this->SetY(5);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'ORDER AUDIT REPORT', 0, 1, 'C');
    }

    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(128, 128, 128);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

// Get order statistics
try {
    // Order status counts
    $status_query = $conn->prepare("
        SELECT 
            order_status,
            COUNT(*) as count,
            SUM(total_price) as total_value
        FROM orders
        WHERE placed_on BETWEEN ? AND ?
        GROUP BY order_status
    ");
    $status_query->execute([$start_date, $end_date]);
    $status_counts = $status_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array for easier access
    $status_data = [];
    $total_orders = 0;
    $total_value = 0;
    
    foreach ($status_counts as $status) {
        $status_data[$status['order_status']] = [
            'count' => $status['count'],
            'value' => $status['total_value']
        ];
        $total_orders += $status['count'];
        $total_value += $status['total_value'];
    }
    
    // Calculate processing times
    $processing_times_query = $conn->prepare("
        SELECT 
            AVG(TIMESTAMPDIFF(HOUR, placed_on, 
                CASE 
                    WHEN order_status IN ('delivered', 'shipped') THEN placed_on 
                    ELSE NOW() 
                END
            )) as avg_processing_time
        FROM orders
        WHERE placed_on BETWEEN ? AND ?
        AND order_status != 'pending'
    ");
    $processing_times_query->execute([$start_date, $end_date]);
    $processing_times = $processing_times_query->fetch(PDO::FETCH_ASSOC);
    
    // Top customers
    $top_customers_query = $conn->prepare("
        SELECT 
            o.user_id,
            u.name as customer_name,
            COUNT(o.id) as order_count,
            SUM(o.total_price) as total_spent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.placed_on BETWEEN ? AND ?
        GROUP BY o.user_id
        ORDER BY total_spent DESC
        LIMIT 5
    ");
    $top_customers_query->execute([$start_date, $end_date]);
    $top_customers = $top_customers_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily order trends
    $daily_trends_query = $conn->prepare("
        SELECT 
            DATE(placed_on) as order_date,
            COUNT(*) as order_count,
            SUM(total_price) as daily_total
        FROM orders
        WHERE placed_on BETWEEN ? AND ?
        GROUP BY DATE(placed_on)
        ORDER BY order_date DESC
        LIMIT 7
    ");
    $daily_trends_query->execute([$start_date, $end_date]);
    $daily_trends = $daily_trends_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent orders
    $recent_orders_query = $conn->prepare("
        SELECT 
            o.*,
            u.name as customer_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.placed_on BETWEEN ? AND ?
        ORDER BY o.placed_on DESC
        LIMIT 15
    ");
    $recent_orders_query->execute([$start_date, $end_date]);
    $recent_orders = $recent_orders_query->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Create PDF document
$pdf = new OrderManagementReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Keens.lk');
$pdf->SetAuthor('Warehouse Audit');
$pdf->SetTitle('Order Audit Report');
$pdf->SetSubject('Order Processing and Status');

// Set margins
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20);

// Add a page
$pdf->AddPage();

// Report date and period
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, 'Report Generated: ' . date('F d, Y h:i A'), 0, 1, 'R');
$pdf->Cell(0, 5, 'Period: ' . date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date)), 0, 1, 'R');
$pdf->Ln(10);

// Order Summary Section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 97, 238); // Primary color
$pdf->Cell(0, 10, 'ORDER SUMMARY', 0, 1);

// Summary Table
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(120, 8, 'Total Orders', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 8, number_format($total_orders), 1, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(120, 8, 'Total Order Value', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 8, '$' . number_format($total_value, 2), 1, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(120, 8, 'Average Order Value', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$avg_order_value = $total_orders > 0 ? $total_value / $total_orders : 0;
$pdf->Cell(60, 8, '$' . number_format($avg_order_value, 2), 1, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(120, 8, 'Average Processing Time', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$avg_hours = isset($processing_times['avg_processing_time']) ? $processing_times['avg_processing_time'] : 0;
$pdf->Cell(60, 8, number_format($avg_hours, 1) . ' hours', 1, 1, 'R');

$pdf->Ln(5);

// Orders by Status Section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 97, 238);
$pdf->Cell(0, 10, 'ORDERS BY STATUS', 0, 1);

// Order Status Table
$pdf->SetFillColor(67, 97, 238);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 8, 'Status', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Count', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Percentage', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Value', 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(245, 245, 245);

$fill = false;
$statuses = ['pending', 'processing', 'picked_up', 'shipped', 'delivered'];
foreach ($statuses as $status_name) {
    $count = isset($status_data[$status_name]) ? $status_data[$status_name]['count'] : 0;
    $value = isset($status_data[$status_name]) ? $status_data[$status_name]['value'] : 0;
    $percentage = $total_orders > 0 ? ($count / $total_orders) * 100 : 0;
    
    $pdf->Cell(60, 7, ucfirst($status_name), 1, 0, 'L', $fill);
    $pdf->Cell(40, 7, $count, 1, 0, 'C', $fill);
    $pdf->Cell(40, 7, number_format($percentage, 2) . '%', 1, 0, 'C', $fill);
    $pdf->Cell(40, 7, '$' . number_format($value, 2), 1, 1, 'R', $fill);
    
    $fill = !$fill;
}

// Daily Order Trends Section
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 97, 238);
$pdf->Cell(0, 10, 'DAILY ORDER TRENDS (LAST 7 DAYS)', 0, 1);

if (count($daily_trends) > 0) {
    // Daily Trends Table
    $pdf->SetFillColor(67, 97, 238);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'Date', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Orders', 1, 0, 'C', true);
    $pdf->Cell(80, 8, 'Total Value', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(245, 245, 245);
    
    $fill = false;
    foreach ($daily_trends as $day) {
        $pdf->Cell(60, 7, date('F d, Y', strtotime($day['order_date'])), 1, 0, 'L', $fill);
        $pdf->Cell(40, 7, $day['order_count'], 1, 0, 'C', $fill);
        $pdf->Cell(80, 7, '$' . number_format($day['daily_total'], 2), 1, 1, 'R', $fill);
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'No daily trend data available for the selected period.', 0, 1);
}

// Top Customers Section
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 97, 238);
$pdf->Cell(0, 10, 'TOP CUSTOMERS', 0, 1);

if (count($top_customers) > 0) {
    // Top Customers Table
    $pdf->SetFillColor(67, 97, 238);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 8, 'Customer', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Orders', 1, 0, 'C', true);
    $pdf->Cell(70, 8, 'Total Spent', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(245, 245, 245);
    
    $fill = false;
    foreach ($top_customers as $customer) {
        $pdf->Cell(80, 7, $customer['customer_name'], 1, 0, 'L', $fill);
        $pdf->Cell(30, 7, $customer['order_count'], 1, 0, 'C', $fill);
        $pdf->Cell(70, 7, '$' . number_format($customer['total_spent'], 2), 1, 1, 'R', $fill);
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'No customer data available for the selected period.', 0, 1);
}

$pdf->Ln(10);

// Recent Orders Section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 97, 238);
$pdf->Cell(0, 10, 'RECENT ORDERS', 0, 1);

if (count($recent_orders) > 0) {
    // Recent Orders Table
    $pdf->SetFillColor(67, 97, 238);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(20, 8, 'Order ID', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Customer', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Status', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Items', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Total', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Date', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(245, 245, 245);
    
    $fill = false;
    foreach ($recent_orders as $order) {
        $order_items = count(explode(',', $order['total_products']));
        
        $pdf->Cell(20, 7, '#' . $order['id'], 1, 0, 'C', $fill);
        $pdf->Cell(45, 7, substr($order['customer_name'], 0, 22), 1, 0, 'L', $fill);
        
        // Status with color coding
        if ($order['order_status'] == 'delivered') {
            $pdf->SetTextColor(40, 167, 69); // Green
        } elseif ($order['order_status'] == 'shipped') {
            $pdf->SetTextColor(0, 123, 255); // Blue
        } elseif ($order['order_status'] == 'picked_up') {
            $pdf->SetTextColor(255, 193, 7); // Yellow
        } elseif ($order['order_status'] == 'processing') {
            $pdf->SetTextColor(255, 153, 0); // Orange
        } else {
            $pdf->SetTextColor(108, 117, 125); // Gray
        }
        
        $pdf->Cell(30, 7, ucfirst($order['order_status']), 1, 0, 'C', $fill);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Cell(30, 7, $order_items, 1, 0, 'C', $fill);
        $pdf->Cell(30, 7, '$' . number_format($order['total_price'], 2), 1, 0, 'R', $fill);
        $pdf->Cell(25, 7, date('m/d/Y', strtotime($order['placed_on'])), 1, 1, 'C', $fill);
        
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'No recent orders available for the selected period.', 0, 1);
}

// Performance Metrics & Recommendations
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(67, 97, 238);
$pdf->Cell(0, 10, 'PERFORMANCE METRICS & RECOMMENDATIONS', 0, 1);

// Calculate performance metrics
$pending_percentage = isset($status_data['pending']) && $total_orders > 0 ? 
    ($status_data['pending']['count'] / $total_orders) * 100 : 0;
    
$processing_percentage = isset($status_data['processing']) && $total_orders > 0 ? 
    ($status_data['processing']['count'] / $total_orders) * 100 : 0;
    
$shipped_percentage = isset($status_data['shipped']) && $total_orders > 0 ? 
    ($status_data['shipped']['count'] / $total_orders) * 100 : 0;
    
$delivered_percentage = isset($status_data['delivered']) && $total_orders > 0 ? 
    ($status_data['delivered']['count'] / $total_orders) * 100 : 0;

// Performance Scorecard
$pdf->SetFillColor(245, 245, 245);
$pdf->Rect(15, $pdf->GetY(), 180, 45, 'F');
$pdf->SetY($pdf->GetY() + 5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetX(20);
$pdf->Cell(0, 8, 'Order Processing Performance', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetX(25);

// Create performance indicators with color coding
$processing_score = $avg_hours < 24 ? 'Excellent' : ($avg_hours < 48 ? 'Good' : 'Needs Improvement');
$processing_color = $avg_hours < 24 ? [40, 167, 69] : ($avg_hours < 48 ? [255, 153, 0] : [220, 53, 69]);

$pending_score = $pending_percentage < 10 ? 'Excellent' : ($pending_percentage < 20 ? 'Good' : 'Needs Improvement');
$pending_color = $pending_percentage < 10 ? [40, 167, 69] : ($pending_percentage < 20 ? [255, 153, 0] : [220, 53, 69]);

$pdf->Cell(110, 8, 'Average Order Processing Time:', 0, 0);
$pdf->SetTextColor($processing_color[0], $processing_color[1], $processing_color[2]);
$pdf->Cell(50, 8, number_format($avg_hours, 1) . ' hours (' . $processing_score . ')', 0, 1);

$pdf->SetX(25);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(110, 8, 'Pending Orders Percentage:', 0, 0);
$pdf->SetTextColor($pending_color[0], $pending_color[1], $pending_color[2]);
$pdf->Cell(50, 8, number_format($pending_percentage, 2) . '% (' . $pending_score . ')', 0, 1);

$pdf->SetX(25);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(110, 8, 'Shipped Orders Percentage:', 0, 0);
$pdf->Cell(50, 8, number_format($shipped_percentage, 2) . '%', 0, 1);

$pdf->SetX(25);
$pdf->Cell(110, 8, 'Delivered Orders Percentage:', 0, 0);
$pdf->Cell(50, 8, number_format($delivered_percentage, 2) . '%', 0, 1);

$pdf->Ln(10);

// Recommendations Section
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(67, 97, 238);
$pdf->Cell(0, 8, 'Recommendations for Improvement', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Dynamic recommendations based on metrics
$pdf->SetX(20);
$pdf->Cell(5, 6, '•', 0, 0);

if ($pending_percentage > 20) {
    $pdf->MultiCell(165, 6, 'Prioritize clearing the ' . number_format($pending_percentage, 2) . '% of pending orders to improve fulfillment rates and customer satisfaction.', 0, 'L');
} else {
    $pdf->MultiCell(165, 6, 'Maintain the current efficient order processing workflow that has kept pending orders at only ' . number_format($pending_percentage, 2) . '%.', 0, 'L');
}

$pdf->SetX(20);
$pdf->Cell(5, 6, '•', 0, 0);

if ($avg_hours > 48) {
    $pdf->MultiCell(165, 6, 'Reduce average processing time from ' . number_format($avg_hours, 1) . ' hours to under 24 hours by optimizing warehouse workflows and staff allocation.', 0, 'L');
} else {
    $pdf->MultiCell(165, 6, 'Continue to maintain processing times below ' . number_format($avg_hours, 1) . ' hours to ensure timely delivery and customer satisfaction.', 0, 'L');
}

$pdf->SetX(20);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->MultiCell(165, 6, 'Focus on developing strategies to increase the average order value from $' . number_format($avg_order_value, 2) . ' through product bundling and cross-selling opportunities.', 0, 'L');

$pdf->SetX(20);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->MultiCell(165, 6, 'Implement customer loyalty programs targeting the top spenders identified in this report to encourage repeat business.', 0, 'L');

$pdf->SetX(20);
$pdf->Cell(5, 6, '•', 0, 0);
$pdf->MultiCell(165, 6, 'Monitor daily order trends to better allocate staff resources during peak periods and ensure timely order fulfillment.', 0, 'L');

// Output PDF
$pdf->Output('order_audit_report.pdf', 'I');
?>