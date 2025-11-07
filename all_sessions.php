<?php
// coaching/all_sessions.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$supervisor_id = $currentUser['EmployeeID'];

// Get filter parameters
$filter_agent = isset($_GET['agent']) ? $conn->real_escape_string($_GET['agent']) : '';
$filter_type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
$filter_status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$filter_date_from = isset($_GET['date_from']) ? $conn->real_escape_string($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? $conn->real_escape_string($_GET['date_to']) : '';

// Build query with filters
$where_clauses = ["cs.supervisor_id = '$supervisor_id'"];

if ($filter_agent) {
    $where_clauses[] = "cs.agent_id = '$filter_agent'";
}
if ($filter_type) {
    $where_clauses[] = "cs.coaching_type = '$filter_type'";
}
if ($filter_status) {
    $where_clauses[] = "cs.status = '$filter_status'";
}
if ($filter_date_from) {
    $where_clauses[] = "cs.session_date >= '$filter_date_from'";
}
if ($filter_date_to) {
    $where_clauses[] = "cs.session_date <= '$filter_date_to'";
}

$where_sql = implode(' AND ', $where_clauses);

$sessions_query = "SELECT cs.*, 
                   CONCAT(e.FirstName, ' ', e.LastName) as agent_name
                   FROM coaching_sessions cs
                   LEFT JOIN Employees e ON cs.agent_id = e.EmployeeID
                   WHERE $where_sql
                   ORDER BY cs.session_date DESC, cs.session_time DESC";
$sessions = $conn->query($sessions_query);

// Get agents for filter dropdown (only those with sessions)
$agents_query = "SELECT DISTINCT e.EmployeeID, e.FirstName, e.LastName
                 FROM Employees e
                 INNER JOIN coaching_sessions cs ON e.EmployeeID = cs.agent_id
                 WHERE cs.supervisor_id = '$supervisor_id'
                 ORDER BY e.FirstName, e.LastName";
$agents = $conn->query($agents_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Coaching Sessions - Cohere</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="top-nav">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        <span class="nav-title">🎯 Coaching Portal</span>
        <a href="index.php">Dashboard</a>
        <a href="new_session.php">New Session</a>
        <a href="all_sessions.php" class="active">All Sessions</a>
        <a href="agents.php">Agents</a>
        <?php if (getUserAccessLevel() === 'manager'): ?>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <div class="sessions-header-banner">
            <h1>All Coaching Sessions</h1>
            <p>View and manage all coaching sessions</p>
        </div>

        <div class="filters-container">
            <h3>🔍 Filter Sessions</h3>
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <label for="agent">Agent</label>
                    <select name="agent" id="agent">
                        <option value="">All Agents</option>
                        <?php 
                        if ($agents && $agents->num_rows > 0):
                            while ($agent = $agents->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $agent['EmployeeID']; ?>" 
                                    <?php echo $filter_agent == $agent['EmployeeID'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent['FirstName'] . ' ' . $agent['LastName']); ?>
                            </option>
                        <?php 
                            endwhile;
                        endif; 
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="type">Coaching Type</label>
                    <select name="type" id="type">
                        <option value="">All Types</option>
                        <?php foreach (COACHING_TYPES as $key => $value): ?>
                            <option value="<?php echo $key; ?>" 
                                    <?php echo $filter_type == $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending_followup" <?php echo $filter_status == 'pending_followup' ? 'selected' : ''; ?>>Pending Follow-up</option>
                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date_from">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo $filter_date_from; ?>">
                </div>

                <div class="filter-group">
                    <label for="date_to">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo $filter_date_to; ?>">
                </div>

                <div class="filter-group">
                    <button type="submit" class="btn btn-primary filter-btn">Apply Filters</button>
                </div>

                <div class="filter-group">
                    <a href="all_sessions.php" class="btn btn-secondary filter-btn">Clear Filters</a>
                </div>
            </form>
        </div>

        <div class="sessions-table-wrapper">
            <div class="sessions-count">
                <strong><?php echo $sessions ? $sessions->num_rows : 0; ?></strong> session(s) found
            </div>

            <?php if ($sessions && $sessions->num_rows > 0): ?>
                <table class="sessions-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Agent</th>
                            <th>Type</th>
                            <th>Topic</th>
                            <th>Status</th>
                            <th>Follow-up</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($session = $sessions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo date('M d, Y', strtotime($session['session_date'])); ?>
                                    <br><span class="table-time"><?php echo date('g:i A', strtotime($session['session_time'])); ?></span>
                                </td>
                                <td>
                                    <a href="agent_profile.php?id=<?php echo $session['agent_id']; ?>" class="agent-link">
                                        <?php echo htmlspecialchars($session['agent_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $session['coaching_type']; ?>">
                                        <?php echo COACHING_TYPES[$session['coaching_type']]; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($session['topic']); ?></td>
                                <td>
                                    <span class="status-<?php echo $session['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $session['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($session['follow_up_date']) {
                                        echo date('M d, Y', strtotime($session['follow_up_date']));
                                    } else {
                                        echo '<span class="empty-cell">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="view_session.php?id=<?php echo $session['id']; ?>" class="btn-view">View</a>
                                    <a href="edit_session.php?id=<?php echo $session['id']; ?>" class="btn-edit">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <p>🔍 No coaching sessions found matching your filters.</p>
                    <a href="all_sessions.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>