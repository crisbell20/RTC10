<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['Admin', 'CCMD'])) {
    header('Location: ../../html/auth/login.html');
    exit;
}

$userName = $_SESSION['user_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - PNP RTC X</title>
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
        .subject-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
        }
        .subject-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        .subject-code {
            font-family: monospace;
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-brand">
            <h5>PNP Regional Training Center X</h5>
            <p>Subject Management</p>
        </div>
        <div class="nav-user">
            <span><?= htmlspecialchars($userName) ?></span>
            <button class="btn-logout" id="logoutBtn"><i class="bi bi-box-arrow-right"></i></button>
        </div>
    </div>

    <div class="dashboard-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="../../html/admin/dashboard.php"><i class="bi bi-grid-1x2-fill"></i>Dashboard</a></li>
                <li class="menu-label"><i class="bi bi-folder-fill"></i>Masterfiles</li>
                <li><a href="users.php"><i class="bi bi-people-fill"></i>Manage Users</a></li>
                <li><a href="courses.php"><i class="bi bi-book-fill"></i>Manage Courses</a></li>
                <li><a href="subjects.php" class="active"><i class="bi bi-journal-text"></i>Manage Subjects</a></li>
                <li><a href="exams.php"><i class="bi bi-pencil-square"></i>Manage Exams</a></li>
                <li><a href="question-bank.php"><i class="bi bi-folder"></i>Question Bank</a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart"></i>Reports & Analytics</a></li>
                <li><a href="archive.php"><i class="bi bi-archive"></i>Archive</a></li>
                <li><a href="audit-logs.php"><i class="bi bi-clock-history"></i>Audit Logs</a></li>
                <li><a href="settings.php"><i class="bi bi-gear"></i>System Settings</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Manage Subjects</h2>
                    <button class="btn btn-add-user" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="bi bi-plus-lg me-2"></i>Add New Subject
                    </button>
                </div>
            </div>

            <div id="messageAlert"></div>

            <div class="section-card">
                <div class="section-header">
                    <i class="bi bi-journal-text" style="color: var(--secondary-color); font-size: 1.3rem;"></i>
                    <div>
                        <h5>All Subjects</h5>
                        <p>Subjects organized by course</p>
                    </div>
                </div>
                <div class="section-body">
                    <div class="d-flex flex-wrap gap-2 mb-3 align-items-end">
                        <div>
                            <label class="form-label small mb-1" for="filterCourse">Filter by Course</label>
                            <select id="filterCourse" class="form-select form-select-sm" style="min-width: 200px;">
                                <option value="">All courses</option>
                            </select>
                        </div>
                        <div class="ms-auto">
                            <label class="form-label small mb-1" for="searchSubject">Search</label>
                            <input id="searchSubject" type="text" class="form-control form-control-sm" placeholder="Search subjects...">
                        </div>
                    </div>
                    <div id="subjectsContainer">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-hourglass-split"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subjectModalLabel">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="subjectForm">
                    <input type="hidden" id="subjectId" name="subject_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Course *</label>
                            <select id="subjectCourse" class="form-select" required>
                                <option value="">Select course...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject Name *</label>
                            <input type="text" class="form-control" id="subjectName" required placeholder="e.g., Mathematics">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject Code *</label>
                            <input type="text" class="form-control" id="subjectCode" required placeholder="e.g., MATH101">
                            <small class="text-muted">Must be unique</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="subjectDescription" rows="3" placeholder="Brief description of the subject"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="subjectSubmitBtn" class="btn btn-add-user">Add Subject</button>
                    </div>
                </form>
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

        // Load courses for filter and modal
        function loadCourses() {
            axios.get('../../api/masterfiles/courses.php?action=list')
                .then(response => {
                    if (response.data.success) {
                        const filterSelect = document.getElementById('filterCourse');
                        const modalSelect = document.getElementById('subjectCourse');
                        
                        filterSelect.innerHTML = '<option value="">All courses</option>';
                        modalSelect.innerHTML = '<option value="">Select course...</option>';
                        
                        response.data.data.forEach(course => {
                            const option1 = new Option(course.Course_Name, course.Course_ID);
                            const option2 = new Option(course.Course_Name, course.Course_ID);
                            filterSelect.add(option1);
                            modalSelect.add(option2);
                        });
                    }
                })
                .catch(error => console.error('Error loading courses:', error));
        }

        // Load subjects
        function loadSubjects() {
            const courseFilter = document.getElementById('filterCourse').value;
            const searchTerm = document.getElementById('searchSubject').value;

            let url = '../../api/masterfiles/subjects.php?action=list';
            if (courseFilter) url += `&course_id=${courseFilter}`;
            if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;

            axios.get(url)
                .then(response => {
                    const container = document.getElementById('subjectsContainer');
                    
                    if (response.data.success && response.data.data.length > 0) {
                        // First, get course names
                        axios.get('../../api/masterfiles/courses.php?action=list')
                            .then(coursesResponse => {
                                const courses = {};
                                if (coursesResponse.data.success) {
                                    coursesResponse.data.data.forEach(c => {
                                        courses[c.Course_ID] = c.Course_Name;
                                    });
                                }

                                // Group by course
                                const grouped = {};
                                response.data.data.forEach(subject => {
                                    const courseName = courses[subject.Course_ID] || 'Unknown Course';
                                    if (!grouped[courseName]) {
                                        grouped[courseName] = [];
                                    }
                                    grouped[courseName].push(subject);
                                });

                                container.innerHTML = Object.keys(grouped).map(courseName => `
                                    <div class="mb-4">
                                        <h5 class="mb-3"><i class="bi bi-book me-2"></i>${courseName}</h5>
                                        ${grouped[courseName].map(s => `
                                            <div class="subject-card">
                                                <div class="subject-header">
                                                    <div class="flex-grow-1">
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <h6 class="mb-0">${escapeHtml(s.Subject_Name)}</h6>
                                                            <span class="subject-code">${escapeHtml(s.Subject_Code)}</span>
                                                        </div>
                                                        ${s.Description ? `<p class="text-muted mb-0 small">${escapeHtml(s.Description)}</p>` : ''}
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline" onclick="editSubject(${s.Subject_ID})">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline text-danger" onclick="deleteSubject(${s.Subject_ID})">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                `).join('');
                            });
                    } else {
                        container.innerHTML = '<div class="text-center text-muted py-4">No subjects found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    document.getElementById('subjectsContainer').innerHTML = 
                        '<div class="text-center text-danger py-4">Error loading subjects</div>';
                });
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Edit subject
        function editSubject(subjectId) {
            axios.get(`../../api/masterfiles/subjects.php?action=list`)
                .then(response => {
                    if (response.data.success) {
                        const subject = response.data.data.find(s => s.Subject_ID == subjectId);
                        if (subject) {
                            document.getElementById('subjectId').value = subject.Subject_ID;
                            document.getElementById('subjectCourse').value = subject.Course_ID;
                            document.getElementById('subjectName').value = subject.Subject_Name;
                            document.getElementById('subjectCode').value = subject.Subject_Code;
                            document.getElementById('subjectDescription').value = subject.Description || '';
                            
                            document.getElementById('subjectModalLabel').textContent = 'Edit Subject';
                            document.getElementById('subjectSubmitBtn').textContent = 'Update Subject';
                            
                            new bootstrap.Modal(document.getElementById('addSubjectModal')).show();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading subject:', error);
                    Swal.fire('Error', 'Failed to load subject details', 'error');
                });
        }

        // Add/Update subject form submission
        document.getElementById('subjectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const subjectId = document.getElementById('subjectId').value;
            const formData = {
                course_id: document.getElementById('subjectCourse').value,
                subject_name: document.getElementById('subjectName').value,
                subject_code: document.getElementById('subjectCode').value,
                description: document.getElementById('subjectDescription').value
            };

            const action = subjectId ? 'update' : 'add';
            if (subjectId) {
                formData.subject_id = subjectId;
            }

            axios.post(`../../api/masterfiles/subjects.php?action=${action}`, formData)
                .then(response => {
                    if (response.data.success) {
                        Swal.fire('Success', response.data.message, 'success');
                        bootstrap.Modal.getInstance(document.getElementById('addSubjectModal')).hide();
                        loadSubjects();
                        document.getElementById('subjectForm').reset();
                        document.getElementById('subjectId').value = '';
                        document.getElementById('subjectModalLabel').textContent = 'Add New Subject';
                        document.getElementById('subjectSubmitBtn').textContent = 'Add Subject';
                    } else {
                        Swal.fire('Error', response.data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorMessage = error.response?.data?.message || 'Failed to save subject';
                    Swal.fire('Error', errorMessage, 'error');
                });
        });

        // Delete subject
        function deleteSubject(subjectId) {
            Swal.fire({
                title: 'Delete Subject?',
                text: 'This will also delete all related exams and questions. This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#ef4444'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.post(`../../api/masterfiles/subjects.php?action=delete`, { subject_id: subjectId })
                        .then(response => {
                            if (response.data.success) {
                                Swal.fire('Deleted', response.data.message, 'success');
                                loadSubjects();
                            } else {
                                Swal.fire('Error', response.data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error', 'Failed to delete subject', 'error');
                        });
                }
            });
        }

        // Reset modal on close
        document.getElementById('addSubjectModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('subjectForm').reset();
            document.getElementById('subjectId').value = '';
            document.getElementById('subjectModalLabel').textContent = 'Add New Subject';
            document.getElementById('subjectSubmitBtn').textContent = 'Add Subject';
        });

        // Filter handlers
        document.getElementById('filterCourse').addEventListener('change', loadSubjects);
        document.getElementById('searchSubject').addEventListener('input', debounce(loadSubjects, 500));

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Initialize
        loadCourses();
        loadSubjects();
    </script>
</body>
</html>
