<?php
// coaching/config.php
// Configuration file for coaching portal

// Include the main database connection
require_once __DIR__ . '/../db_connection.php';

// Upload directory for coaching attachments
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Coaching types
define('COACHING_TYPES', [
    'performance' => 'Performance Coaching',
    'behavioral' => 'Behavioral Coaching',
    'skill_development' => 'Skill Development',
    'quality' => 'Quality Coaching',
    'other' => 'Other'
]);

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Function to check if user is logged in
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['employeeID'])) {
        header('Location: ../login.php');
        exit();
    }
}

// Function to get current user info
function getCurrentUser() {
    global $conn;
    
    if (!isset($_SESSION['employeeID'])) {
        return null;
    }
    
    $employeeID = $conn->real_escape_string($_SESSION['employeeID']);
    
    $query = "SELECT EmployeeID, FirstName, LastName, Email, role, is_qa 
              FROM Employees 
              WHERE EmployeeID = '$employeeID'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Add full name for convenience
        $user['full_name'] = $user['FirstName'] . ' ' . $user['LastName'];
        return $user;
    }
    
    return null;
}

// Function to get all employees (for agent selection)
function getAllEmployees() {
    global $conn;
    
    $query = "SELECT EmployeeID, FirstName, LastName, Email, role 
              FROM Employees 
              WHERE IsVerified = 1 
              ORDER BY FirstName, LastName";
    
    return $conn->query($query);
}

// Function to get profile picture URL
function getProfilePictureUrl($employeeID) {
    global $conn;
    
    $employeeID = $conn->real_escape_string($employeeID);
    $query = "SELECT profile_picture FROM Employees WHERE EmployeeID = '$employeeID'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        if (!empty($employee['profile_picture'])) {
            return 'uploads/profile_pictures/' . $employee['profile_picture'];
        }
    }
    
    return null; // No picture
}

// Function to display profile picture or initials
function displayProfilePicture($employeeID, $firstName, $lastName, $size = 70) {
    $pictureUrl = getProfilePictureUrl($employeeID);
    $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
    
    if ($pictureUrl) {
        return '<img src="' . htmlspecialchars($pictureUrl) . '" 
                     alt="Profile Picture" 
                     style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; object-fit: cover;">';
    } else {
        return '<div style="width: ' . $size . 'px; height: ' . $size . 'px; border-radius: 50%; 
                            background: linear-gradient(135deg, #004AAD 0%, #FFA500 100%); 
                            color: white; display: flex; align-items: center; justify-content: center; 
                            font-size: ' . ($size/3) . 'px; font-weight: bold;">' 
                . $initials . 
                '</div>';
    }
}

// Function to check if user has manager/admin access
function isManagerOrAbove() {
    global $conn;
    
    if (!isset($_SESSION['employeeID'])) {
        return false;
    }
    
    $employeeID = $conn->real_escape_string($_SESSION['employeeID']);
    
    // Check role and QA status
    $query = "SELECT role, is_qa FROM Employees WHERE EmployeeID = '$employeeID'";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Define manager-level roles (based on your actual roles)
        $manager_roles = ['Admin', 'Director', 'Manager', 'SOM Approver'];
        
        // Check if role is in manager list OR is QA
        if (in_array($user['role'], $manager_roles) || $user['is_qa'] == 1) {
            return true;
        }
    }
    
    return false;
}

// Function to get user's access level
function getUserAccessLevel() {
    if (isManagerOrAbove()) {
        return 'manager'; // Can see all data
    }
    return 'supervisor'; // Can only see their own data
}
?>


