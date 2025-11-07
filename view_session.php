<?php
// coaching/view_session.php
require_once 'config.php';
checkAuth();

$session_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']) ? true : false;

if (!$session_id) {
    header('Location: index.php');
    exit();
}

// Get session details
$session_query = "SELECT cs.*, 
                  a.FirstName as agent_first, a.LastName as agent_last, a.Email as agent_email,
                  CONCAT(a.FirstName, ' ', a.LastName) as agent_name,
                  s.FirstName as supervisor_first, s.LastName as supervisor_last,
                  CONCAT(s.FirstName, ' ', s.LastName) as supervisor_name
                  FROM coaching_sessions cs
                  LEFT JOIN Employees a ON cs.agent_id = a.EmployeeID
                  LEFT JOIN Employees s ON cs.supervisor_id = s.EmployeeID
                  WHERE cs.id = $session_id";
$result = $conn->query($session_query);

if (!$result || $result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$session = $result->fetch_assoc();
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coaching Session Details - Cohere</title>
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
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                ✅ Coaching session saved successfully!
            </div>
        <?php endif; ?>

        <div class="session-detail-container">
            <div class="session-detail-header">
                <h2><?php echo htmlspecialchars($session['topic']); ?></h2>
                <p style="opacity: 0.9; margin-top: 10px;">
                    📅 <?php echo date('l, F j, Y', strtotime($session['session_date'])); ?> 
                    at <?php echo date('g:i A', strtotime($session['session_time'])); ?>
                </p>
            </div>

            <div class="session-detail-body">
                <div class="detail-row">
                    <div class="detail-label">Agent</div>
                    <div class="detail-value">
                        <strong><?php echo htmlspecialchars($session['agent_name']); ?></strong>
                        <br><small><?php echo htmlspecialchars($session['agent_email']); ?></small>
                        <br><a href="agent_profile.php?id=<?php echo $session['agent_id']; ?>" class="btn-view" style="margin-top: 8px;">View Profile</a>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Coaching Type</div>
                    <div class="detail-value">
                        <span class="badge badge-<?php echo $session['coaching_type']; ?>">
                            <?php echo COACHING_TYPES[$session['coaching_type']]; ?>
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Coach/Supervisor</div>
                    <div class="detail-value"><?php echo htmlspecialchars($session['supervisor_name']); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="status-<?php echo $session['status']; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $session['status'])); ?>
                        </span>
                    </div>
                </div>

                <?php if ($session['follow_up_date']): ?>
                <div class="detail-row">
                    <div class="detail-label">Follow-up Date</div>
                    <div class="detail-value">
                        📆 <?php echo date('F j, Y', strtotime($session['follow_up_date'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="detail-section">
                    <h3>📝 Discussion Notes</h3>
                    <div class="detail-content">
                        <?php echo nl2br(htmlspecialchars($session['discussion_notes'])); ?>
                    </div>
                </div>

                <?php if ($session['strengths']): ?>
                <div class="detail-section">
                    <h3>✅ Strengths Identified</h3>
                    <div class="detail-content" style="background: #e8f5e9; border-left: 4px solid #4caf50;">
                        <?php echo nl2br(htmlspecialchars($session['strengths'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($session['areas_for_improvement']): ?>
                <div class="detail-section">
                    <h3>🎯 Areas for Improvement</h3>
                    <div class="detail-content" style="background: #fff3e0; border-left: 4px solid #ff9800;">
                        <?php echo nl2br(htmlspecialchars($session['areas_for_improvement'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($session['action_items']): ?>
                <div class="detail-section">
                    <h3>📋 Action Items</h3>
                    <div class="detail-content" style="background: #e3f2fd; border-left: 4px solid #2196f3;">
                        <?php echo nl2br(htmlspecialchars($session['action_items'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="action-bar">
                    <a href="edit_session.php?id=<?php echo $session_id; ?>" class="btn btn-primary">✏️ Edit Session</a>
                    <a href="agent_profile.php?id=<?php echo $session['agent_id']; ?>" class="btn btn-secondary">👤 View Agent Profile</a>
                    <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
                    Created: <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?>
                    <?php if ($session['updated_at'] != $session['created_at']): ?>
                        | Updated: <?php echo date('M j, Y g:i A', strtotime($session['updated_at'])); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>