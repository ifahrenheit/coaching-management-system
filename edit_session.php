<?php
// coaching/edit_session.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$session_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_message = '';
$error_message = '';

if (!$session_id) {
    header('Location: index.php');
    exit();
}

// Get session details
$session_query = "SELECT * FROM coaching_sessions WHERE id = $session_id";
$result = $conn->query($session_query);

if (!$result || $result->num_rows === 0) {
    header('Location: index.php');
    exit();
}

$session = $result->fetch_assoc();

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
    
    $update_query = "UPDATE coaching_sessions SET
                     agent_id = '$agent_id',
                     session_date = '$session_date',
                     session_time = '$session_time',
                     coaching_type = '$coaching_type',
                     topic = '$topic',
                     discussion_notes = '$discussion_notes',
                     action_items = '$action_items',
                     strengths = '$strengths',
                     areas_for_improvement = '$areas_for_improvement',
                     follow_up_date = $follow_up_date,
                     status = '$status'
                     WHERE id = $session_id";
    
    if ($conn->query($update_query)) {
        $success_message = "Coaching session updated successfully!";
        // Refresh session data
        $result = $conn->query($session_query);
        $session = $result->fetch_assoc();
    } else {
        $error_message = "Error updating session: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Coaching Session - Cohere</title>
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
        <div class="form-container">
            <h1 style="color: #004AAD; margin-bottom: 10px;">✏️ Edit Coaching Session</h1>
            <p style="color: #666; margin-bottom: 30px;">Update coaching session details and feedback</p>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="coaching-form">
                <div class="form-section">
                    <h3>Session Details</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="agent_id">Agent <span style="color: red;">*</span></label>
                            <select name="agent_id" id="agent_id" required>
                                <option value="">Select Agent</option>
                                <?php 
                                if ($employees && $employees->num_rows > 0):
                                    while ($employee = $employees->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $employee['EmployeeID']; ?>" 
                                            <?php echo $session['agent_id'] == $employee['EmployeeID'] ? 'selected' : ''; ?>>
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
                            <label for="coaching_type">Coaching Type <span style="color: red;">*</span></label>
                            <select name="coaching_type" id="coaching_type" required>
                                <?php foreach (COACHING_TYPES as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" 
                                            <?php echo $session['coaching_type'] == $key ? 'selected' : ''; ?>>
                                        <?php echo $value; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_date">Session Date <span style="color: red;">*</span></label>
                            <input type="date" name="session_date" id="session_date" 
                                   value="<?php echo $session['session_date']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="session_time">Session Time <span style="color: red;">*</span></label>
                            <input type="time" name="session_time" id="session_time" 
                                   value="<?php echo $session['session_time']; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="topic">Topic/Subject <span style="color: red;">*</span></label>
                        <input type="text" name="topic" id="topic" 
                               value="<?php echo htmlspecialchars($session['topic']); ?>" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Discussion & Feedback</h3>
                    
                    <div class="form-group">
                        <label for="discussion_notes">Discussion Notes <span style="color: red;">*</span></label>
                        <textarea name="discussion_notes" id="discussion_notes" rows="6" required><?php echo htmlspecialchars($session['discussion_notes']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="strengths">Strengths Identified</label>
                        <textarea name="strengths" id="strengths" rows="4"><?php echo htmlspecialchars($session['strengths']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="areas_for_improvement">Areas for Improvement</label>
                        <textarea name="areas_for_improvement" id="areas_for_improvement" rows="4"><?php echo htmlspecialchars($session['areas_for_improvement']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="action_items">Action Items</label>
                        <textarea name="action_items" id="action_items" rows="4"><?php echo htmlspecialchars($session['action_items']); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Follow-up</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="follow_up_date">Follow-up Date</label>
                            <input type="date" name="follow_up_date" id="follow_up_date" 
                                   value="<?php echo $session['follow_up_date']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="status">Status <span style="color: red;">*</span></label>
                            <select name="status" id="status" required>
                                <option value="completed" <?php echo $session['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="pending_followup" <?php echo $session['status'] == 'pending_followup' ? 'selected' : ''; ?>>Pending Follow-up</option>
                                <option value="cancelled" <?php echo $session['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Update Coaching Session</button>
                    <a href="view_session.php?id=<?php echo $session_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>