// Chart instances
let performanceChart, passFailChart, subjectsChart, participationChart;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    loadCourses();
    loadReports();

    // Logout handler
    document.getElementById('logoutBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Logout',
            text: 'Are you sure you want to logout?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../../api/auth/logout.php';
            }
        });
    });

    // Course filter change
    document.getElementById('filterCourse').addEventListener('change', function() {
        const courseId = this.value;
        loadSubjects(courseId);
    });
});

// Initialize all charts
function initializeCharts() {
    // Performance Trend Chart (Line)
    const perfCtx = document.getElementById('performanceChart').getContext('2d');
    performanceChart = new Chart(perfCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Average Score (%)',
                data: [],
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Pass/Fail Chart (Pie)
    const pfCtx = document.getElementById('passFailChart').getContext('2d');
    passFailChart = new Chart(pfCtx, {
        type: 'pie',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#10b981', '#ef4444']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Top Subjects Chart (Bar)
    const subjCtx = document.getElementById('subjectsChart').getContext('2d');
    subjectsChart = new Chart(subjCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Average Score (%)',
                data: [],
                backgroundColor: '#a855f7'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Participation Chart (Bar)
    const partCtx = document.getElementById('participationChart').getContext('2d');
    participationChart = new Chart(partCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Number of Examinees',
                data: [],
                backgroundColor: '#f59e0b'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Load courses for filter
async function loadCourses() {
    try {
        const response = await axios.get('../../api/masterfiles/courses.php?action=list');
        const courses = response.data.data;

        const select = document.getElementById('filterCourse');
        select.innerHTML = '<option value="">All courses</option>';
        
        courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.Course_ID;
            option.textContent = course.Course_Name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

// Load subjects for filter
async function loadSubjects(courseId) {
    try {
        const url = courseId 
            ? `../../api/masterfiles/subjects.php?action=list&course_id=${courseId}`
            : '../../api/masterfiles/subjects.php?action=list';
        
        const response = await axios.get(url);
        const subjects = response.data.data;

        const select = document.getElementById('filterSubject');
        select.innerHTML = '<option value="">All subjects</option>';
        
        subjects.forEach(subject => {
            const option = document.createElement('option');
            option.value = subject.Subject_ID;
            option.textContent = subject.Subject_Name;
            select.appendChild(option);
        });
    } catch (error) {
        console.error('Error loading subjects:', error);
    }
}

// Load all reports data
async function loadReports() {
    const dateRange = document.getElementById('dateRange').value;
    const courseId = document.getElementById('filterCourse').value;
    const subjectId = document.getElementById('filterSubject').value;

    const params = new URLSearchParams({
        date_range: dateRange,
        ...(courseId && { course_id: courseId }),
        ...(subjectId && { subject_id: subjectId })
    });

    try {
        // Load all data in parallel
        await Promise.all([
            loadMetrics(params),
            loadPerformanceTrend(params),
            loadPassFail(params),
            loadTopSubjects(params),
            loadParticipation(params),
            loadDetailedResults(params)
        ]);
    } catch (error) {
        console.error('Error loading reports:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load reports data'
        });
    }
}

// Load key metrics
async function loadMetrics(params) {
    try {
        const response = await axios.get(`../../api/masterfiles/reports.php?action=metrics&${params}`);
        const data = response.data.data;

        document.getElementById('totalExaminees').textContent = data.total_examinees;
        document.getElementById('totalExams').textContent = data.total_exams;
        document.getElementById('completionRate').textContent = data.completion_rate + '%';
        document.getElementById('avgScore').textContent = data.avg_score + '%';
    } catch (error) {
        console.error('Error loading metrics:', error);
    }
}

// Load performance trend
async function loadPerformanceTrend(params) {
    try {
        const response = await axios.get(`../../api/masterfiles/reports.php?action=performance_trend&${params}`);
        const data = response.data.data;

        const labels = data.map(item => item.date);
        const scores = data.map(item => parseFloat(item.avg_score).toFixed(1));

        performanceChart.data.labels = labels;
        performanceChart.data.datasets[0].data = scores;
        performanceChart.update();
    } catch (error) {
        console.error('Error loading performance trend:', error);
    }
}

// Load pass/fail distribution
async function loadPassFail(params) {
    try {
        const response = await axios.get(`../../api/masterfiles/reports.php?action=pass_fail&${params}`);
        const data = response.data.data;

        passFailChart.data.datasets[0].data = [
            parseInt(data.passed) || 0,
            parseInt(data.failed) || 0
        ];
        passFailChart.update();
    } catch (error) {
        console.error('Error loading pass/fail data:', error);
    }
}

// Load top subjects
async function loadTopSubjects(params) {
    try {
        const response = await axios.get(`../../api/masterfiles/reports.php?action=top_subjects&${params}`);
        const data = response.data.data;

        const labels = data.map(item => item.Subject_Name);
        const scores = data.map(item => parseFloat(item.avg_score).toFixed(1));

        subjectsChart.data.labels = labels;
        subjectsChart.data.datasets[0].data = scores;
        subjectsChart.update();
    } catch (error) {
        console.error('Error loading top subjects:', error);
    }
}

// Load participation data
async function loadParticipation(params) {
    try {
        const response = await axios.get(`../../api/masterfiles/reports.php?action=participation&${params}`);
        const data = response.data.data;

        const labels = data.map(item => item.exam_title);
        const counts = data.map(item => parseInt(item.participant_count));

        participationChart.data.labels = labels;
        participationChart.data.datasets[0].data = counts;
        participationChart.update();
    } catch (error) {
        console.error('Error loading participation data:', error);
    }
}

// Load detailed results table
async function loadDetailedResults(params) {
    try {
        const response = await axios.get(`../../api/masterfiles/reports.php?action=detailed_results&${params}`);
        const data = response.data.data;

        const tbody = document.getElementById('resultsTableBody');
        
        if (data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox"></i> No results found
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = data.map(row => {
            const statusClass = row.status === 'Passed' ? 'text-success' : 'text-danger';
            const date = new Date(row.Submission_Date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });

            return `
                <tr>
                    <td>${escapeHtml(row.examinee_name)}</td>
                    <td>${escapeHtml(row.exam_title)}</td>
                    <td>${escapeHtml(row.Subject_Name)}</td>
                    <td>${date}</td>
                    <td>${row.Score}</td>
                    <td>${row.Percentage}%</td>
                    <td><span class="${statusClass} fw-bold">${row.status}</span></td>
                </tr>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading detailed results:', error);
    }
}

// Export report
function exportReport() {
    Swal.fire({
        title: 'Export Report',
        text: 'Export functionality will be implemented soon',
        icon: 'info'
    });
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
