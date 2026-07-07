/**
 * CCMD Exams Management
 * View and monitor exams (read-only)
 */

const API_BASE = '../../api/masterfiles/exams.php';
const COURSES_API = '../../api/masterfiles/courses.php';
const SUBJECTS_API = '../../api/masterfiles/subjects.php';

let exams = [];
let courses = [];
let currentCourseFilter = '';
let currentSubjectFilter = '';
let currentStatusFilter = '';

// Load exams
function loadExams() {
    axios.get(`${API_BASE}?action=list`)
        .then(response => {
            if (response.data.success) {
                exams = response.data.data || [];
                renderExams();
            } else {
                showError('Failed to load exams');
            }
        })
        .catch(error => {
            console.error('Error loading exams:', error);
            showError('Error loading exams');
        });
}

// Render exams list
function renderExams() {
    const container = document.getElementById('examsContainer');
    
    if (!container) {
        console.error('Exams container not found');
        return;
    }
    
    // Apply filters
    let filteredExams = exams;
    
    if (currentCourseFilter) {
        filteredExams = filteredExams.filter(e => e.Course_ID == currentCourseFilter);
    }
    
    if (currentSubjectFilter) {
        filteredExams = filteredExams.filter(e => e.Subject_ID == currentSubjectFilter);
    }
    
    if (currentStatusFilter) {
        filteredExams = filteredExams.filter(e => e.Status === currentStatusFilter);
    }
    
    if (filteredExams.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No exams found</div>';
        return;
    }
    
    // Group by course and subject
    const grouped = {};
    filteredExams.forEach(exam => {
        const courseKey = exam.Course_Name || 'No Course';
        const subjectKey = exam.Subject_Name || 'No Subject';
        
        if (!grouped[courseKey]) {
            grouped[courseKey] = {};
        }
        
        if (!grouped[courseKey][subjectKey]) {
            grouped[courseKey][subjectKey] = [];
        }
        
        grouped[courseKey][subjectKey].push(exam);
    });
    
    // Render grouped exams
    let html = '';
    
    Object.keys(grouped).sort().forEach(courseName => {
        html += `<div class="course-group">
            <h5 class="course-title">${courseName}</h5>`;
        
        Object.keys(grouped[courseName]).sort().forEach(subjectName => {
            html += `<div class="subject-group">
                <h6 class="subject-title">${subjectName}</h6>
                <div class="exams-grid">`;
            
            grouped[courseName][subjectName].forEach(exam => {
                console.log(exam);
                const statusClass = exam.Status === 'Published' ? 'success' : 
                                  exam.Status === 'Draft' ? 'secondary' : 'warning';
                
                const scheduleDate = exam.Schedule_Date ? 
                    new Date(exam.Schedule_Date).toLocaleDateString() : 'Not scheduled';
                
                html += `
                    <div class="exam-card">
                        <div class="exam-card-header">
                            <h6 class="exam-title">${escapeHtml(exam.Title)}</h6>
                            <span class="badge bg-${statusClass}">${exam.Status}</span>
                        </div>
                        <div class="exam-card-body">
                            <div class="exam-info">
                                <i class="bi bi-calendar3"></i>
                                <span>${scheduleDate}</span>
                            </div>
                            <div class="exam-info">
                                <i class="bi bi-clock"></i>
                                <span>${exam.Duration || 0} minutes</span>
                            </div>
                            <div class="exam-info">
                                <i class="bi bi-list-check"></i>
                                <span>${exam.Question_Count || 0} questions</span>
                            </div>
                            <div class="exam-info">
                                <i class="bi bi-trophy"></i>
                                <span>Passing: ${exam.Passing_Score || 0}%</span>
                            </div>
                        </div>
                        <div class="exam-card-footer">
                            <button class="btn btn-sm btn-outline-primary" onclick="viewExamDetails(${exam.Exam_ID})">
                                <i class="bi bi-eye"></i> View Details
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="manageQuestions(${exam.Exam_ID}, '${escapeHtml(exam.Title)}')">
                                <i class="bi bi-list-check"></i> Manage Questions
                            </button>
                        </div>
                    </div>
                `;
            });
            
            html += `</div></div>`;
        });
        
        html += `</div>`;
    });
    
    container.innerHTML = html;
}

// View exam details
function viewExamDetails(examId) {
    const exam = exams.find(e => e.Exam_ID == examId);
    
    if (!exam) {
        showError('Exam not found');
        return;
    }
    
    Swal.fire({
        title: exam.Title,
        html: `
            <div class="text-start">
                <p><strong>Course:</strong> ${exam.Course_Name || 'N/A'}</p>
                <p><strong>Subject:</strong> ${exam.Subject_Name || 'N/A'}</p>
                <p><strong>Description:</strong> ${exam.Description || 'No description'}</p>
                <p><strong>Schedule:</strong> ${exam.Schedule_Date ? new Date(exam.Schedule_Date).toLocaleString() : 'Not scheduled'}</p>
                <p><strong>Duration:</strong> ${exam.Duration || 0} minutes</p>
                <p><strong>Questions:</strong> ${exam.Question_Count || 0}</p>
                <p><strong>Passing Score:</strong> ${exam.Passing_Score || 0}%</p>
                <p><strong>Status:</strong> <span class="badge bg-${exam.Status === 'Published' ? 'success' : 'secondary'}">${exam.Status}</span></p>
                <p><strong>Randomized:</strong> ${exam.Is_Randomized ? 'Yes' : 'No'}</p>
                ${exam.Time_Limit ? `<p><strong>Time Limit:</strong> ${exam.Time_Limit} minutes</p>` : ''}
            </div>
        `,
        icon: 'info',
        width: '600px',
        confirmButtonText: 'Close'
    });
}

// Manage exam questions
let currentExamId = null;
let currentExamQuestions = [];
let allQuestions = [];

function manageQuestions(examId, examTitle) {
    currentExamId = examId;
    
    // Load current exam questions
    loadExamQuestions(examId, examTitle);
}

// Load questions assigned to exam
function loadExamQuestions(examId, examTitle) {
    axios.get(`../../api/masterfiles/exam-questions.php?action=list&exam_id=${examId}`)
        .then(response => {
            if (response.data.success) {
                currentExamQuestions = response.data.data || [];
                showQuestionsModal(examTitle);
            }
        })
        .catch(error => {
            console.error('Error loading exam questions:', error);
            showError('Failed to load exam questions');
        });
}

// Show questions management modal
function showQuestionsModal(examTitle) {
    const questionsHtml = currentExamQuestions.length === 0 
        ? '<p class="text-muted text-center py-4">No questions assigned yet</p>'
        : currentExamQuestions.map((q, index) => `
            <div class="question-item mb-3 p-3 border rounded" style="text-align: left;">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong>${index + 1}. ${escapeHtml(q.Question_Text)}</strong>
                        <div class="mt-2">
                            ${q.choices ? q.choices.map(c => `
                                <div class="choice-item ${c.Is_Correct ? 'text-success' : ''}" style="padding: 4px 0;">
                                    ${c.Is_Correct ? '<i class="bi bi-check-circle-fill me-2"></i>' : '<i class="bi bi-circle me-2"></i>'}
                                    ${escapeHtml(c.Choice_Text)}
                                </div>
                            `).join('') : ''}
                        </div>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="removeQuestion(${q.Question_ID})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    
    Swal.fire({
        title: `Manage Questions - ${examTitle}`,
        html: `
            <div style="text-align: left;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-info">${currentExamQuestions.length} questions</span>
                    <button class="btn btn-sm btn-primary" onclick="openQuestionSelection()">
                        <i class="bi bi-plus-lg"></i> Add Questions
                    </button>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    ${questionsHtml}
                </div>
            </div>
        `,
        width: '800px',
        showConfirmButton: true,
        confirmButtonText: 'Close',
        customClass: {
            htmlContainer: 'text-start'
        }
    });
}

// Remove question from exam
function removeQuestion(questionId) {
    Swal.fire({
        title: 'Remove Question?',
        text: 'This will remove the question from this exam',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.post('../../api/masterfiles/exam-questions.php', {
                action: 'remove',
                exam_id: currentExamId,
                question_id: questionId
            })
            .then(response => {
                if (response.data.success) {
                    Swal.fire('Removed!', 'Question removed from exam', 'success').then(() => {
                        loadExams(); // Refresh exam list
                        // Reload the questions modal
                        const exam = exams.find(e => e.Exam_ID == currentExamId);
                        if (exam) {
                            loadExamQuestions(currentExamId, exam.Title);
                        }
                    });
                } else {
                    showError(response.data.message || 'Failed to remove question');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Failed to remove question');
            });
        }
    });
}

// Open question selection modal
function openQuestionSelection() {
    Swal.fire({
        title: 'Loading questions...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    loadAllQuestions();
}

// Load all available questions
function loadAllQuestions() {
    axios.get('../../api/masterfiles/exam.php?action=get_questions')
        .then(response => {
            if (response.data.success) {
                allQuestions = response.data.questions || [];
                showQuestionSelectionModal();
            } else {
                showError('Failed to load questions');
            }
        })
        .catch(error => {
            console.error('Error loading questions:', error);
            showError('Failed to load questions');
        });
}

// Show question selection modal
function showQuestionSelectionModal() {
    // Filter out already assigned questions
    const assignedIds = currentExamQuestions.map(q => q.Question_ID);
    const availableQuestions = allQuestions.filter(q => !assignedIds.includes(q.Question_ID));
    
    if (availableQuestions.length === 0) {
        Swal.fire({
            title: 'No Questions Available',
            text: 'All questions are already assigned to this exam',
            icon: 'info'
        });
        return;
    }
    
    const questionsHtml = availableQuestions.map(q => `
        <div class="form-check mb-3" style="text-align: left;">
            <input class="form-check-input question-checkbox" type="checkbox" value="${q.Question_ID}" id="q${q.Question_ID}">
            <label class="form-check-label" for="q${q.Question_ID}">
                <strong>${escapeHtml(q.Question_Text)}</strong>
                <br>
                <small class="text-muted">${q.Subject_Name || 'No subject'}</small>
            </label>
        </div>
    `).join('');
    
    Swal.fire({
        title: 'Select Questions to Add',
        html: `
            <div style="text-align: left;">
                <div class="mb-3">
                    <span class="badge bg-primary" id="selectedQuestionCount">0 selected</span>
                </div>
                <div style="max-height: 400px; overflow-y: auto;">
                    ${questionsHtml}
                </div>
            </div>
        `,
        width: '700px',
        showCancelButton: true,
        confirmButtonText: 'Add Selected',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const selectedIds = Array.from(document.querySelectorAll('.question-checkbox:checked')).map(cb => parseInt(cb.value));
            
            if (selectedIds.length === 0) {
                Swal.showValidationMessage('Please select at least one question');
                return false;
            }
            
            return selectedIds;
        },
        didOpen: () => {
            // Add change listeners to checkboxes
            document.querySelectorAll('.question-checkbox').forEach(cb => {
                cb.addEventListener('change', () => {
                    const count = document.querySelectorAll('.question-checkbox:checked').length;
                    document.getElementById('selectedQuestionCount').textContent = `${count} selected`;
                });
            });
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            saveQuestionSelection(result.value);
        }
    });
}

// Save question selection
function saveQuestionSelection(selectedIds) {
    Swal.fire({
        title: 'Adding questions...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    axios.post('../../api/masterfiles/exam-questions.php', {
        action: 'add_multiple',
        exam_id: currentExamId,
        question_ids: selectedIds
    })
    .then(response => {
        if (response.data.success) {
            Swal.fire('Success!', `${selectedIds.length} question(s) added to exam`, 'success').then(() => {
                loadExams(); // Refresh exam list
                // Reload the questions modal
                const exam = exams.find(e => e.Exam_ID == currentExamId);
                if (exam) {
                    loadExamQuestions(currentExamId, exam.Title);
                }
            });
        } else {
            showError(response.data.message || 'Failed to add questions');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to add questions');
    });
}

// Load courses for filter
function loadCourses() {
    axios.get(`${COURSES_API}?action=list`)
        .then(response => {
            if (response.data.success) {
                courses = response.data.data || [];
                populateCourseFilter();
            }
        })
        .catch(error => {
            console.error('Error loading courses:', error);
        });
}

// Populate course filter
function populateCourseFilter() {
    const select = document.getElementById('filterCourse');
    if (!select) return;
    
    select.innerHTML = '<option value="">All courses</option>';
    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course.Course_ID;
        option.textContent = course.Course_Name;
        select.appendChild(option);
    });
}

// Load subjects for filter
function loadSubjects(courseId = '') {
    let url = `${SUBJECTS_API}?action=list`;
    if (courseId) {
        url += `&course_id=${courseId}`;
    }
    
    axios.get(url)
        .then(response => {
            if (response.data.success) {
                populateSubjectFilter(response.data.data || []);
            }
        })
        .catch(error => {
            console.error('Error loading subjects:', error);
        });
}

// Populate subject filter
function populateSubjectFilter(subjects) {
    const select = document.getElementById('filterSubject');
    if (!select) return;
    
    select.innerHTML = '<option value="">All subjects</option>';
    subjects.forEach(subject => {
        const option = document.createElement('option');
        option.value = subject.Subject_ID;
        option.textContent = subject.Subject_Name;
        select.appendChild(option);
    });
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Show error
function showError(message) {
    Swal.fire({
        title: 'Error',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK'
    });
}

// Filter handlers
document.getElementById('filterCourse')?.addEventListener('change', function() {
    currentCourseFilter = this.value;
    loadSubjects(this.value);
    renderExams();
});

document.getElementById('filterSubject')?.addEventListener('change', function() {
    currentSubjectFilter = this.value;
    renderExams();
});

document.getElementById('filterStatus')?.addEventListener('change', function() {
    currentStatusFilter = this.value;
    renderExams();
});

// Initialize
loadCourses();
loadSubjects();
loadExams();
