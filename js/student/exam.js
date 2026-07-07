// Exam Taking JavaScript
let sessionId = null;
let examData = null;
let questions = [];
let currentQuestionIndex = 0;
let answers = {};
let timerInterval = null;
let startTime = null;

// Get session ID from URL
const urlParams = new URLSearchParams(window.location.search);
sessionId = urlParams.get('session_id');

if (!sessionId) {
    Swal.fire({
        icon: 'error',
        title: 'Invalid Session',
        text: 'No exam session found. Redirecting to dashboard...',
        timer: 3000,
        showConfirmButton: false
    }).then(() => {
        window.location.href = '../examinee/dashboard.php';
    });
}

// Load exam questions
async function loadExam() {
    try {
        const response = await axios.get(`../../api/examinee/exam-questions.php?action=get_session_questions&session_id=${sessionId}`);
        
        if (response.data.success) {
            examData = response.data.data;
            questions = examData.questions;
            
            if (questions.length === 0) {
                throw new Error('No questions found for this exam');
            }
            
            // Check if exam time has expired
            if (examData.time_expired) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Time Expired',
                    text: 'The time limit for this exam has expired. Submitting your answers...',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });
                
                // Auto-submit the exam
                setTimeout(() => {
                    submitExam(true);
                }, 2000);
                return;
            }
            
            initializeExam();
            renderQuestion(0);
            startTimer();
            
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('examContainer').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading exam:', error);
        Swal.fire({
            icon: 'error',
            title: 'Failed to Load Exam',
            text: error.response?.data?.message || 'Could not load exam questions',
            confirmButtonText: 'Go to Dashboard'
        }).then(() => {
            window.location.href = '../examinee/dashboard.php';
        });
    }
}

// Initialize exam UI
function initializeExam() {
    document.getElementById('totalQuestions').textContent = questions.length;
    document.getElementById('totalQuestionsNav').textContent = questions.length;
    
    // Display exam title
    if (examData.exam_title) {
        document.getElementById('examTitle').textContent = examData.exam_title;
    }
    
    // Display deadline information
    if (examData.time_started && examData.duration) {
        const startTime = new Date(examData.time_started);
        const deadline = new Date(startTime.getTime() + (examData.duration * 60 * 1000));
        const deadlineStr = deadline.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        document.getElementById('deadlineText').textContent = `Must finish by ${deadlineStr}`;
    }
    
    updateProgress();
}

// Render a question
function renderQuestion(index) {
    if (index < 0 || index >= questions.length) return;
    
    currentQuestionIndex = index;
    const question = questions[index];
    
    const container = document.getElementById('questionsContainer');
    container.innerHTML = `
        <div class="question-card">
            <div class="d-flex align-items-start mb-4">
                <span class="question-number">${index + 1}</span>
                <div class="flex-grow-1">
                    <h5 class="mb-2">${escapeHtml(question.Question_Text)}</h5>
                    <small class="text-muted">
                        <i class="bi bi-bookmark"></i> ${escapeHtml(question.Subject_Name)}
                    </small>
                </div>
            </div>
            
            <div class="choices">
                ${question.answers.map((answer, idx) => `
                    <label class="choice-option ${answers[question.Question_ID] == answer.Choice_ID ? 'selected' : ''}" 
                           onclick="selectAnswer(${question.Question_ID}, ${answer.Choice_ID})">
                        <input type="radio" 
                               name="question_${question.Question_ID}" 
                               value="${answer.Choice_ID}"
                               ${answers[question.Question_ID] == answer.Choice_ID ? 'checked' : ''}>
                        <span>${String.fromCharCode(65 + idx)}. ${escapeHtml(answer.Choice_Text)}</span>
                    </label>
                `).join('')}
            </div>
        </div>
    `;
    
    // Update navigation
    document.getElementById('currentQuestionNum').textContent = index + 1;
    document.getElementById('prevBtn').disabled = index === 0;
    
    if (index === questions.length - 1) {
        document.getElementById('nextBtn').style.display = 'none';
        document.getElementById('submitBtn').style.display = 'block';
    } else {
        document.getElementById('nextBtn').style.display = 'block';
        document.getElementById('submitBtn').style.display = 'none';
    }
}

// Select an answer
async function selectAnswer(questionId, choiceId) {
    answers[questionId] = choiceId;
    
    // Update UI
    const choices = document.querySelectorAll('.choice-option');
    choices.forEach(choice => choice.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    
    // Save answer to server
    try {
        await axios.post('../../api/examinee/submit-answer.php', {
            session_id: sessionId,
            question_id: questionId,
            choice_id: choiceId
        });
        
        updateProgress();
    } catch (error) {
        console.error('Error saving answer:', error);
        // Don't show error to user, just log it
    }
}

// Update progress
function updateProgress() {
    const answeredCount = Object.keys(answers).length;
    const percentage = (answeredCount / questions.length) * 100;
    
    document.getElementById('answeredCount').textContent = answeredCount;
    document.getElementById('progressBar').style.width = percentage + '%';
    
    // Enable/disable submit button based on completion
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        if (answeredCount === questions.length) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-success');
            submitBtn.innerHTML = '<i class="bi bi-check-circle"></i> Submit Exam';
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-secondary');
            submitBtn.innerHTML = `<i class="bi bi-lock"></i> Answer All Questions (${answeredCount}/${questions.length})`;
        }
    }
}

// Navigation
function nextQuestion() {
    if (currentQuestionIndex < questions.length - 1) {
        renderQuestion(currentQuestionIndex + 1);
    }
}

function previousQuestion() {
    if (currentQuestionIndex > 0) {
        renderQuestion(currentQuestionIndex - 1);
    }
}

// Timer
function startTimer() {
    // Calculate elapsed time from session start
    const sessionStart = new Date(examData.time_started).getTime();
    const duration = examData.duration * 60 * 1000; // Duration in milliseconds
    
    timerInterval = setInterval(() => {
        const now = Date.now();
        const elapsed = now - sessionStart;
        const remaining = duration - elapsed;
        
        // If time expired, auto-submit
        if (remaining <= 0) {
            clearInterval(timerInterval);
            Swal.fire({
                icon: 'warning',
                title: 'Time Expired',
                text: 'The time limit has been reached. Submitting your exam...',
                allowOutsideClick: false,
                showConfirmButton: false
            });
            setTimeout(() => {
                submitExam(true);
            }, 2000);
            return;
        }
        
        // Display remaining time
        const hours = Math.floor(remaining / 3600000);
        const minutes = Math.floor((remaining % 3600000) / 60000);
        const seconds = Math.floor((remaining % 60000) / 1000);
        
        const timeString = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        document.getElementById('timer').textContent = timeString;
        
        // Warning when less than 10 minutes remaining
        if (remaining < 600000) { // 10 minutes
            document.getElementById('timer').classList.add('warning');
        }
        
        // Show warning at 5 minutes
        if (remaining < 300000 && remaining > 299000) {
            Swal.fire({
                icon: 'warning',
                title: '5 Minutes Remaining',
                text: 'You have 5 minutes left to complete the exam.',
                timer: 3000,
                showConfirmButton: false
            });
        }
    }, 1000);
}

function pad(num) {
    return num.toString().padStart(2, '0');
}

// Submit exam
async function submitExam(autoSubmit = false) {
    const answeredCount = Object.keys(answers).length;
    const unanswered = questions.length - answeredCount;
    
    // If not auto-submit, check if all questions are answered
    if (!autoSubmit) {
        if (unanswered > 0) {
            Swal.fire({
                title: 'Incomplete Exam',
                html: `
                    <p>You must answer all questions before submitting.</p>
                    <p class="text-danger"><strong>${unanswered} question(s) remaining</strong></p>
                `,
                icon: 'warning',
                confirmButtonText: 'Continue Answering',
                confirmButtonColor: '#2563eb'
            });
            return;
        }
        
        // All questions answered, ask for confirmation
        const result = await Swal.fire({
            title: 'Submit Exam?',
            text: 'Are you sure you want to submit your exam? You cannot change your answers after submission.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Submit',
            cancelButtonText: 'Review Answers',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d'
        });
        
        if (!result.isConfirmed) return;
    }
    
    try {
        clearInterval(timerInterval);
        
        const response = await axios.post('../../api/examinee/submit-exam.php', {
            session_id: sessionId
        });
        
        if (response.data.success) {
            const data = response.data.data;
            
            Swal.fire({
                title: data.remarks === 'Passed' ? 'Congratulations!' : 'Exam Completed',
                html: `
                    <div class="text-center">
                        <h3 class="mb-3">${data.remarks}</h3>
                        <p class="mb-2">Score: <strong>${data.score} / ${data.total_questions}</strong></p>
                        <p class="mb-2">Percentage: <strong>${data.percentage}%</strong></p>
                        <p class="text-muted">Passing Score: ${data.passing_score}%</p>
                    </div>
                `,
                icon: data.remarks === 'Passed' ? 'success' : 'info',
                confirmButtonText: 'View Results',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '../examinee/dashboard.php';
            });
        }
    } catch (error) {
        console.error('Error submitting exam:', error);
        Swal.fire({
            icon: 'error',
            title: 'Submission Failed',
            text: error.response?.data?.message || 'Failed to submit exam. Please try again.'
        });
        
        // Only restart timer if not auto-submit
        if (!autoSubmit) {
            startTimer();
        }
    }
}

// Prevent accidental page close
window.addEventListener('beforeunload', (e) => {
    if (sessionId && timerInterval) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// Initialize
loadExam();
