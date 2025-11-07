<?php
// coaching/new_session.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$supervisor_id = $currentUser['EmployeeID'];
$success_message = '';
$error_message = '';

// Get all employees for dropdown
$employees = getAllEmployees();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_id = $conn->real_escape_string($_POST['agent_id']);
    $session_date = $conn->real_escape_string($_POST['session_date']);
    $session_time = $conn->real_escape_string($_POST['session_time']);
    $coaching_type = $conn->real_escape_string($_POST['coaching_type']);
    $topic = $conn->real_escape_string($_POST['topic']);
    $discussion_notes = $conn->real_escape_string($_POST['discussion_notes']);
    $action_items = $conn->real_escape_string($_POST['action_items']);
    $strengths = $conn->real_escape_string($_POST['strengths']);
    $areas_for_improvement = $conn->real_escape_string($_POST['areas_for_improvement']);
    $follow_up_date = !empty($_POST['follow_up_date']) ? "'" . $conn->real_escape_string($_POST['follow_up_date']) . "'" : "NULL";
    $status = $conn->real_escape_string($_POST['status']);
    
    $insert_query = "INSERT INTO coaching_sessions 
                     (agent_id, supervisor_id, session_date, session_time, coaching_type, topic, 
                      discussion_notes, action_items, strengths, areas_for_improvement, follow_up_date, status)
                     VALUES 
                     ('$agent_id', '$supervisor_id', '$session_date', '$session_time', '$coaching_type', '$topic',
                      '$discussion_notes', '$action_items', '$strengths', '$areas_for_improvement', $follow_up_date, '$status')";
    
    if ($conn->query($insert_query)) {
        $session_id = $conn->insert_id;
        header("Location: view_session.php?id=$session_id&success=1");
        exit();
    } else {
        $error_message = "Error creating session: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Coaching Session - Cohere</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="top-nav">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        <span class="nav-title">🎯 Coaching Portal</span>
        <a href="index.php">Dashboard</a>
        <a href="new_session.php" class="active">New Session</a>
        <a href="all_sessions.php">All Sessions</a>
        <a href="agents.php">Agents</a>
        <?php if (getUserAccessLevel() === 'manager'): ?>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <div class="session-header-banner">
            <h1>New Coaching Session</h1>
            <p>Document a coaching session with detailed feedback and action items</p>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" class="coaching-form">
                <div class="form-section">
                    <h3>Session Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="agent_id">Agent <span class="required-asterisk">*</span></label>
                            <select name="agent_id" id="agent_id" required>
                                <option value="">Select Agent</option>
                                <?php 
                                if ($employees && $employees->num_rows > 0):
                                    while ($employee = $employees->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $employee['EmployeeID']; ?>">
                                        <?php echo htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']); ?>
                                        (<?php echo htmlspecialchars($employee['EmployeeID']); ?>)
                                    </option>
                                <?php 
                                    endwhile;
                                endif; 
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="coaching_type">Coaching Type <span class="required-asterisk">*</span></label>
                            <select name="coaching_type" id="coaching_type" required>
                                <?php foreach (COACHING_TYPES as $key => $value): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_date">Session Date <span class="required-asterisk">*</span></label>
                            <input type="date" name="session_date" id="session_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="session_time">Session Time <span class="required-asterisk">*</span></label>
                            <input type="time" name="session_time" id="session_time" value="<?php echo date('H:i'); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="topic">Topic/Subject <span class="required-asterisk">*</span></label>
                        <input type="text" name="topic" id="topic" placeholder="e.g., Call handling improvement, Quality adherence" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Discussion & Feedback</h3>
                    
                    <div class="form-group">
                        <label for="discussion_notes">Discussion Notes <span class="required-asterisk">*</span></label>
                        <textarea name="discussion_notes" id="discussion_notes" rows="6" 
                                  placeholder="Detailed notes about what was discussed during the coaching session..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="strengths">Strengths Identified</label>
                        <textarea name="strengths" id="strengths" rows="4" 
                                  placeholder="What did the agent do well? What are their strong points?"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="areas_for_improvement">Areas for Improvement</label>
                        <textarea name="areas_for_improvement" id="areas_for_improvement" rows="4" 
                                  placeholder="What areas need development? What can be improved?"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="action_items">Action Items</label>
                        <textarea name="action_items" id="action_items" rows="4" 
                                  placeholder="Specific actions the agent should take. Clear, measurable steps..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Follow-up</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="follow_up_date">Follow-up Date</label>
                            <input type="date" name="follow_up_date" id="follow_up_date">
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span class="required-asterisk">*</span></label>
                            <select name="status" id="status" required>
                                <option value="completed">Completed</option>
                                <option value="pending_followup">Pending Follow-up</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Save Coaching Session</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>