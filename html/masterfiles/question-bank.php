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
    <title>Question Bank - PNP RTC X</title>
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
        .question-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            background: white;
        }
        .question-text {
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        .answer-option {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 4px;
            background: #f9fafb;
        }
        .answer-option.correct {
            background: #d1fae5;
            border-left: 3px solid #10b981;
        }
        .difficulty-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="nav-brand">
            <h5>PNP Regional Training Center X</h5>
            <p>Question Bank Management</p>
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
                <li><a href="subjects.php"><i class="bi bi-journal-text"></i>Manage Subjects</a></li>
                <li><a href="exams.php"><i class="bi bi-pencil-square"></i>Manage Exams</a></li>
                <li><a href="question-bank.php" class="active"><i class="bi bi-folder"></i>Question Bank</a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart"></i>Reports & Analytics</a></li>
                <li><a href="archive.php"><i class="bi bi-archive"></i>Archive</a></li>
                <li><a href="audit-logs.php"><i class="bi bi-clock-history"></i>Audit Logs</a></li>
                <li><a href="settings.php"><i class="bi bi-gear"></i>System Settings</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Question Bank</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline" data-bs-toggle="modal" data-bs-target="#importQuestionsModal">
                            <i class="bi bi-upload me-2"></i>Import Questions
                        </button>
                        <button class="btn btn-add-user" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                            <i class="bi bi-plus-lg me-2"></i>Add New Question
                        </button>
                    </div>
                </div>
            </div>

            <div id="messageAlert"></div>

            <div class="section-card">
                <div class="section-header">
                    <i class="bi bi-folder" style="color: var(--secondary-color); font-size: 1.3rem;"></i>
                    <div>
                        <h5>All Questions</h5>
                        <p>Manage exam questions by subject</p>
                    </div>
                </div>
                <div class="section-body">
                    <div class="d-flex flex-wrap gap-2 mb-3 align-items-end">
                        <div>
                            <label class="form-label small mb-1" for="filterSubject">Subject</label>
                            <select id="filterSubject" class="form-select form-select-sm" style="min-width: 200px;">
                                <option value="">All subjects</option>
                            </select>
                        </div>
                        <div class="ms-auto">
                            <label class="form-label small mb-1" for="searchQuestion">Search</label>
                            <input id="searchQuestion" type="text" class="form-control form-control-sm" placeholder="Search questions...">
                        </div>
                    </div>
                    <div id="questionsContainer">
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-hourglass-split"></i> Loading...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Questions Modal -->
    <div class="modal fade" id="importQuestionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Questions from Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>File Format Requirements:</strong>
                        <ul class="mb-0 mt-2">
                            <li>File must be Excel (.xlsx, .xls) or CSV (.csv)</li>
                            <li>First row should contain headers</li>
                            <li>Required columns: <code>Subject</code>, <code>Question</code>, <code>Option_A</code>, <code>Option_B</code>, <code>Option_C</code>, <code>Option_D</code>, <code>Correct_Answer</code>, <code>Points</code></li>
                            <li>Correct_Answer should be A, B, C, or D</li>
                            <li>Subject must match existing subjects in the system (case-sensitive)</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Available Subjects in System:</strong>
                        <div id="availableSubjectsList" class="mt-2 small">
                            <em>Loading subjects...</em>
                        </div>
                        <small class="text-muted d-block mt-2">Use these exact names in your Excel file's Subject column</small>
                    </div>
                    
                    <div class="mb-3">
                        <button class="btn btn-sm btn-outline" onclick="downloadTemplate()">
                            <i class="bi bi-download me-2"></i>Download Template
                        </button>
                    </div>

                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Select File *</label>
                            <input type="file" class="form-control" id="importFile" accept=".xlsx,.xls,.csv" required>
                            <small class="text-muted">Supported formats: Excel (.xlsx, .xls) or CSV (.csv)</small>
                        </div>
                        
                        <div id="importPreview" class="mt-3" style="display: none;">
                            <h6>Preview (First 5 rows):</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered" id="previewTable">
                                    <thead></thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div id="importStats" class="alert alert-secondary"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-add-user" id="importBtn" disabled>
                        <i class="bi bi-upload me-2"></i>Import Questions
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Question Modal -->
    <div class="modal fade" id="addQuestionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="questionModalLabel">Add New Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="questionForm">
                    <input type="hidden" id="questionId" name="question_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Subject *</label>
                            <select id="questionSubject" class="form-select" required>
                                <option value="">Select subject...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Question Text *</label>
                            <textarea class="form-control" id="questionText" rows="3" required></textarea>
                        </div>
                        <div id="answersSection">
                            <label class="form-label">Answer Options (Multiple Choice) *</label>
                            <div id="answersList">
                                <div class="input-group mb-2">
                                    <span class="input-group-text">
                                        <input type="radio" name="correctAnswer" value="0" required>
                                    </span>
                                    <input type="text" class="form-control" placeholder="Option A" data-answer-index="0" required>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">
                                        <input type="radio" name="correctAnswer" value="1" required>
                                    </span>
                                    <input type="text" class="form-control" placeholder="Option B" data-answer-index="1" required>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">
                                        <input type="radio" name="correctAnswer" value="2" required>
                                    </span>
                                    <input type="text" class="form-control" placeholder="Option C" data-answer-index="2" required>
                                </div>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">
                                        <input type="radio" name="correctAnswer" value="3" required>
                                    </span>
                                    <input type="text" class="form-control" placeholder="Option D" data-answer-index="3" required>
                                </div>
                            </div>
                            <small class="text-muted">Select the radio button for the correct answer</small>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Points</label>
                            <input type="number" class="form-control" id="questionPoints" min="1" value="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="questionSubmitBtn" class="btn btn-add-user">Add Question</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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

        // Load subjects for filter
        function loadSubjects() {
            axios.get('../../api/masterfiles/subjects.php?action=list')
                .then(response => {
                    if (response.data.success) {
                        const filterSelect = document.getElementById('filterSubject');
                        const modalSelect = document.getElementById('questionSubject');
                        
                        filterSelect.innerHTML = '<option value="">All subjects</option>';
                        modalSelect.innerHTML = '<option value="">Select subject...</option>';
                        
                        response.data.data.forEach(subject => {
                            const option1 = new Option(subject.Subject_Name, subject.Subject_ID);
                            const option2 = new Option(subject.Subject_Name, subject.Subject_ID);
                            filterSelect.add(option1);
                            modalSelect.add(option2);
                        });
                    }
                })
                .catch(error => console.error('Error loading subjects:', error));
        }

        // Load questions
        function loadQuestions() {
            const subjectFilter = document.getElementById('filterSubject').value;
            const searchTerm = document.getElementById('searchQuestion').value;

            let url = '../../api/masterfiles/exam.php?action=get_questions';
            if (subjectFilter) url += `&subject_id=${subjectFilter}`;
            if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;

            axios.get(url)
                .then(response => {
                    const container = document.getElementById('questionsContainer');
                    
                    if (response.data.success && response.data.questions.length > 0) {
                        container.innerHTML = response.data.questions.map(q => `
                            <div class="question-card">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1">
                                        <span class="badge bg-secondary me-2">${q.Subject_Name || 'N/A'}</span>
                                        <span class="badge bg-primary">Multiple Choice</span>
                                        <span class="badge bg-info ms-2">${q.Points || 1} pts</span>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline" onclick="editQuestion(${q.Question_ID})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline text-danger" onclick="deleteQuestion(${q.Question_ID})">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="question-text">${escapeHtml(q.Question_Text)}</div>
                                <div class="mt-2">
                                    ${q.answers ? q.answers.map(a => `
                                        <div class="answer-option ${a.Is_Correct ? 'correct' : ''}">
                                            ${a.Is_Correct ? '<i class="bi bi-check-circle-fill text-success me-2"></i>' : ''}
                                            ${escapeHtml(a.Choice_Text)}
                                        </div>
                                    `).join('') : '<small class="text-muted">No answers available</small>'}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-muted py-4">No questions found</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading questions:', error);
                    document.getElementById('questionsContainer').innerHTML = 
                        '<div class="text-center text-danger py-4">Error loading questions</div>';
                });
        }

        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add question form submission
        document.getElementById('questionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const questionId = document.getElementById('questionId').value;
            const formData = {
                subject_id: document.getElementById('questionSubject').value,
                question_text: document.getElementById('questionText').value,
                question_type: 'Multiple Choice',
                points: document.getElementById('questionPoints').value,
                answers: []
            };

            // Collect answers
            const answerInputs = document.querySelectorAll('#answersList input[type="text"]');
            const correctAnswerIndex = parseInt(document.querySelector('input[name="correctAnswer"]:checked')?.value || 0);
            
            answerInputs.forEach((input, index) => {
                if (input.value.trim()) {
                    formData.answers.push({
                        answer_text: input.value.trim(),
                        is_correct: index === correctAnswerIndex
                    });
                }
            });

            const url = questionId ? 
                `../../api/masterfiles/exam.php?action=update_question&question_id=${questionId}` :
                '../../api/masterfiles/exam.php?action=add_question';

            axios.post(url, formData)
                .then(response => {
                    if (response.data.success) {
                        Swal.fire('Success', response.data.message, 'success');
                        bootstrap.Modal.getInstance(document.getElementById('addQuestionModal')).hide();
                        loadQuestions();
                        document.getElementById('questionForm').reset();
                    } else {
                        Swal.fire('Error', response.data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to save question', 'error');
                });
        });

        // Edit question
        function editQuestion(questionId) {
            // Find the question in the loaded questions
            axios.get(`../../api/masterfiles/exam.php?action=get_questions`)
                .then(response => {
                    if (response.data.success) {
                        const question = response.data.questions.find(q => q.Question_ID == questionId);
                        if (!question) {
                            Swal.fire('Error', 'Question not found', 'error');
                            return;
                        }

                        // Populate the form
                        document.getElementById('questionId').value = question.Question_ID;
                        document.getElementById('questionSubject').value = question.Subject_ID;
                        document.getElementById('questionText').value = question.Question_Text;
                        document.getElementById('questionPoints').value = question.Points || 1;

                        // Populate answers
                        const answersList = document.getElementById('answersList');
                        answersList.innerHTML = '';
                        
                        if (question.answers && question.answers.length > 0) {
                            question.answers.forEach((answer, index) => {
                                const answerId = String.fromCharCode(65 + index); // A, B, C, D
                                const answerDiv = document.createElement('div');
                                answerDiv.className = 'mb-2 d-flex align-items-center gap-2';
                                answerDiv.innerHTML = `
                                    <input type="radio" name="correctAnswer" value="${index}" ${answer.Is_Correct ? 'checked' : ''} required>
                                    <span class="fw-bold">${answerId}.</span>
                                    <input type="text" class="form-control" value="${escapeHtml(answer.Choice_Text)}" required>
                                `;
                                answersList.appendChild(answerDiv);
                            });
                        } else {
                            // Default 4 options if no answers
                            for (let i = 0; i < 4; i++) {
                                const answerId = String.fromCharCode(65 + i);
                                const answerDiv = document.createElement('div');
                                answerDiv.className = 'mb-2 d-flex align-items-center gap-2';
                                answerDiv.innerHTML = `
                                    <input type="radio" name="correctAnswer" value="${i}" ${i === 0 ? 'checked' : ''} required>
                                    <span class="fw-bold">${answerId}.</span>
                                    <input type="text" class="form-control" required>
                                `;
                                answersList.appendChild(answerDiv);
                            }
                        }

                        // Update modal title and button
                        document.getElementById('questionModalLabel').textContent = 'Edit Question';
                        document.getElementById('questionSubmitBtn').textContent = 'Update Question';

                        // Show modal
                        new bootstrap.Modal(document.getElementById('addQuestionModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to load question', 'error');
                });
        }

        // Delete question
        function deleteQuestion(questionId) {
            Swal.fire({
                title: 'Delete Question?',
                text: 'This action cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                confirmButtonColor: '#ef4444'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(`../../api/masterfiles/exam.php?action=delete_question&question_id=${questionId}`)
                        .then(response => {
                            if (response.data.success) {
                                Swal.fire('Deleted', response.data.message, 'success');
                                loadQuestions();
                            } else {
                                Swal.fire('Error', response.data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            const errorMsg = error.response?.data?.message || 'Failed to delete question';
                            Swal.fire('Error', errorMsg, 'error');
                        });
                }
            });
        }

        // Filter handlers
        document.getElementById('filterSubject').addEventListener('change', loadQuestions);
        document.getElementById('searchQuestion').addEventListener('input', debounce(loadQuestions, 500));

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
        loadSubjects();
        loadQuestions();

        // Reset modal when opening for add (not edit)
        document.getElementById('addQuestionModal').addEventListener('show.bs.modal', function(e) {
            // Check if this is triggered by the "Add Question" button (not by editQuestion function)
            if (!document.getElementById('questionId').value) {
                document.getElementById('questionForm').reset();
                document.getElementById('questionId').value = '';
                document.getElementById('questionModalLabel').textContent = 'Add New Question';
                document.getElementById('questionSubmitBtn').textContent = 'Add Question';
                
                // Reset to default 4 answer options
                const answersList = document.getElementById('answersList');
                answersList.innerHTML = '';
                for (let i = 0; i < 4; i++) {
                    const answerId = String.fromCharCode(65 + i);
                    const answerDiv = document.createElement('div');
                    answerDiv.className = 'mb-2 d-flex align-items-center gap-2';
                    answerDiv.innerHTML = `
                        <input type="radio" name="correctAnswer" value="${i}" ${i === 0 ? 'checked' : ''} required>
                        <span class="fw-bold">${answerId}.</span>
                        <input type="text" class="form-control" required>
                    `;
                    answersList.appendChild(answerDiv);
                }
            }
        });

        // Import functionality
        let importData = [];
        
        // Load available subjects when import modal is opened
        document.getElementById('importQuestionsModal').addEventListener('show.bs.modal', function() {
            loadAvailableSubjects();
        });
        
        function loadAvailableSubjects() {
            axios.get('../../api/masterfiles/subjects.php?action=list')
                .then(response => {
                    if (response.data.success && response.data.data.length > 0) {
                        const subjectsList = response.data.data
                            .map(s => `<span class="badge bg-primary me-1 mb-1">${s.Subject_Name}</span>`)
                            .join('');
                        document.getElementById('availableSubjectsList').innerHTML = subjectsList;
                    } else {
                        document.getElementById('availableSubjectsList').innerHTML = '<em class="text-danger">No subjects found. Please add subjects first.</em>';
                    }
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    document.getElementById('availableSubjectsList').innerHTML = '<em class="text-danger">Error loading subjects</em>';
                });
        }
        
        document.getElementById('importFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                    const jsonData = XLSX.utils.sheet_to_json(firstSheet);

                    if (jsonData.length === 0) {
                        Swal.fire('Error', 'The file is empty', 'error');
                        return;
                    }

                    // Validate required columns
                    const requiredColumns = ['Subject', 'Question', 'Option_A', 'Option_B', 'Option_C', 'Option_D', 'Correct_Answer', 'Points'];
                    const firstRow = jsonData[0];
                    const missingColumns = requiredColumns.filter(col => !(col in firstRow));
                    
                    if (missingColumns.length > 0) {
                        Swal.fire('Error', `Missing required columns: ${missingColumns.join(', ')}`, 'error');
                        return;
                    }

                    importData = jsonData;
                    displayPreview(jsonData.slice(0, 5));
                    document.getElementById('importBtn').disabled = false;
                    
                } catch (error) {
                    console.error('Error reading file:', error);
                    Swal.fire('Error', 'Failed to read file. Please check the format.', 'error');
                }
            };
            reader.readAsArrayBuffer(file);
        });

        function displayPreview(data) {
            const preview = document.getElementById('importPreview');
            const table = document.getElementById('previewTable');
            const stats = document.getElementById('importStats');
            
            if (data.length === 0) return;

            // Create table headers
            const headers = Object.keys(data[0]);
            table.querySelector('thead').innerHTML = `<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
            
            // Create table rows
            table.querySelector('tbody').innerHTML = data.map(row => 
                `<tr>${headers.map(h => `<td>${row[h] || ''}</td>`).join('')}</tr>`
            ).join('');

            stats.innerHTML = `<strong>Total questions to import:</strong> ${importData.length}`;
            preview.style.display = 'block';
        }

        document.getElementById('importBtn').addEventListener('click', function() {
            if (importData.length === 0) return;

            Swal.fire({
                title: 'Import Questions?',
                text: `This will import ${importData.length} questions`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, import',
                confirmButtonColor: '#2563eb'
            }).then((result) => {
                if (result.isConfirmed) {
                    performImport();
                }
            });
        });

        function performImport() {
            const importBtn = document.getElementById('importBtn');
            importBtn.disabled = true;
            importBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Importing...';

            axios.post('../../api/masterfiles/exam.php?action=import_questions', {
                questions: importData
            })
            .then(response => {
                if (response.data.success) {
                    let errorDetails = '';
                    if (response.data.errors && response.data.errors.length > 0) {
                        errorDetails = '<div class="mt-3 text-start alert alert-danger"><strong>❌ Error Details:</strong><ul class="mb-0 mt-2">';
                        response.data.errors.forEach(err => {
                            errorDetails += `<li>${err}</li>`;
                        });
                        errorDetails += '</ul></div>';
                    }
                    
                    Swal.fire({
                        title: response.data.failed > 0 ? 'Import Completed with Errors' : 'Success!',
                        html: `
                            <div class="text-start">
                                <p><strong>✅ Imported:</strong> ${response.data.imported || 0} questions</p>
                                ${response.data.failed ? `<p><strong>❌ Failed:</strong> ${response.data.failed} questions</p>` : ''}
                                ${errorDetails}
                                ${response.data.failed > 0 ? '<div class="alert alert-info mt-3"><strong>💡 Common Issues:</strong><ul class="mb-0 mt-2"><li>Subject name must match exactly (case-sensitive)</li><li>All required columns must be present</li><li>At least 2 answer options required per question</li><li>Correct_Answer must be A, B, C, or D</li></ul></div>' : ''}
                            </div>
                        `,
                        icon: response.data.failed > 0 ? 'warning' : 'success',
                        width: '700px',
                        customClass: {
                            htmlContainer: 'text-start'
                        }
                    });
                    
                    if (response.data.imported > 0) {
                        bootstrap.Modal.getInstance(document.getElementById('importQuestionsModal')).hide();
                        loadQuestions();
                        document.getElementById('importForm').reset();
                        document.getElementById('importPreview').style.display = 'none';
                        importData = [];
                    }
                } else {
                    Swal.fire('Error', response.data.message || 'Import failed', 'error');
                }
            })
            .catch(error => {
                console.error('Import error:', error);
                const errorMsg = error.response?.data?.message || 'Failed to import questions. Please check your file format.';
                Swal.fire('Error', errorMsg, 'error');
            })
            .finally(() => {
                importBtn.disabled = false;
                importBtn.innerHTML = '<i class="bi bi-upload me-2"></i>Import Questions';
            });
        }

        function downloadTemplate() {
            const template = [
                {
                    Subject: 'Mathematics',
                    Question: 'What is 2 + 2?',
                    Option_A: '3',
                    Option_B: '4',
                    Option_C: '5',
                    Option_D: '6',
                    Correct_Answer: 'B',
                    Points: 1
                },
                {
                    Subject: 'Science',
                    Question: 'What is the chemical symbol for water?',
                    Option_A: 'H2O',
                    Option_B: 'CO2',
                    Option_C: 'O2',
                    Option_D: 'N2',
                    Correct_Answer: 'A',
                    Points: 1
                }
            ];

            const ws = XLSX.utils.json_to_sheet(template);
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Questions');
            XLSX.writeFile(wb, 'question_bank_template.xlsx');
        }
    </script>
</body>
</html>
