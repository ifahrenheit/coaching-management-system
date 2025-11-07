# Cohere Coaching Portal - Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [Features](#features)
3. [System Requirements](#system-requirements)
4. [Installation](#installation)
5. [Project Structure](#project-structure)
6. [Database Schema](#database-schema)
7. [User Roles & Permissions](#user-roles--permissions)
8. [Pages & Functionality](#pages--functionality)
9. [CSS & Styling System](#css--styling-system)
10. [API/URL Parameters](#apiurl-parameters)
11. [Security Considerations](#security-considerations)
12. [Maintenance & Updates](#maintenance--updates)
13. [Troubleshooting](#troubleshooting)

---

## Project Overview

The **Cohere Coaching Portal** is a comprehensive web-based coaching management system designed to help supervisors and managers track, document, and analyze coaching sessions with their team members.

### Purpose
- Document coaching sessions with detailed feedback
- Track employee development and progress
- Generate reports on coaching activities
- Manage coaching follow-ups and action items
- Provide organizational oversight for coaching programs

### Technology Stack
- **Backend**: PHP
- **Database**: MySQL
- **Frontend**: HTML5, CSS3
- **Version**: 1.0 (Optimized)

---

## Features

### Core Features
✅ **Session Management**
- Create, read, update, view coaching sessions
- Track session date, time, type, and topic
- Record discussion notes, strengths, and improvement areas
- Set follow-up dates and track pending actions

✅ **Agent Directory**
- View all team members (agents)
- See coaching statistics per agent
- Quick access to agent profiles
- Filter and search agents

✅ **Session Tracking**
- View all coaching sessions
- Filter by agent, type, status, or date range
- Detailed session history
- Status tracking (Completed, Pending Follow-up, Cancelled)

✅ **Reporting** (Manager Only)
- Summary reports
- Reports by supervisor
- Reports by agent
- Reports by coaching type
- Detailed session reports
- Export to CSV
- Print functionality

✅ **Dashboard**
- Quick statistics overview
- Recent sessions list
- Quick action buttons
- Team performance overview (managers only)

### Additional Features
✅ Responsive design (mobile-friendly)
✅ User authentication & authorization
✅ Role-based access control
✅ Professional UI with consistent branding
✅ Date/time tracking
✅ Email notifications (if configured)

---

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Web Server**: Apache, Nginx, or equivalent
- **SSL**: Recommended for production

### Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

### Disk Space
- ~50 MB for application files
- Additional space for database backups

---

## Installation

### Step 1: File Upload
1. Upload all PHP files to your server at `/coaching/` directory
2. Upload CSS file to `/coaching/assets/css/style.css`
3. Ensure proper file permissions (644 for files, 755 for directories)

### Step 2: Database Setup
1. Create a new MySQL database named `coaching_db` (or your preferred name)
2. Create the following tables:

```sql
-- Employees table
CREATE TABLE Employees (
    EmployeeID INT PRIMARY KEY,
    FirstName VARCHAR(100),
    LastName VARCHAR(100),
    Email VARCHAR(100),
    IsVerified BOOLEAN,
    AccessLevel VARCHAR(50)
);

-- Coaching Sessions table
CREATE TABLE coaching_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT,
    supervisor_id INT,
    session_date DATE,
    session_time TIME,
    coaching_type VARCHAR(50),
    topic VARCHAR(255),
    discussion_notes LONGTEXT,
    strengths LONGTEXT,
    areas_for_improvement LONGTEXT,
    action_items LONGTEXT,
    follow_up_date DATE,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (supervisor_id) REFERENCES Employees(EmployeeID)
);
```

### Step 3: Configuration
1. Update `config.php` with your database credentials:
   ```php
   $servername = "localhost";
   $username = "your_db_user";
   $password = "your_db_password";
   $dbname = "coaching_db";
   ```

2. Define coaching types in `config.php`:
   ```php
   define('COACHING_TYPES', array(
       'performance' => 'Performance',
       'behavioral' => 'Behavioral',
       'skill_development' => 'Skill Development',
       'quality' => 'Quality',
       'other' => 'Other'
   ));
   ```

### Step 4: Test Access
1. Navigate to `https://yourdomain.com/coaching/index.php`
2. Log in with your credentials
3. Verify all pages load correctly

---

## Project Structure

```
coaching/
├── index.php                 # Dashboard page
├── agents.php               # Agents directory page
├── new_session.php          # Create new coaching session
├── all_sessions.php         # View all sessions with filters
├── reports.php              # Reports page (manager only)
├── view_session.php         # View single session details
├── edit_session.php         # Edit existing session
├── agent_profile.php        # View agent profile
├── config.php               # Database & constants config
├── assets/
│   └── css/
│       └── style.css        # Main stylesheet (1633 lines)
└── README.md                # Project information
```

---

## Database Schema

### Employees Table
| Column | Type | Description |
|--------|------|-------------|
| EmployeeID | INT | Unique employee identifier |
| FirstName | VARCHAR(100) | Employee first name |
| LastName | VARCHAR(100) | Employee last name |
| Email | VARCHAR(100) | Employee email address |
| IsVerified | BOOLEAN | Account verification status |
| AccessLevel | VARCHAR(50) | 'supervisor' or 'manager' |

### Coaching Sessions Table
| Column | Type | Description |
|--------|------|-------------|
| id | INT | Unique session identifier (auto-increment) |
| agent_id | INT | Employee receiving coaching |
| supervisor_id | INT | Employee providing coaching |
| session_date | DATE | Date of coaching session |
| session_time | TIME | Time of coaching session |
| coaching_type | VARCHAR(50) | Type of coaching (performance, behavioral, etc.) |
| topic | VARCHAR(255) | Session topic/subject |
| discussion_notes | LONGTEXT | Detailed discussion notes |
| strengths | LONGTEXT | Identified strengths |
| areas_for_improvement | LONGTEXT | Areas needing improvement |
| action_items | LONGTEXT | Specific action items |
| follow_up_date | DATE | Date for follow-up session |
| status | VARCHAR(50) | Status (completed, pending_followup, cancelled) |
| created_at | TIMESTAMP | Record creation timestamp |

---

## User Roles & Permissions

### Supervisor Role
- **Can Do:**
  - View their own dashboard
  - Create new coaching sessions
  - View all sessions they conducted
  - View agent directory
  - Filter and search sessions
  - Edit their own sessions
  - View agent profiles

- **Cannot Do:**
  - Access Reports page
  - View other supervisors' sessions
  - See organization-wide analytics
  - Access manager features

### Manager Role
- **Can Do:**
  - Everything supervisors can do
  - Access Reports page
  - Generate all reports (Summary, By Supervisor, By Agent, By Type, Detailed)
  - View organization-wide coaching data
  - See all sessions across all supervisors
  - Export reports to CSV
  - View "MANAGER VIEW" badge
  - See team performance overview

- **Cannot Do:**
  - Conduct coaching sessions (they don't coach directly)
  - Delete records (safety measure)

---

## Pages & Functionality

### 1. Dashboard (index.php)
**URL**: `/coaching/index.php`

**Purpose**: Main landing page with overview statistics

**Features**:
- Welcome message personalized to user
- Quick stats cards (Total Sessions, This Month, Pending Follow-ups)
- Recent sessions table (10 most recent)
- Quick action buttons
- Team performance overview (managers only)

**Users**: All authenticated users

### 2. Agents Directory (agents.php)
**URL**: `/coaching/agents.php`

**Purpose**: View and manage all team members

**Features**:
- Grid view of all agents
- Agent cards with avatar, email, ID
- Statistics per agent (Sessions, Last Session, Pending)
- Search/filter functionality
- Quick links to view profile or create new session
- Visual indicators for pending follow-ups (red border)

**Users**: All authenticated users

### 3. New Coaching Session (new_session.php)
**URL**: `/coaching/new_session.php`

**Purpose**: Create a new coaching session record

**Features**:
- Select agent from dropdown
- Choose coaching type
- Set session date and time
- Enter topic/subject
- Write discussion notes
- Record strengths identified
- Note areas for improvement
- Set action items
- Schedule follow-up date
- Set session status

**Validation**: All required fields must be filled

**Users**: Supervisors and Managers

### 4. All Sessions (all_sessions.php)
**URL**: `/coaching/all_sessions.php`

**Purpose**: View and filter all coaching sessions

**Features**:
- Table view of all sessions
- Filter by:
  - Agent
  - Coaching Type
  - Status
  - Date Range
- Column sorting
- Quick view/edit links
- Clear filters button

**Users**: All authenticated users (supervisors see own, managers see all)

### 5. Reports (reports.php)
**URL**: `/coaching/reports.php`

**Purpose**: Generate and analyze coaching data

**Features**:
- 5 report types:
  1. **Summary**: Total sessions, unique agents, supervisors, status breakdown
  2. **By Supervisor**: Sessions per supervisor with completion rates
  3. **By Agent**: Sessions per agent with last session date
  4. **By Type**: Coaching type distribution with percentages
  5. **Detailed**: Complete session details table
- Date range filtering
- Supervisor/Agent filtering
- Export to CSV
- Print functionality

**Users**: Managers only (access denied for supervisors)

### 6. View Session (view_session.php)
**URL**: `/coaching/view_session.php?id={session_id}`

**Purpose**: View detailed session information

**Features**:
- Complete session details
- Agent information
- Discussion notes
- Identified strengths
- Areas for improvement
- Action items
- Follow-up information
- Status display
- Edit/delete options

**Users**: Session owner or manager

### 7. Agent Profile (agent_profile.php)
**URL**: `/coaching/agent_profile.php?id={employee_id}`

**Purpose**: View individual agent coaching history

**Features**:
- Agent information (name, email, ID)
- Coaching statistics
- Type breakdown
- All sessions conducted with this agent
- Progress indicators
- Last session details

**Users**: All authenticated users

---

## CSS & Styling System

### Design System

#### Color Palette
```css
:root {
    --primary-color: #004AAD;        /* Blue */
    --secondary-color: #FFA500;      /* Orange */
    --success-color: #27ae60;        /* Green */
    --warning-color: #f39c12;        /* Yellow */
    --danger-color: #e74c3c;         /* Red */
    --info-color: #2196f3;           /* Light Blue */
}
```

#### Typography
- **Font Family**: System fonts (Apple System Font, Segoe UI, Roboto, etc.)
- **Base Size**: 14px
- **Line Height**: 1.6
- **Headings**: Bold, larger variants

#### Spacing & Layout
- **Max Width**: 1400px
- **Padding**: 30px containers, 20px cards
- **Gap**: 20-30px between elements
- **Border Radius**: 5-12px

#### Shadows
```css
--shadow-sm: 0 2px 8px rgba(0,0,0,0.1);
--shadow-md: 0 4px 12px rgba(0,0,0,0.1);
--shadow-lg: 0 4px 12px rgba(0,0,0,0.15);
```

### CSS File Structure

The main `style.css` file is organized into sections:

1. **CSS Variables** (line 1-30) - Color and spacing definitions
2. **Base Styles** (line 31-70) - Global HTML elements
3. **Navigation** (line 71-130) - Top navigation bar
4. **Banners & Headers** (line 131-200) - Page headers
5. **Cards** (line 201-280) - Card components
6. **Buttons** (line 281-380) - Button styles
7. **Tables** (line 381-480) - Table styling
8. **Forms** (line 481-600) - Form elements
9. **Page-Specific** (line 601-1600) - Individual page styles
10. **Responsive** (line 1601-1633) - Mobile breakpoints

### Responsive Breakpoints

```css
/* Tablets and below */
@media (max-width: 1024px) {
    .dashboard-content {
        grid-template-columns: 1fr;
    }
}

/* Mobile devices */
@media (max-width: 768px) {
    /* Single column layouts */
    /* Smaller text and padding */
    /* Adjusted grid columns */
}
```

### CSS Classes Reference

#### Common Classes
- `.card` - White card with shadow
- `.btn` - Base button style
- `.btn-primary` - Blue button
- `.btn-secondary` - Orange button
- `.badge` - Small colored label
- `.status-*` - Status indicators
- `.alert` - Alert/notification box

#### Page-Specific Classes
- `.welcome-banner` - Dashboard welcome banner
- `.agent-card-enhanced` - Agent directory cards
- `.session-header-banner` - New session page banner
- `.sessions-table-wrapper` - All sessions table container
- `.report-filters` - Reports filter section
- `.summary-cards` - Report summary cards

---

## API/URL Parameters

### Query Parameters

#### agents.php
- No parameters

#### all_sessions.php
- `agent` - Filter by agent ID
- `type` - Filter by coaching type
- `status` - Filter by status
- `date_from` - Start date (YYYY-MM-DD)
- `date_to` - End date (YYYY-MM-DD)

#### reports.php (Manager only)
- `type` - Report type (summary, by_supervisor, by_agent, by_type, detailed)
- `date_from` - Start date (YYYY-MM-DD)
- `date_to` - End date (YYYY-MM-DD)
- `supervisor` - Filter by supervisor ID
- `agent` - Filter by agent ID

#### view_session.php
- `id` - Session ID (required)

#### edit_session.php
- `id` - Session ID (required)

#### agent_profile.php
- `id` - Employee ID (required)

---

## Security Considerations

### Authentication
- All pages require user authentication via `checkAuth()`
- Invalid sessions redirect to login

### Authorization
- Reports page restricted to managers only
- Users can only see their own sessions (unless manager)
- Database queries escape user input via `real_escape_string()`

### Best Practices Implemented
✅ User authentication check on all pages
✅ Role-based access control
✅ SQL injection prevention (escaping)
✅ CSRF protection (recommended for production)
✅ Input validation on forms
✅ Data visibility based on user role
✅ Secure session handling

### Recommendations for Production
1. Use prepared statements instead of string escaping
2. Implement CSRF tokens on all forms
3. Enable SSL/HTTPS
4. Add rate limiting on login attempts
5. Implement password hashing (bcrypt)
6. Add audit logging for sensitive operations
7. Regular security updates for PHP and MySQL
8. Use environment variables for database credentials

---

## Maintenance & Updates

### Regular Tasks

#### Weekly
- Check error logs
- Monitor database size
- Verify backups are running

#### Monthly
- Review session statistics
- Check for pending follow-ups
- Clean up old records

#### Quarterly
- Update PHP dependencies
- Review security patches
- Optimize database queries
- Check user access levels

### Database Maintenance

#### Backup
```bash
mysqldump -u username -p coaching_db > backup_YYYY-MM-DD.sql
```

#### Optimize Tables
```sql
OPTIMIZE TABLE coaching_sessions, Employees;
```

#### Check for Issues
```sql
CHECK TABLE coaching_sessions;
REPAIR TABLE coaching_sessions;
```

### Adding New Features

1. **Add New Coaching Type**:
   - Update `COACHING_TYPES` constant in `config.php`
   - Add CSS class `.badge-{type_name}` in style.css

2. **Add New Report Type**:
   - Add case statement in `reports.php`
   - Create SQL query
   - Add tab button in navigation

3. **Add New Database Field**:
   - Add column to table: `ALTER TABLE coaching_sessions ADD COLUMN ...`
   - Update `new_session.php` form
   - Update `view_session.php` display
   - Update style.css if needed

---

## Troubleshooting

### Common Issues

#### "Access denied" on reports page
**Cause**: User is not a manager
**Solution**: Verify user's `access_level` in Employees table is set to 'manager'

#### Pages not loading
**Cause**: File not found or wrong path
**Solution**: 
- Verify all files are uploaded to `/coaching/` directory
- Check file permissions (644 for files)
- Check PHP error logs

#### CSS not loading
**Cause**: CSS file path is incorrect
**Solution**:
- Verify `style.css` is at `/coaching/assets/css/style.css`
- Clear browser cache (Ctrl+Shift+Delete)
- Check browser console for 404 errors

#### Database connection errors
**Cause**: Wrong credentials or server offline
**Solution**:
- Verify database credentials in `config.php`
- Check MySQL is running
- Verify database and tables exist

#### Filters not working
**Cause**: GET parameters not being passed
**Solution**:
- Verify URL parameters are correct
- Check form method is GET
- Clear browser cache

#### Search function not working
**Cause**: JavaScript issue or DOM elements not found
**Solution**:
- Check browser console for JS errors
- Verify DOM element IDs match JavaScript selectors
- Reload page

### Debug Mode

To enable debug logging, add to `config.php`:
```php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

### Performance Optimization

1. **Enable database indexing** on frequently searched columns:
   ```sql
   ALTER TABLE coaching_sessions ADD INDEX idx_agent (agent_id);
   ALTER TABLE coaching_sessions ADD INDEX idx_status (status);
   ```

2. **Enable PHP caching** (OPcache)

3. **Minimize CSS/JS** for production

4. **Use CDN** for static assets if needed

---

## Support & Contact

For issues, feature requests, or questions:

### Documentation Updates
This documentation should be updated whenever:
- New pages are added
- Database schema changes
- Security measures are updated
- Major features are added

### Changelog

**Version 1.0 - Initial Release**
- Dashboard with statistics
- Agent directory
- Coaching session management
- Session filtering and search
- Manager reports
- Responsive design
- CSS optimization (1633 lines vs original 1338)
- Role-based access control
- 6 main pages optimized

**Files Modified**:
- index.php - Cleaned, optimized navigation
- agents.php - Grid view with search
- new_session.php - Form with validations
- all_sessions.php - Table view with filters
- reports.php - Manager reports
- style.css - Consolidated, 1633 lines

---

## License & Credits

**Project**: Cohere Coaching Portal v1.0
**Type**: Internal Business Application
**Last Updated**: 2024

**Optimization Details**:
- Removed 1338 lines of inline CSS
- Consolidated to single 1633-line stylesheet
- Added CSS variables for maintainability
- Implemented role-based navigation
- Added manager-exclusive Reports page
- Mobile-responsive design
- Consistent branding throughout

---

**End of Documentation**

For the latest updates, refer to the GitHub repository or internal wiki.
