<?php
// coaching/reports.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$access_level = getUserAccessLevel();
$is_manager = ($access_level === 'manager');

// Only managers can access reports
if (!$is_manager) {
    die('Access denied. Manager privileges required.');
}

// Get filter parameters
$report_type = isset($_GET['type']) ? $_GET['type'] : 'summary';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
$supervisor_filter = isset($_GET['supervisor']) ? $_GET['supervisor'] : '';
$agent_filter = isset($_GET['agent']) ? $_GET['agent'] : '';

// Build WHERE clause
$where_parts = ["1=1"];
if ($date_from) {
    $date_from_safe = $conn->real_escape_string($date_from);
    $where_parts[] = "cs.session_date >= '$date_from_safe'";
}
if ($date_to) {
    $date_to_safe = $conn->real_escape_string($date_to);
    $where_parts[] = "cs.session_date <= '$date_to_safe'";
}
if ($supervisor_filter) {
    $supervisor_safe = $conn->real_escape_string($supervisor_filter);
    $where_parts[] = "cs.supervisor_id = '$supervisor_safe'";
}
if ($agent_filter) {
    $agent_safe = $conn->real_escape_string($agent_filter);
    $where_parts[] = "cs.agent_id = '$agent_safe'";
}
$where_clause = implode(' AND ', $where_parts);

// Get supervisors list for filter
$supervisors_query = "SELECT DISTINCT e.EmployeeID, e.FirstName, e.LastName 
                      FROM Employees e
                      INNER JOIN coaching_sessions cs ON e.EmployeeID = cs.supervisor_id
                      ORDER BY e.FirstName, e.LastName";
$supervisors = $conn->query($supervisors_query);

// Get agents list for filter
$agents_query = "SELECT DISTINCT e.EmployeeID, e.FirstName, e.LastName 
                 FROM Employees e
                 INNER JOIN coaching_sessions cs ON e.EmployeeID = cs.agent_id
                 ORDER BY e.FirstName, e.LastName";
$agents = $conn->query($agents_query);

// Generate report data based on type
$report_data = null;
$report_title = '';

switch ($report_type) {
    case 'summary':
        $report_title = 'Coaching Summary Report';
        $summary_query = "SELECT 
                          COUNT(*) as total_sessions,
                          COUNT(DISTINCT cs.agent_id) as unique_agents,
                          COUNT(DISTINCT cs.supervisor_id) as unique_supervisors,
                          SUM(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) as completed,
                          SUM(CASE WHEN cs.status = 'pending_followup' THEN 1 ELSE 0 END) as pending,
                          SUM(CASE WHEN cs.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                          FROM coaching_sessions cs
                          WHERE $where_clause";
        $report_data = $conn->query($summary_query)->fetch_assoc();
        break;
        
    case 'by_supervisor':
        $report_title = 'Coaching by Supervisor';
        $supervisor_query = "SELECT 
                             CONCAT(e.FirstName, ' ', e.LastName) as supervisor_name,
                             e.Email as supervisor_email,
                             COUNT(*) as total_sessions,
                             AVG(CASE WHEN cs.status = 'completed' THEN 1 ELSE 0 END) * 100 as completion_rate
                             FROM coaching_sessions cs
                             LEFT JOIN Employees e ON cs.supervisor_id = e.EmployeeID
                             WHERE $where_clause
                             GROUP BY e.EmployeeID, e.FirstName, e.LastName, e.Email
                             ORDER BY total_sessions DESC";
        $report_data = $conn->query($supervisor_query);
        break;
        
    case 'by_agent':
        $report_title = 'Coaching by Agent';
        $agent_query = "SELECT 
                        CONCAT(e.FirstName, ' ', e.LastName) as agent_name,
                        e.Email as agent_email,
                        COUNT(*) as total_sessions,
                        MAX(cs.session_date) as last_session,
                        SUM(CASE WHEN cs.status = 'pending_followup' THEN 1 ELSE 0 END) as pending
                        FROM coaching_sessions cs
                        LEFT JOIN Employees e ON cs.agent_id = e.EmployeeID
                        WHERE $where_clause
                        GROUP BY e.EmployeeID, e.FirstName, e.LastName, e.Email
                        ORDER BY total_sessions DESC";
        $report_data = $conn->query($agent_query);
        break;
        
    case 'by_type':
        $report_title = 'Coaching by Type';
        $type_query = "SELECT 
                       cs.coaching_type,
                       COUNT(*) as count,
                       ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 1) as percentage
                       FROM coaching_sessions cs
                       WHERE $where_clause
                       GROUP BY cs.coaching_type
                       ORDER BY count DESC";
        $report_data = $conn->query($type_query);
        break;
        
    case 'detailed':
        $report_title = 'Detailed Sessions Report';
        $detailed_query = "SELECT 
                          cs.id,
                          cs.session_date,
                          cs.session_time,
                          CONCAT(a.FirstName, ' ', a.LastName) as agent_name,
                          CONCAT(s.FirstName, ' ', s.LastName) as supervisor_name,
                          cs.coaching_type,
                          cs.topic,
                          cs.status,
                          cs.follow_up_date
                          FROM coaching_sessions cs
                          LEFT JOIN Employees a ON cs.agent_id = a.EmployeeID
                          LEFT JOIN Employees s ON cs.supervisor_id = s.EmployeeID
                          WHERE $where_clause
                          ORDER BY cs.session_date DESC, cs.session_time DESC";
        $report_data = $conn->query($detailed_query);
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coaching Reports - Cohere</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="top-nav">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        <span class="nav-title">🎯 Coaching Portal</span>
        <a href="index.php">Dashboard</a>
        <a href="new_session.php">New Session</a>
        <a href="all_sessions.php">All Sessions</a>
        <a href="agents.php">Agents</a>
        <?php if ($is_manager): ?>
            <a href="reports.php" class="active">Reports</a>
        <?php endif; ?>
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <div class="reports-header">
            <h1>Coaching Reports</h1>
            <p>Generate and analyze coaching data across your organization</p>
        </div>

        <div class="report-filters">
            <h3 class="filter-section-title">Report Type</h3>
            <div class="report-type-tabs">
                <a href="?type=summary&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                   class="report-tab <?php echo $report_type == 'summary' ? 'active' : ''; ?>">
                    📊 Summary
                </a>
                <a href="?type=by_supervisor&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                   class="report-tab <?php echo $report_type == 'by_supervisor' ? 'active' : ''; ?>">
                    👥 By Supervisor
                </a>
                <a href="?type=by_agent&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                   class="report-tab <?php echo $report_type == 'by_agent' ? 'active' : ''; ?>">
                    👤 By Agent
                </a>
                <a href="?type=by_type&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                   class="report-tab <?php echo $report_type == 'by_type' ? 'active' : ''; ?>">
                    📋 By Type
                </a>
                <a href="?type=detailed&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" 
                   class="report-tab <?php echo $report_type == 'detailed' ? 'active' : ''; ?>">
                    📄 Detailed
                </a>
            </div>

            <h3 class="filter-section-title filter-spacing">Filters</h3>
            <form method="GET" action="">
                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                <div class="filter-grid">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="form-group">
                        <label>Supervisor</label>
                        <select name="supervisor">
                            <option value="">All Supervisors</option>
                            <?php 
                            if ($supervisors):
                                while ($sup = $supervisors->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $sup['EmployeeID']; ?>" 
                                        <?php echo $supervisor_filter == $sup['EmployeeID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sup['FirstName'] . ' ' . $sup['LastName']); ?>
                                </option>
                            <?php 
                                endwhile;
                            endif; 
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Agent</label>
                        <select name="agent">
                            <option value="">All Agents</option>
                            <?php 
                            if ($agents):
                                while ($agt = $agents->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $agt['EmployeeID']; ?>" 
                                        <?php echo $agent_filter == $agt['EmployeeID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($agt['FirstName'] . ' ' . $agt['LastName']); ?>
                                </option>
                            <?php 
                                endwhile;
                            endif; 
                            ?>
                        </select>
                    </div>
                </div>
                <div class="report-filter-buttons">
                    <button type="submit" class="btn btn-primary">🔍 Generate Report</button>
                    <a href="reports.php?type=<?php echo $report_type; ?>" class="btn btn-secondary">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="report-content">
            <div class="report-header-row">
                <h2><?php echo $report_title; ?></h2>
                <div class="export-buttons">
                    <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
                    <?php if ($report_type !== 'summary'): ?>
                        <button onclick="exportToCSV()" class="btn btn-secondary">📥 Export CSV</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="report-period">
                <strong>Period:</strong> <?php echo date('M d, Y', strtotime($date_from)); ?> 
                to <?php echo date('M d, Y', strtotime($date_to)); ?>
            </div>

            <?php if ($report_type == 'summary' && $report_data): ?>
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total Sessions</h3>
                        <div class="value"><?php echo $report_data['total_sessions']; ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Unique Agents</h3>
                        <div class="value"><?php echo $report_data['unique_agents']; ?></div>
                    </div>
                    <div class="summary-card">
                        <h3>Supervisors</h3>
                        <div class="value"><?php echo $report_data['unique_supervisors']; ?></div>
                    </div>
                    <div class="summary-card completed">
                        <h3>Completed</h3>
                        <div class="value"><?php echo $report_data['completed']; ?></div>
                    </div>
                    <div class="summary-card pending">
                        <h3>Pending</h3>
                        <div class="value"><?php echo $report_data['pending']; ?></div>
                    </div>
                    <div class="summary-card cancelled">
                        <h3>Cancelled</h3>
                        <div class="value"><?php echo $report_data['cancelled']; ?></div>
                    </div>
                </div>

            <?php elseif ($report_type == 'by_supervisor' && $report_data && $report_data->num_rows > 0): ?>
                <table class="report-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>Supervisor Name</th>
                            <th>Email</th>
                            <th>Total Sessions</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['supervisor_email']); ?></td>
                                <td><?php echo $row['total_sessions']; ?></td>
                                <td><?php echo number_format($row['completion_rate'], 1); ?>%</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type == 'by_agent' && $report_data && $report_data->num_rows > 0): ?>
                <table class="report-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>Agent Name</th>
                            <th>Email</th>
                            <th>Total Sessions</th>
                            <th>Last Session</th>
                            <th>Pending Follow-ups</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['agent_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['agent_email']); ?></td>
                                <td><?php echo $row['total_sessions']; ?></td>
                                <td><?php echo $row['last_session'] ? date('M d, Y', strtotime($row['last_session'])) : 'Never'; ?></td>
                                <td class="pending-cell <?php echo $row['pending'] > 0 ? 'has-pending' : ''; ?>">
                                    <?php echo $row['pending']; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type == 'by_type' && $report_data && $report_data->num_rows > 0): ?>
                <table class="report-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>Coaching Type</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo $row['coaching_type']; ?>">
                                        <?php echo COACHING_TYPES[$row['coaching_type']]; ?>
                                    </span>
                                </td>
                                <td><?php echo $row['count']; ?></td>
                                <td>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar">
                                            <div class="progress-bar-fill" style="width: <?php echo $row['percentage']; ?>%;"></div>
                                        </div>
                                        <span class="progress-percentage"><?php echo $row['percentage']; ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php elseif ($report_type == 'detailed' && $report_data && $report_data->num_rows > 0): ?>
                <table class="report-table" id="reportTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Agent</th>
                            <th>Supervisor</th>
                            <th>Type</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Follow-up</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_data->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['session_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($row['session_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['agent_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['supervisor_name']); ?></td>
                                <td><span class="badge badge-<?php echo $row['coaching_type']; ?>">
                                    <?php echo COACHING_TYPES[$row['coaching_type']]; ?>
                                </span></td>
                                <td><?php echo htmlspecialchars($row['topic']); ?></td>
                                <td><span class="status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span></td>
                                <td><?php echo $row['follow_up_date'] ? date('M d, Y', strtotime($row['follow_up_date'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <div class="no-data-message">
                    <p>📊</p>
                    <h3>No data available for this report</h3>
                    <p>Try adjusting your filters or date range</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportToCSV() {
            const table = document.getElementById('reportTable');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (let row of rows) {
                let cols = row.querySelectorAll('td, th');
                let csvRow = [];
                for (let col of cols) {
                    csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
                }
                csv.push(csvRow.join(','));
            }
            
            const csvString = csv.join('\n');
            const blob = new Blob([csvString], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'coaching_report_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>