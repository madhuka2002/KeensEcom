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
if(!isset($_SESSION['auditor_id']) && !isset($_SESSION['admin_id'])){
   die('Unauthorized access');
}

// Create a basic custom PDF class
class StaffReportPDF extends TCPDF {
    public function Header() {
        // Set background color
        $this->SetFillColor(76, 175, 80); // Green header
        $this->Rect(0, 0, $this->getPageWidth(), 20, 'F');
        
        // Title
        $this->SetY(5);
        $this->SetFont('helvetica', 'B', 16);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 10, 'WAREHOUSE STAFF PERFORMANCE REPORT', 0, 1, 'C');
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

// Get staff data and performance metrics
try {
    // Get staff list
    $staff_query = $conn->prepare("
        SELECT * FROM warehouse_staff 
        ORDER BY name ASC
    ");
    $staff_query->execute();
    $staff_list = $staff_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get task statistics per staff member
    $task_stats_query = $conn->prepare("
        SELECT 
            staff_id,
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_tasks,
            SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority_tasks
        FROM staff_tasks
        GROUP BY staff_id
    ");
    $task_stats_query->execute();
    $task_stats = $task_stats_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert to associative array with staff_id as key
    $staff_task_stats = [];
    foreach ($task_stats as $stat) {
        $staff_task_stats[$stat['staff_id']] = $stat;
    }
    
    // Get recent tasks
    $recent_tasks_query = $conn->prepare("
        SELECT 
            t.*,
            w.name as staff_name
        FROM staff_tasks t
        JOIN warehouse_staff w ON t.staff_id = w.staff_id
        ORDER BY t.created_at DESC
        LIMIT 20
    ");
    $recent_tasks_query->execute();
    $recent_tasks = $recent_tasks_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get shift change requests
    $shift_changes_query = $conn->prepare("
        SELECT 
            s.*,
            w.name as staff_name
        FROM shift_change_requests s
        JOIN warehouse_staff w ON s.staff_id = w.staff_id
        ORDER BY s.created_at DESC
        LIMIT 15
    ");
    $shift_changes_query->execute();
    $shift_changes = $shift_changes_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get staff availability stats
    $availability_query = $conn->prepare("
        SELECT 
            shift,
            COUNT(*) as staff_count,
            SUM(CASE WHEN is_available = 1 THEN 1 ELSE 0 END) as available_count
        FROM warehouse_staff
        GROUP BY shift
    ");
    $availability_query->execute();
    $availability_stats = $availability_query->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Create PDF document
$pdf = new StaffReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Keens.lk');
$pdf->SetAuthor('Warehouse Management');
$pdf->SetTitle('Warehouse Staff Performance Report');

// Set margins
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(15);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20);

// Add a page
$pdf->AddPage();

// Report date
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 10, 'Report generated on: ' . date('F d, Y h:i A'), 0, 1, 'R');
$pdf->Ln(5);

// Staff Summary Section
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 10, 'Staff Summary', 0, 1);

// Overall stats
$pdf->SetFillColor(245, 245, 245);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(120, 8, 'Total Staff Members', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 8, count($staff_list), 1, 1, 'C');

$available_staff = 0;
foreach ($staff_list as $staff) {
    if ($staff['is_available'] == 1) {
        $available_staff++;
    }
}

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(120, 8, 'Currently Available Staff', 1, 0, 'L', true);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 8, $available_staff . ' (' . round(($available_staff / count($staff_list)) * 100) . '%)', 1, 1, 'C');

// Staff by shift stats
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 10, 'Staff by Shift', 0, 1);

$pdf->SetFillColor(76, 175, 80);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(60, 8, 'Shift', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Total Staff', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Available Staff', 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetFillColor(245, 245, 245);

$fill = false;
foreach ($availability_stats as $shift) {
    $pdf->Cell(60, 7, ucfirst($shift['shift']), 1, 0, 'C', $fill);
    $pdf->Cell(60, 7, $shift['staff_count'], 1, 0, 'C', $fill);
    $pdf->Cell(60, 7, $shift['available_count'] . ' (' . round(($shift['available_count'] / $shift['staff_count']) * 100) . '%)', 1, 1, 'C', $fill);
    $fill = !$fill;
}

// Staff Performance Section
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 10, 'Staff Performance', 0, 1);

// Staff Performance Table
$pdf->SetFillColor(76, 175, 80);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(40, 8, 'Staff Name', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Shift', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Total Tasks', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Completed', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Pending', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Overdue', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'Rate', 1, 1, 'C', true);

$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(245, 245, 245);

$fill = false;
foreach ($staff_list as $staff) {
    // Get task stats for this staff member
    $stats = isset($staff_task_stats[$staff['staff_id']]) ? $staff_task_stats[$staff['staff_id']] : [
        'total_tasks' => 0,
        'completed_tasks' => 0,
        'pending_tasks' => 0,
        'overdue_tasks' => 0
    ];
    
    // Calculate completion rate
    $completion_rate = $stats['total_tasks'] > 0 ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100) : 0;
    
    // Output row
    $pdf->Cell(40, 7, substr($staff['name'], 0, 20), 1, 0, 'L', $fill);
    $pdf->Cell(25, 7, ucfirst($staff['shift']), 1, 0, 'C', $fill);
    $pdf->Cell(25, 7, $stats['total_tasks'] ?? 0, 1, 0, 'C', $fill);
    
    // Color-code completion numbers
    $pdf->SetTextColor(40, 167, 69); // Green for completed
    $pdf->Cell(25, 7, $stats['completed_tasks'] ?? 0, 1, 0, 'C', $fill);
    
    $pdf->SetTextColor(255, 153, 0); // Orange for pending
    $pdf->Cell(25, 7, $stats['pending_tasks'] ?? 0, 1, 0, 'C', $fill);
    
    $pdf->SetTextColor(220, 53, 69); // Red for overdue
    $pdf->Cell(25, 7, $stats['overdue_tasks'] ?? 0, 1, 0, 'C', $fill);
    
    // Color-code completion rate
    if ($completion_rate >= 80) {
        $pdf->SetTextColor(40, 167, 69); // Green for good
    } elseif ($completion_rate >= 50) {
        $pdf->SetTextColor(255, 153, 0); // Orange for average
    } else {
        $pdf->SetTextColor(220, 53, 69); // Red for poor
    }
    
    $pdf->Cell(15, 7, $completion_rate . '%', 1, 1, 'C', $fill);
    $pdf->SetTextColor(0, 0, 0);
    
    $fill = !$fill;
    
    // Add page break if needed
    if ($pdf->GetY() > 250 && $staff !== end($staff_list)) {
        $pdf->AddPage();
        
        // Reprint table header
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(76, 175, 80);
        $pdf->Cell(0, 10, 'Staff Performance (Continued)', 0, 1);
        
        $pdf->SetFillColor(76, 175, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(40, 8, 'Staff Name', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Shift', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Total Tasks', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Completed', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Pending', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Overdue', 1, 0, 'C', true);
        $pdf->Cell(15, 8, 'Rate', 1, 1, 'C', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetFillColor(245, 245, 245);
        $fill = false;
    }
}

// Top Performing Staff
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 10, 'Top Performing Staff', 0, 1);

// Sort staff_task_stats by completion rate
$sorted_stats = [];
foreach ($staff_list as $staff) {
    if (isset($staff_task_stats[$staff['staff_id']])) {
        $stats = $staff_task_stats[$staff['staff_id']];
        $completion_rate = $stats['total_tasks'] > 0 ? ($stats['completed_tasks'] / $stats['total_tasks']) : 0;
        $sorted_stats[] = [
            'staff_id' => $staff['staff_id'],
            'name' => $staff['name'],
            'shift' => $staff['shift'],
            'total_tasks' => $stats['total_tasks'],
            'completed_tasks' => $stats['completed_tasks'],
            'completion_rate' => $completion_rate
        ];
    }
}

// Sort by completion rate (descending)
usort($sorted_stats, function($a, $b) {
    return $b['completion_rate'] <=> $a['completion_rate'];
});

// Take top 5
$top_performers = array_slice($sorted_stats, 0, 5);

if (count($top_performers) > 0) {
    // Top Performers Table
    $pdf->SetFillColor(76, 175, 80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'Staff Name', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Shift', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Completed Tasks', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Completion Rate', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetFillColor(245, 245, 245);
    
    $fill = false;
    foreach ($top_performers as $performer) {
        $pdf->Cell(60, 7, $performer['name'], 1, 0, 'L', $fill);
        $pdf->Cell(40, 7, ucfirst($performer['shift']), 1, 0, 'C', $fill);
        $pdf->Cell(40, 7, $performer['completed_tasks'] . ' / ' . $performer['total_tasks'], 1, 0, 'C', $fill);
        $pdf->SetTextColor(40, 167, 69); // Green for completion rate
        $pdf->Cell(40, 7, round($performer['completion_rate'] * 100) . '%', 1, 1, 'C', $fill);
        $pdf->SetTextColor(0, 0, 0);
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'No task performance data available.', 0, 1);
}

// Recent Tasks Section
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 10, 'Recent Tasks', 0, 1);

if (count($recent_tasks) > 0) {
    // Recent Tasks Table
    $pdf->SetFillColor(76, 175, 80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(40, 8, 'Task Name', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Assigned To', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Priority', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Status', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Due Date', 1, 0, 'C', true);
    $pdf->Cell(20, 8, 'Created', 1, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetFillColor(245, 245, 245);
    
    $fill = false;
    foreach ($recent_tasks as $index => $task) {
        // Limit to 10 tasks
        if ($index >= 10) break;
        
        $pdf->Cell(40, 7, substr($task['task_name'], 0, 20), 1, 0, 'L', $fill);
        $pdf->Cell(40, 7, substr($task['staff_name'], 0, 20), 1, 0, 'L', $fill);
        
        // Color-code priority
        if ($task['priority'] == 'high') {
            $pdf->SetTextColor(220, 53, 69); // Red for high
        } elseif ($task['priority'] == 'medium') {
            $pdf->SetTextColor(255, 153, 0); // Orange for medium
        } else {
            $pdf->SetTextColor(40, 167, 69); // Green for low
        }
        
        $pdf->Cell(25, 7, ucfirst($task['priority']), 1, 0, 'C', $fill);
        
        // Color-code status
        if ($task['status'] == 'completed') {
            $pdf->SetTextColor(40, 167, 69); // Green for completed
        } elseif ($task['status'] == 'pending') {
            $pdf->SetTextColor(255, 153, 0); // Orange for pending
        } elseif ($task['status'] == 'overdue') {
            $pdf->SetTextColor(220, 53, 69); // Red for overdue
        }
        
        $pdf->Cell(25, 7, ucfirst($task['status']), 1, 0, 'C', $fill);
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Cell(30, 7, date('M d, Y', strtotime($task['due_date'])), 1, 0, 'C', $fill);
        $pdf->Cell(20, 7, date('M d', strtotime($task['created_at'])), 1, 1, 'C', $fill);
        
        $fill = !$fill;
    }
} else {
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 10, 'No recent tasks found.', 0, 1);
}

// Recommendations
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(76, 175, 80);
$pdf->Cell(0, 10, 'Observations & Recommendations', 0, 1);

$pdf->SetFillColor(245, 245, 245);
$pdf->Rect(15, $pdf->GetY(), 180, 90, 'F');

$pdf->SetY($pdf->GetY() + 5);
$pdf->SetX(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 8, 'Key Observations:', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetX(25);

// Calculate overall completion rate
$total_completed = 0;
$total_all_tasks = 0;
foreach ($staff_task_stats as $stats) {
    $total_completed += $stats['completed_tasks'];
    $total_all_tasks += $stats['total_tasks'];
}
$overall_completion = $total_all_tasks > 0 ? round(($total_completed / $total_all_tasks) * 100) : 0;

$pdf->MultiCell(170, 6, "1. Overall task completion rate: {$overall_completion}%", 0, 'L');

// Calculate shift with highest availability
$highest_avail_shift = '';
$highest_avail_rate = 0;
foreach ($availability_stats as $shift) {
    $avail_rate = $shift['staff_count'] > 0 ? ($shift['available_count'] / $shift['staff_count']) : 0;
    if ($avail_rate > $highest_avail_rate) {
        $highest_avail_rate = $avail_rate;
        $highest_avail_shift = $shift['shift'];
    }
}

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "2. " . ucfirst($highest_avail_shift) . " shift has the highest staff availability rate at " . round($highest_avail_rate * 100) . "%.", 0, 'L');

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "3. " . count($top_performers) . " staff members have a task completion rate above 80%.", 0, 'L');

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "4. Currently " . $available_staff . " out of " . count($staff_list) . " staff members are available for assignment.", 0, 'L');

// Recommendations
$pdf->SetY($pdf->GetY() + 5);
$pdf->SetX(20);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Recommendations:', 0, 1);

$pdf->SetFont('helvetica', '', 10);

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "1. Recognize top-performing staff members to maintain motivation and set examples for others.", 0, 'L');

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "2. Review task allocation for staff with low completion rates to identify potential issues or training needs.", 0, 'L');

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "3. Optimize staff scheduling to ensure adequate coverage across all shifts, particularly during peak operational hours.", 0, 'L');

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "4. Implement a standardized task prioritization system to ensure critical warehouse operations are completed first.", 0, 'L');

$pdf->SetX(25);
$pdf->MultiCell(170, 6, "5. Consider providing additional training to staff members with consistently overdue tasks.", 0, 'L');

// Output PDF
$pdf->Output('warehouse_staff_report.pdf', 'I');
?>