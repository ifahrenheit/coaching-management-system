<?php
// coaching/upload_picture.php
require_once 'config.php';
checkAuth();

$currentUser = getCurrentUser();
$agent_id = isset($_GET['agent_id']) ? $conn->real_escape_string($_GET['agent_id']) : '';
$success_message = '';
$error_message = '';

// If no agent_id provided, use current user
if (!$agent_id) {
    $agent_id = $currentUser['EmployeeID'];
}

// Check if user has permission (either uploading for themselves or they're admin/supervisor)
$can_upload = ($agent_id == $currentUser['EmployeeID']) || 
              ($currentUser['role'] == 'Admin') || 
              ($currentUser['is_supervisor'] ?? false);

if (!$can_upload) {
    die('Access denied');
}

// Get agent details
$agent_query = "SELECT * FROM Employees WHERE EmployeeID = '$agent_id'";
$agent_result = $conn->query($agent_query);
if (!$agent_result || $agent_result->num_rows === 0) {
    die('Agent not found');
}
$agent = $agent_result->fetch_assoc();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Upload failed. Please try again.";
    } elseif (!in_array($file['type'], $allowed_types)) {
        $error_message = "Invalid file type. Only JPG, PNG, and GIF allowed.";
    } elseif ($file['size'] > $max_size) {
        $error_message = "File too large. Maximum size is 5MB.";
    } else {
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'profile_' . $agent_id . '_' . time() . '.' . $extension;
        $upload_path = __DIR__ . '/uploads/profile_pictures/' . $filename;
        
        // Delete old picture if exists
        if (!empty($agent['profile_picture'])) {
            $old_file = __DIR__ . '/uploads/profile_pictures/' . $agent['profile_picture'];
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Update database with file path
            $update_query = "UPDATE Employees SET profile_picture = '$filename' WHERE EmployeeID = '$agent_id'";
            
            if ($conn->query($update_query)) {
                $success_message = "Profile picture updated successfully!";
                // Refresh agent data
                $agent_result = $conn->query($agent_query);
                $agent = $agent_result->fetch_assoc();
            } else {
                $error_message = "Database error: " . $conn->error;
                unlink($upload_path); // Delete uploaded file if DB update fails
            }
        } else {
            $error_message = "Failed to save file. Check directory permissions.";
        }
    }
}

// Handle picture deletion
if (isset($_GET['delete']) && $_GET['delete'] == '1') {
    if (!empty($agent['profile_picture'])) {
        $file_path = __DIR__ . '/uploads/profile_pictures/' . $agent['profile_picture'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $update_query = "UPDATE Employees SET profile_picture = NULL WHERE EmployeeID = '$agent_id'";
        if ($conn->query($update_query)) {
            $success_message = "Profile picture deleted successfully!";
            $agent['profile_picture'] = null;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Profile Picture - Cohere</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .upload-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .current-picture {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .current-picture img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #004AAD;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .current-picture .no-picture {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: linear-gradient(135deg, #004AAD 0%, #FFA500 100%);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            font-weight: bold;
            border: 4px solid #004AAD;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .upload-form {
            margin-top: 30px;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
            margin-bottom: 20px;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: block;
            padding: 15px;
            background: #f8f9fa;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .file-input-label:hover {
            background: #e9ecef;
            border-color: #004AAD;
        }
        
        .file-input-label.has-file {
            background: #e3f2fd;
            border-color: #004AAD;
            border-style: solid;
        }
        
        .file-name {
            margin-top: 10px;
            color: #004AAD;
            font-weight: 600;
        }
        
        .upload-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .requirements {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: #666;
        }
        
        .requirements ul {
            margin: 10px 0 0 20px;
            padding: 0;
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <a href="../dashboard.php" class="back-link">← Back to Dashboard</a>
        <span class="nav-title">🎯 Coaching Portal</span>
        <a href="index.php">Dashboard</a>
        <a href="agents.php">Agents</a>
        <span class="user-info">👤 <?php echo htmlspecialchars($currentUser['full_name']); ?></span>
    </div>

    <div class="coaching-container">
        <div class="upload-container">
            <h1 style="color: #004AAD; text-align: center; margin-bottom: 10px;">
                📸 Profile Picture
            </h1>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">
                Upload a profile picture for <?php echo htmlspecialchars($agent['FirstName'] . ' ' . $agent['LastName']); ?>
            </p>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="current-picture">
                <?php if (!empty($agent['profile_picture'])): ?>
                    <img src="uploads/profile_pictures/<?php echo htmlspecialchars($agent['profile_picture']); ?>" 
                         alt="Profile Picture">
                    <div style="margin-top: 15px;">
                        <a href="?agent_id=<?php echo urlencode($agent_id); ?>&delete=1" 
                           class="btn btn-delete"
                           onclick="return confirm('Are you sure you want to delete this picture?');">
                            🗑️ Delete Picture
                        </a>
                    </div>
                <?php else: ?>
                    <div class="no-picture">
                        <?php echo strtoupper(substr($agent['FirstName'], 0, 1) . substr($agent['LastName'], 0, 1)); ?>
                    </div>
                    <p style="color: #999; margin-top: 15px;">No profile picture yet</p>
                <?php endif; ?>
            </div>

            <form method="POST" enctype="multipart/form-data" class="upload-form">
                <div class="file-input-wrapper">
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*" required>
                    <label for="profile_picture" class="file-input-label" id="fileLabel">
                        <div style="font-size: 48px; margin-bottom: 10px;">📁</div>
                        <strong>Click to choose a file</strong>
                        <div style="font-size: 13px; color: #999; margin-top: 5px;">
                            or drag and drop here
                        </div>
                        <div class="file-name" id="fileName" style="display: none;"></div>
                    </label>
                </div>

                <div class="requirements">
                    <strong>📋 Requirements:</strong>
                    <ul>
                        <li>Accepted formats: JPG, PNG, GIF</li>
                        <li>Maximum file size: 5MB</li>
                        <li>Recommended: Square image (e.g., 500x500px)</li>
                        <li>For best results, use a clear, front-facing photo</li>
                    </ul>
                </div>

                <div class="upload-buttons">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        📤 Upload Picture
                    </button>
                    <a href="agent_profile.php?id=<?php echo urlencode($agent_id); ?>" 
                       class="btn btn-secondary" style="flex: 1;">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // File input handling
        const fileInput = document.getElementById('profile_picture');
        const fileLabel = document.getElementById('fileLabel');
        const fileName = document.getElementById('fileName');

        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                fileName.textContent = '📄 ' + file.name;
                fileName.style.display = 'block';
                fileLabel.classList.add('has-file');
            } else {
                fileName.style.display = 'none';
                fileLabel.classList.remove('has-file');
            }
        });

        // Drag and drop support
        fileLabel.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileLabel.style.background = '#e3f2fd';
            fileLabel.style.borderColor = '#004AAD';
        });

        fileLabel.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileLabel.style.background = '#f8f9fa';
            fileLabel.style.borderColor = '#ddd';
        });

        fileLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            fileLabel.style.background = '#f8f9fa';
            fileLabel.style.borderColor = '#ddd';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>