<?php
// coaching/agent_profile.php
require_once 'config.php';
checkAuth();

$agent_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';

if (!$agent_id) {
    header('Location: agents.php');
    exit();
}

// Get agent details
$agent_query = "SELECT * FROM Employees WHERE EmployeeID = '$agent_id'";
$agent_result = $conn->query($agent_query);

if (!$agent_result || $agent_result->num_rows === 0) {
    header('Location: agents.php');
    exit();
}

$agent = $agent_result->fetch_assoc();
$currentUser = getCurrentUser();

// Get coaching statistics for this agent (with error handling)
$total_sessions = 0;
$total_sessions_query = "SELECT COUNT(*) as total FROM coaching_sessions WHERE agent_id = '$agent_id'";
$total_result = $conn->query($total_sessions_query);
if ($total_result) {
    $total_sessions = $total_result->fetch_assoc()['total'];
}

// Get last session date (with error handling)
$last_session_date = null;
$last_session_query = "SELECT MAX(session_date) as last_date FROM coaching_sessions WHERE agent_id = '$agent_id'";
$last_session_result = $conn->query($last_session_query);
if ($last_session_result) {
    $last_data = $last_session_result->fetch_assoc();
    $last_session_date = $last_data['last_date'];
}

// Get coaching type breakdown (with error handling)
$type_breakdown_query = "SELECT coaching_type, COUNT(*) as count 
                         FROM coaching_sessions 
                         WHERE agent_id = '$agent_id' 
                         GROUP BY coaching_type";
$type_breakdown = $conn->query($type_breakdown_query);

// Get all coaching sessions for this agent (with error handling)
$sessions_query = "SELECT cs.*, 
                   CONCAT(s.FirstName, ' ', s.LastName) as supervisor_name
                   FROM coaching_sessions cs
                   LEFT JOIN Employees s ON cs.supervisor_id = s.EmployeeID
                   WHERE cs.agent_id = '$agent_id'
                   ORDER BY cs.session_date DESC, cs.session_time DESC";
$sessions = $conn->query($sessions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Profile - <?php echo htmlspecialchars($agent['FirstName'] . ' ' . $agent['LastName']); ?> - Cohere</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="top-nav">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        <span class="nav-title">🎯 Coaching Portal</span>
        <a href="index.php">Dashboard</a>
        <a href="new_session.php">New Session</a>
        <a href="all_sessions.php">All Sessions</a>
        <a href="agents.php" class="active">Agents</a>
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-info">
                    <div class="profile-avatar">
    <?php 
    $pictureUrl = getProfilePictureUrl($agent['EmployeeID']);
    if ($pictureUrl): 
    ?>
        <img src="<?php echo htmlspecialchars($pictureUrl); ?>" 
             alt="Profile Picture"
             style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
    <?php else: ?>
        <?php echo strtoupper(substr($agent['FirstName'], 0, 1) . substr($agent['LastName'], 0, 1)); ?>
    <?php endif; ?>
</div>
                    <div class="profile-details">
                        <h2><?php echo htmlspecialchars($agent['FirstName'] . ' ' . $agent['LastName']); ?></h2>
                        <p class="profile-email">📧 <?php echo htmlspecialchars($agent['Email']); ?></p>
                        <p class="profile-role">🆔 Employee ID: <?php echo htmlspecialchars($agent['EmployeeID']); ?></p>
                        <?php if (!empty($agent['role'])): ?>
                            <p class="profile-role">👤 Role: <?php echo htmlspecialchars($agent['role']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-actions">
    <a href="upload_picture.php?agent_id=<?php echo urlencode($agent_id); ?>" 
       class="btn btn-secondary" 
       style="background: #FFA500; margin-right: 10px;">
        📸 Upload Picture
    </a>
    <a href="new_session.php?agent_id=<?php echo urlencode($agent_id); ?>" class="btn btn-primary">
        ➕ New Coaching Session
    </a>
</div>
            </div>

            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <h3>Total Sessions</h3>
                    <p class="stat-number"><?php echo $total_sessions; ?></p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <h3>Last Session</h3>
                    <p class="stat-number" style="font-size: 18px;">
                        <?php echo $last_session_date ? date('M d, Y', strtotime($last_session_date)) : 'Never'; ?>
                    </p>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📋</div>
                    <h3>Coaching Types</h3>
                    <div class="type-breakdown">
                        <?php 
                        if ($type_breakdown && $type_breakdown->num_rows > 0):
                            while ($type = $type_breakdown->fetch_assoc()): 
                        ?>
                            <span class="badge badge-<?php echo $type['coaching_type']; ?>">
                                <?php echo COACHING_TYPES[$type['coaching_type']]; ?>: <?php echo $type['count']; ?>
                            </span>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <span style="color: #999; font-size: 14px;">No sessions yet</span>
                        <?php
                        endif; 
                        ?>
                    </div>
                </div>
            </div>

            <div class="profile-sessions">
                <h2>📚 Coaching History</h2>
                
                <?php if ($sessions && $sessions->num_rows > 0): ?>
                    <div class="sessions-timeline">
                        <?php while ($session = $sessions->fetch_assoc()): ?>
                            <div class="session-card">
                                <div class="session-header">
                                    <div class="session-date">
                                        <span class="date-day"><?php echo date('d', strtotime($session['session_date'])); ?></span>
                                        <span class="date-month"><?php echo date('M Y', strtotime($session['session_date'])); ?></span>
                                    </div>
                                    <div class="session-info">
                                        <h3><?php echo htmlspecialchars($session['topic']); ?></h3>
                                        <p class="session-meta">
                                            <span class="badge badge-<?php echo $session['coaching_type']; ?>">
                                                <?php echo COACHING_TYPES[$session['coaching_type']]; ?>
                                            </span>
                                            <span style="color: #666;">👤 Coach: <?php echo htmlspecialchars($session['supervisor_name']); ?></span>
                                            <span style="color: #666;">🕐 <?php echo date('g:i A', strtotime($session['session_time'])); ?></span>
                                        </p>
                                    </div>
                                    <div class="session-status">
                                        <span class="status-<?php echo $session['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $session['status'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="session-body">
                                    <div class="session-section">
                                        <h4>📝 Discussion Notes</h4>
                                        <p><?php echo nl2br(htmlspecialchars($session['discussion_notes'])); ?></p>
                                    </div>
                                    
                                    <?php if (!empty($session['strengths'])): ?>
                                        <div class="session-section strengths">
                                            <h4>✅ Strengths</h4>
                                            <p><?php echo nl2br(htmlspecialchars($session['strengths'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($session['areas_for_improvement'])): ?>
                                        <div class="session-section improvements">
                                            <h4>🎯 Areas for Improvement</h4>
                                            <p><?php echo nl2br(htmlspecialchars($session['areas_for_improvement'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($session['action_items'])): ?>
                                        <div class="session-section actions">
                                            <h4>📋 Action Items</h4>
                                            <p><?php echo nl2br(htmlspecialchars($session['action_items'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($session['follow_up_date'])): ?>
                                        <div class="session-followup">
                                            <strong>📆 Follow-up:</strong> <?php echo date('F d, Y', strtotime($session['follow_up_date'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="session-footer">
                                    <a href="view_session.php?id=<?php echo $session['id']; ?>" class="btn-view">View Details</a>
                                    <a href="edit_session.php?id=<?php echo $session['id']; ?>" class="btn-edit">Edit</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="no-sessions">
                        <p style="font-size: 18px; margin-bottom: 20px;">📝 No coaching sessions recorded yet for this agent.</p>
                        <a href="new_session.php?agent_id=<?php echo urlencode($agent_id); ?>" class="btn btn-primary">
                            ➕ Create First Session
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>