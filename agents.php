<?php
// coaching/agents.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$supervisor_id = $currentUser['EmployeeID'];

// Get all employees with coaching statistics
$agents_query = "SELECT e.EmployeeID, e.FirstName, e.LastName, e.Email,
                 COUNT(cs.id) as total_sessions,
                 MAX(cs.session_date) as last_session,
                 SUM(CASE WHEN cs.status = 'pending_followup' THEN 1 ELSE 0 END) as pending_followups
                 FROM Employees e
                 LEFT JOIN coaching_sessions cs ON e.EmployeeID = cs.agent_id AND cs.supervisor_id = '$supervisor_id'
                 WHERE e.IsVerified = 1
                 GROUP BY e.EmployeeID, e.FirstName, e.LastName, e.Email
                 ORDER BY e.FirstName, e.LastName";
$agents = $conn->query($agents_query);

$total_agents = $agents ? $agents->num_rows : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Agents - Cohere Coaching Portal</title>
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
        <?php if (getUserAccessLevel() === 'manager'): ?>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <div class="agents-header-banner">
            <h1>Agent Directory</h1>
            <p>Manage and track coaching sessions for all your team members</p>
        </div>

        <div class="agents-controls">
            <div class="agents-count">
                📊 <strong><?php echo $total_agents; ?></strong> Total Agents
            </div>
            <div class="search-bar-enhanced">
                <input type="text" id="agentSearch" placeholder="Search by name or email...">
                <span class="search-icon">🔍</span>
            </div>
        </div>

        <?php if ($agents && $agents->num_rows > 0): ?>
            <div class="agents-grid-enhanced">
                <?php while ($agent = $agents->fetch_assoc()): ?>
                    <div class="agent-card-enhanced <?php echo ($agent['pending_followups'] > 0) ? 'has-pending' : ''; ?>" 
                         data-name="<?php echo strtolower($agent['FirstName'] . ' ' . $agent['LastName'] . ' ' . $agent['Email']); ?>">
                        
                        <div class="agent-card-header">
                            <div class="agent-avatar-enhanced">
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
                            <div class="agent-info-enhanced">
                                <h3><?php echo htmlspecialchars($agent['FirstName'] . ' ' . $agent['LastName']); ?></h3>
                                <div class="agent-email-enhanced">
                                    📧 <?php echo htmlspecialchars($agent['Email']); ?>
                                </div>
                                <span class="agent-id-badge">
                                    ID: <?php echo htmlspecialchars($agent['EmployeeID']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="agent-stats-enhanced">
                            <div class="stat-item">
                                <div class="stat-item-label">Sessions</div>
                                <div class="stat-item-value"><?php echo $agent['total_sessions']; ?></div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="stat-item-label">Last Session</div>
                                <div class="stat-item-value small-text">
                                    <?php 
                                    if ($agent['last_session']) {
                                        $date = new DateTime($agent['last_session']);
                                        $now = new DateTime();
                                        $diff = $now->diff($date);
                                        
                                        if ($diff->days == 0) {
                                            echo 'Today';
                                        } elseif ($diff->days == 1) {
                                            echo 'Yesterday';
                                        } elseif ($diff->days < 7) {
                                            echo $diff->days . ' days ago';
                                        } else {
                                            echo date('M d, Y', strtotime($agent['last_session']));
                                        }
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="stat-item <?php echo ($agent['pending_followups'] > 0) ? 'alert' : ''; ?>">
                                <div class="stat-item-label">
                                    <?php echo ($agent['pending_followups'] > 0) ? 'Pending' : 'Status'; ?>
                                </div>
                                <div class="stat-item-value <?php echo ($agent['pending_followups'] == 0) ? 'small-text' : ''; ?>">
                                    <?php 
                                    if ($agent['pending_followups'] > 0) {
                                        echo '⚠️ ' . $agent['pending_followups'];
                                    } else {
                                        echo '<span style="color: #27ae60;">✓ Up to date</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="agent-actions-enhanced">
                            <a href="agent_profile.php?id=<?php echo urlencode($agent['EmployeeID']); ?>" class="btn btn-primary">
                                👤 View Profile
                            </a>
                            <a href="new_session.php?agent_id=<?php echo urlencode($agent['EmployeeID']); ?>" class="btn btn-secondary">
                                ➕ New Session
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-agents-state">
                <div class="empty-icon">👥</div>
                <h3>No Agents Found</h3>
                <p>There are no verified employees in the system yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality with real-time filtering
        const searchInput = document.getElementById('agentSearch');
        const agentCards = document.querySelectorAll('.agent-card-enhanced');
        
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            let visibleCount = 0;
            
            agentCards.forEach(card => {
                const agentName = card.getAttribute('data-name');
                if (agentName.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update count display
            const countElement = document.querySelector('.agents-count strong');
            if (countElement) {
                countElement.textContent = visibleCount;
            }
        });
        
        // Add animation on load
        document.addEventListener('DOMContentLoaded', function() {
            agentCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.4s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 50);
            });
        });
    </script>
</body>
</html>