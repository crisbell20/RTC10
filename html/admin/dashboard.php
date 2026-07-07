<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

// Check if user is Admin
if ($_SESSION['user_role'] !== 'Admin') {
    header('Location: ../../login.php');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PNP RTC X</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .sidebar-menu .menu-label {
            display: block;
            padding: 12px 20px;
            font-size: 0.7rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
            margin-bottom: 8px;
            cursor: default;
            pointer-events: none;
        }
        .sidebar-menu .menu-label i {
            margin-right: 6px;
            font-size: 0.8rem;
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <div class="nav-brand">
            <h5>PNP Regional Training Center X</h5>
            <p>Administration & System Management</p>
        </div>
        <div class="nav-user">
            <span><?= $userName ?></span>
            <button class="btn-logout" id="logoutBtn"><i class="bi bi-box-arrow-right"></i></button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a></li>
                <li class="menu-label"><i class="bi bi-folder-fill"></i>Masterfiles</li>
                <li><a href="../masterfiles/users.php"><i class="bi bi-people-fill"></i>Manage Users</a></li>
                <li><a href="../masterfiles/courses.php"><i class="bi bi-book-fill"></i>Manage Courses</a></li>
                <li><a href="../masterfiles/subjects.php"><i class="bi bi-journal-text"></i>Manage Subjects</a></li>
                <li><a href="../masterfiles/exams.php"><i class="bi bi-pencil-square"></i>Manage Exams</a></li>
                <li><a href="../masterfiles/question-bank.php"><i class="bi bi-folder"></i>Question Bank</a></li>
                <li><a href="../masterfiles/reports.php"><i class="bi bi-bar-chart"></i>Reports & Analytics</a></li>
                <li><a href="../masterfiles/archive.php"><i class="bi bi-archive"></i>Archive</a></li>
                <li><a href="../masterfiles/audit-logs.php"><i class="bi bi-clock-history"></i>Audit Logs</a></li>
                <li><a href="../masterfiles/settings.php"><i class="bi bi-gear"></i>System Settings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <div class="page-header">
                <h2>Dashboard</h2>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-header">
                        <div class="stat-label">Total Users</div>
                        <div class="stat-icon icon-blue"><i class="bi bi-people-fill"></i></div>
                    </div>
                    <div class="stat-value" id="totalUsers">--</div>
                    <div class="stat-footer">
                        <span id="usersChange">--</span>
                    </div>
                </div>

                <div class="stat-card green">
                    <div class="stat-header">
                        <div class="stat-label">Active Courses</div>
                        <div class="stat-icon icon-green"><i class="bi bi-book-fill"></i></div>
                    </div>
                    <div class="stat-value" id="activeCourses">--</div>
                    <div class="stat-footer">
                        <span id="coursesChange">--</span>
                    </div>
                </div>

                <div class="stat-card red">
                    <div class="stat-header">
                        <div class="stat-label">System Alerts</div>
                        <div class="stat-icon icon-red"><i class="bi bi-exclamation-circle-fill"></i></div>
                    </div>
                    <div class="stat-value" id="systemAlerts">--</div>
                    <div class="stat-footer">
                        <span id="alertsStatus">--</span>
                    </div>
                </div>

                <div class="stat-card purple">
                    <div class="stat-header">
                        <div class="stat-label">System Uptime</div>
                        <div class="stat-icon icon-purple"><i class="bi bi-check-circle-fill"></i></div>
                    </div>
                    <div class="stat-value" id="systemUptime">--</div>
                    <div class="stat-footer">
                        <span id="uptimeLabel">--</span>
                    </div>
                </div>
            </div>

            <!-- <div class="section-card">
                <div class="section-header">
                    <i class="bi bi-people-fill" style="color: var(--secondary-color); font-size: 1.3rem;"></i>
                    <div>
                        <h5>User Management</h5>
                        <p>System users by role</p>
                    </div>
                </div>
                <div class="section-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="userTableBody">
                        </tbody>
                    </table>
                </div>
            </div> -->

            <!-- System Activity Log -->
            <div class="section-card">
                <div class="section-header">
                    <i class="bi bi-clock-history" style="color: var(--secondary-color); font-size: 1.3rem;"></i>
                    <div>
                        <h5>System Activity Log</h5>
                        <p>Recent system events and changes</p>
                    </div>
                </div>
                <div class="section-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Type</th>
                                <th>Timestamp</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Administrator</strong></td>
                                <td>User Account Created</td>
                                <td>User Management</td>
                                <td>2026-02-20 10:35</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                            <tr>
                                <td><strong>CCMD Officer</strong></td>
                                <td>Course Published</td>
                                <td>Course Management</td>
                                <td>2026-02-20 09:22</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                            <tr>
                                <td><strong>System</strong></td>
                                <td>Database Backup</td>
                                <td>System Maintenance</td>
                                <td>2026-02-20 02:15</td>
                                <td><span class="badge badge-success">Success</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="../../assets/js/auto-logout.js"></script>
    <script>
        // Logout handler
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Logout',
                text: 'Are you sure you want to logout?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'No, stay',
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../api/auth/logout.php', {
                        method: 'POST'
                    }).then(() => {
                        window.location.href = '../../login.php';
                    }).catch(() => {
                        window.location.href = '../../login.php';
                    });
                }
            });
        });

        // Load dashboard statistics
        axios.get('../../api/admin/stats.php', { withCredentials: true })
            .then(function (response) {
                if (response.data.success && response.data.stats) {
                    const stats = response.data.stats;
                    
                    // Update Total Users
                    const totalUsersEl = document.getElementById('totalUsers');
                    if (totalUsersEl) {
                        totalUsersEl.textContent = stats.total_users || 0;
                    }

                    // Update Active Courses
                    const activeCoursesEl = document.getElementById('activeCourses');
                    if (activeCoursesEl) {
                        activeCoursesEl.textContent = stats.total_courses || 0;
                    }
                    
                    // Update users by role if elements exist
                    if (stats.by_role) {
                        const adminCount = stats.by_role.Admin || 0;
                        const ccmdCount = stats.by_role.CCMD || 0;
                        const examineeCount = stats.by_role.Examinee || 0;
                        
                        // Update role-specific counts if elements exist
                        const adminEl = document.getElementById('statAdmin');
                        if (adminEl) adminEl.textContent = adminCount;
                        
                        const ccmdEl = document.getElementById('statCcmd');
                        if (ccmdEl) ccmdEl.textContent = ccmdCount;
                        
                        const examineeEl = document.getElementById('statExaminee');
                        if (examineeEl) examineeEl.textContent = examineeCount;
                    }
                }
            })
            .catch(function (error) {
                console.error('Error loading dashboard stats:', error);
                const totalUsersEl = document.getElementById('totalUsers');
                if (totalUsersEl) {
                    totalUsersEl.textContent = '0';
                }
                const activeCoursesEl = document.getElementById('activeCourses');
                if (activeCoursesEl) {
                    activeCoursesEl.textContent = '0';
                }
            });
    </script>
</body>
</html>
