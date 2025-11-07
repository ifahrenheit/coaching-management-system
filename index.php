<?php
// coaching/index.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$supervisor_id = $currentUser['EmployeeID'];
$access_level = getUserAccessLevel();
$is_manager = ($access_level === 'manager');

// Build WHERE clause based on access level
if ($is_manager) {
    $where_clause = "1=1"; // Show all sessions
    $my_where = "supervisor_id = '$supervisor_id'"; // For "My Sessions" section
} else {
    $where_clause = "supervisor_id = '$supervisor_id'"; // Only their sessions
    $my_where = $where_clause;
}

// Get overall statistics
$total_sessions_query = "SELECT COUNT(*) as total FROM coaching_sessions 
                         WHERE $where_clause AND status != 'cancelled'";
$total_sessions_result = $conn->query($total_sessions_query);
$total_sessions = $total_sessions_result ? $total_sessions_result->fetch_assoc()['total'] : 0;

$this_month_query = "SELECT COUNT(*) as total FROM coaching_sessions 
                     WHERE $where_clause
                     AND MONTH(session_date) = MONTH(CURRENT_DATE())
                     AND YEAR(session_date) = YEAR(CURRENT_DATE())";
$this_month_result = $conn->query($this_month_query);
$this_month = $this_month_result ? $this_month_result->fetch_assoc()['total'] : 0;

$pending_followup_query = "SELECT COUNT(*) as total FROM coaching_sessions 
                           WHERE $where_clause
                           AND status = 'pending_followup'";
$pending_result = $conn->query($pending_followup_query);
$pending_followup = $pending_result ? $pending_result->fetch_assoc()['total'] : 0;

// For managers: Get team breakdown
$team_stats = null;
if ($is_manager) {
    $team_stats_query = "SELECT 
                         e.EmployeeID as supervisor_id,
                         CONCAT(e.FirstName, ' ', e.LastName) as supervisor_name,
                         e.Email as supervisor_email,
                         COUNT(cs.id) as total_sessions,
                         SUM(CASE WHEN MONTH(cs.session_date) = MONTH(CURRENT_DATE()) 
                             AND YEAR(cs.session_date) = YEAR(CURRENT_DATE()) THEN 1 ELSE 0 END) as this_month,
                         SUM(CASE WHEN cs.status = 'pending_followup' THEN 1 ELSE 0 END) as pending,
                         MAX(cs.session_date) as last_session_date
                         FROM coaching_sessions cs
                         LEFT JOIN Employees e ON cs.supervisor_id = e.EmployeeID
                         GROUP BY e.EmployeeID, e.FirstName, e.LastName, e.Email
                         ORDER BY total_sessions DESC";
    $team_stats = $conn->query($team_stats_query);
}

// Get recent sessions (limit to own sessions for supervisors)
$recent_query = "SELECT cs.*, 
                 e.FirstName, e.LastName,
                 CONCAT(e.FirstName, ' ', e.LastName) as agent_name,
                 sup.FirstName as sup_first, sup.LastName as sup_last,
                 CONCAT(sup.FirstName, ' ', sup.LastName) as supervisor_name
                 FROM coaching_sessions cs
                 LEFT JOIN Employees e ON cs.agent_id = e.EmployeeID
                 LEFT JOIN Employees sup ON cs.supervisor_id = sup.EmployeeID
                 WHERE " . ($is_manager ? "1=1" : $where_clause) . "
                 ORDER BY cs.session_date DESC, cs.session_time DESC
                 LIMIT 10";
$recent_sessions = $conn->query($recent_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coaching Portal - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="top-nav">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        <span class="nav-title">🎯 Coaching Portal</span>
        <a href="index.php" class="active">Dashboard</a>
        <a href="new_session.php">New Session</a>
        <a href="all_sessions.php">All Sessions</a>
        <a href="agents.php">Agents</a>
        <?php if ($is_manager): ?>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <span class="user-info">
            👤 <?php echo htmlspecialchars($currentUser['full_name']); ?>
            <?php if ($is_manager): ?>
                <span class="access-badge">MANAGER VIEW</span>
            <?php endif; ?>
        </span>
    </div>

    <div class="coaching-container">
        <div class="welcome-banner">
            <h1>Welcome, <?php echo htmlspecialchars($currentUser['FirstName']); ?>!</h1>
            <p>
                <?php if ($is_manager): ?>
                    Organization-wide coaching overview and team performance
                <?php else: ?>
                    Track and manage coaching sessions for your team
                <?php endif; ?>
            </p>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <h3><?php echo $is_manager ? 'Total (All Teams)' : 'Total Sessions'; ?></h3>
                <p class="stat-number"><?php echo $total_sessions; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <h3>This Month</h3>
                <p class="stat-number"><?php echo $this_month; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏰</div>
                <h3>Pending Follow-up</h3>
                <p class="stat-number"><?php echo $pending_followup; ?></p>
            </div>
        </div>

        <?php if ($is_manager && $team_stats && $team_stats->num_rows > 0): ?>
            <div class="team-overview-section">
                <h2 class="section-title">👥 Team Performance Overview</h2>
                <p class="section-subtitle">Coaching activity breakdown by supervisor/team</p>
                
                <div class="team-cards-grid">
                    <?php while ($team = $team_stats->fetch_assoc()): ?>
                        <div class="team-card">
                            <div class="team-card-header">
                                <div class="team-avatar">
                                    <?php 
                                    $names = explode(' ', $team['supervisor_name']);
                                    echo strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                    ?>
                                </div>
                                <div class="team-info">
                                    <h4><?php echo htmlspecialchars($team['supervisor_name']); ?></h4>
                                    <p><?php echo htmlspecialchars($team['supervisor_email']); ?></p>
                                </div>
                            </div>
                            
                            <div class="team-stats-row">
                                <div class="team-stat">
                                    <div class="team-stat-label">Total</div>
                                    <div class="team-stat-value"><?php echo $team['total_sessions']; ?></div>
                                </div>
                                <div class="team-stat">
                                    <div class="team-stat-label">This Month</div>
                                    <div class="team-stat-value"><?php echo $team['this_month']; ?></div>
                                </div>
                                <div class="team-stat">
                                    <div class="team-stat-label">Pending</div>
                                    <div class="team-stat-value <?php echo $team['pending'] > 0 ? 'pending' : ''; ?>">
                                        <?php echo $team['pending']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($team['last_session_date']): ?>
                                <p class="last-session-text">
                                    Last session: <?php echo date('M d, Y', strtotime($team['last_session_date'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="dashboard-content">
            <div class="recent-sessions">
                <h2><?php echo $is_manager ? 'Recent Coaching Sessions (All Teams)' : 'Recent Coaching Sessions'; ?></h2>
                <?php if ($recent_sessions && $recent_sessions->num_rows > 0): ?>
                    <table class="sessions-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <?php if ($is_manager): ?>
                                    <th>Supervisor</th>
                                <?php endif; ?>
                                <th>Agent</th>
                                <th>Type</th>
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($session = $recent_sessions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($session['session_date'])); ?></td>
                                    <?php if ($is_manager): ?>
                                        <td class="supervisor-cell">
                                            <?php echo htmlspecialchars($session['supervisor_name']); ?>
                                        </td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($session['agent_name']); ?></td>
                                    <td><span class="badge badge-<?php echo $session['coaching_type']; ?>">
                                        <?php echo COACHING_TYPES[$session['coaching_type']]; ?>
                                    </span></td>
                                    <td><?php echo htmlspecialchars($session['topic']); ?></td>
                                    <td><span class="status-<?php echo $session['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $session['status'])); ?>
                                    </span></td>
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
                        <p>🎯 No coaching sessions yet!</p>
                        <a href="new_session.php" class="btn btn-primary">Create Your First Session</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="new_session.php" class="btn btn-primary">
                        <span class="icon">➕</span> New Coaching Session
                    </a>
                    <a href="agents.php" class="btn btn-secondary">
                        <span class="icon">👥</span> View All Agents
                    </a>
                    <a href="all_sessions.php" class="btn btn-secondary">
                        <span class="icon">📋</span> All Sessions
                    </a>
                    <?php if ($is_manager): ?>
                        <a href="reports.php" class="btn btn-manager">
                            <span class="icon">📊</span> Generate Reports
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>