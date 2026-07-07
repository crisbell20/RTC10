const API_BASE = '../../api/masterfiles/';
const COURSES_API = API_BASE + 'courses.php';
const SECTIONS_API = API_BASE + 'sections.php';
const BATCHES_API = API_BASE + 'batches.php';
const USERS_API = API_BASE + 'users.php';

let addBatchModal = null;
let addSectionModal = null;
let addCourseModal = null;

function initModals() {
    const batchModalEl = document.getElementById('addBatchModal');
    const sectionModalEl = document.getElementById('addSectionModal');
    const courseModalEl = document.getElementById('addCourseModal');

    if (batchModalEl) {
        addBatchModal = bootstrap.Modal.getOrCreateInstance(batchModalEl);
    }
    if (sectionModalEl) {
        addSectionModal = bootstrap.Modal.getOrCreateInstance(sectionModalEl);
    }
    if (courseModalEl) {
        addCourseModal = bootstrap.Modal.getOrCreateInstance(courseModalEl);
    }
}

function hideModal(modal) {
    if (modal) {
        modal.hide();
    }
}

function showAlert(message, type = 'success') {
    const alertDiv = document.getElementById('messageAlert');
    alertDiv.innerHTML = `<div class="alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>`;
}

function loadCourses() {
    axios.get(`${COURSES_API}?action=tree`)
        .then(response => {
            if (response.data.success) {
                renderCourses(response.data.data || []);
            } else {
                setCoursesLoadError('Failed to load courses');
                showAlert(response.data.message || 'Failed to load courses', 'error');
            }
        })
        .catch(error => {
            const msg = error.response?.data?.message || 'Error loading courses';
            setCoursesLoadError(msg);
            showAlert(msg, 'error');
            console.error(error);
        });
}

function setCoursesLoadError(message) {
    const container = document.getElementById('coursesContainer');
    if (container) {
        container.innerHTML = `<div class="text-center text-danger py-4">${escapeHtml(message)}</div>`;
    }
}

function renderCourses(courses) {
    const container = document.getElementById('coursesContainer');

    if (courses.length === 0) {
        container.innerHTML = '<div class="text-center text-muted py-4">No courses found</div>';
        return;
    }

    container.innerHTML = courses.map(course => `
        <div class="course-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5>${escapeHtml(course.Course_Name)}</h5>
                    ${course.Details ? `<p class="text-muted mb-1">${escapeHtml(course.Details)}</p>` : ''}
                    ${course.Duration ? `<small class="text-muted">Duration: ${escapeHtml(course.Duration)}</small>` : ''}
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-action="add-batch" data-course-id="${course.Course_ID}">
                        <i class="bi bi-plus"></i> Add Batch
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-course" data-course-id="${course.Course_ID}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="mt-3" id="batches-${course.Course_ID}">
                ${renderBatches(course.batches || [], course.Course_ID)}
            </div>
        </div>
    `).join('');
}

function renderBatches(batches, courseId) {
    if (!batches || batches.length === 0) {
        return '<div class="text-muted small ms-3">No batches yet</div>';
    }

    return batches.map(batch => `
        <div class="section-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${escapeHtml(batch.Batch_Name)}</strong>
                    ${batch.Date_Started ? `<small class="text-muted ms-2">Start: ${new Date(batch.Date_Started).toLocaleDateString()}</small>` : ''}
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-info" data-action="add-section" data-batch-id="${batch.Batch_ID}">
                        <i class="bi bi-plus"></i> Add Section
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-batch" data-batch-id="${batch.Batch_ID}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="mt-2" id="sections-${batch.Batch_ID}">
                ${renderSections(batch.sections || [], courseId)}
            </div>
        </div>
    `).join('');
}

function renderSections(sections, courseId) {
    if (!sections || sections.length === 0) {
        return '<div class="text-muted small ms-3">No sections yet</div>';
    }

    return sections.map(section => `
        <div class="batch-item">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>${escapeHtml(section.Section_Name)}</strong>
                    <small class="text-muted ms-2">Capacity: ${section.Capacity}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-info" data-action="view-section-users" data-section-id="${section.Section_ID}" title="View/Assign Users">
                        <i class="bi bi-people"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete-section" data-section-id="${section.Section_ID}" data-course-id="${courseId}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div id="section-users-${section.Section_ID}" class="batch-users ms-3"></div>
        </div>
    `).join('');
}

function addBatch(courseId) {
    const courseIdInput = document.getElementById('batchCourseId');
    if (!courseIdInput) {
        showAlert('Batch form is not available', 'error');
        return;
    }
    courseIdInput.value = courseId;
    if (addBatchModal) {
        addBatchModal.show();
    }
}

function addSection(batchId) {
    const batchIdInput = document.getElementById('sectionBatchId');
    if (!batchIdInput) {
        showAlert('Section form is not available', 'error');
        return;
    }
    batchIdInput.value = batchId;
    if (addSectionModal) {
        addSectionModal.show();
    }
}

function deleteCourse(courseId) {
    Swal.fire({
        title: 'Delete Course?',
        text: 'This will also delete all sections and batches',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            axios.post(COURSES_API, { action: 'delete', course_id: courseId })
                .then(() => {
                    showAlert('Course deleted successfully', 'success');
                    loadCourses();
                })
                .catch(error => {
                    showAlert(error.response?.data?.message || 'Error deleting course', 'error');
                });
        }
    });
}

function deleteSection(sectionId, courseId) {
    Swal.fire({
        title: 'Delete Section?',
        text: 'This will also delete all examinee assignments in this section',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            axios.post(SECTIONS_API, { action: 'delete', section_id: sectionId })
                .then(() => {
                    showAlert('Section deleted successfully', 'success');
                    loadCourses();
                })
                .catch(error => {
                    showAlert(error.response?.data?.message || 'Error deleting section', 'error');
                });
        }
    });
}

function deleteBatch(batchId) {
    Swal.fire({
        title: 'Delete Batch?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            axios.post(BATCHES_API, { action: 'delete', batch_id: batchId })
                .then(() => {
                    showAlert('Batch deleted successfully', 'success');
                    loadCourses();
                })
                .catch(error => {
                    showAlert(error.response?.data?.message || 'Error deleting batch', 'error');
                });
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Form handlers
function bindCourseForms() {
    const addCourseForm = document.getElementById('addCourseForm');
    const addBatchForm = document.getElementById('addBatchForm');
    const addSectionForm = document.getElementById('addSectionForm');

    if (!addCourseForm || !addBatchForm || !addSectionForm) {
        console.error('Manage Courses forms are missing from the page');
        return;
    }

    addCourseForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = {
            course_name: formData.get('course_name'),
            details: formData.get('details'),
            duration: formData.get('duration'),
            action: 'add'
        };

        axios.post(COURSES_API, data)
            .then(response => {
                if (response.data.success) {
                    showAlert('Course added successfully', 'success');
                    this.reset();
                    hideModal(addCourseModal);
                    loadCourses();
                } else {
                    showAlert(response.data.message || 'Failed to add course', 'error');
                }
            })
            .catch(error => {
                showAlert(error.response?.data?.message || 'Error adding course', 'error');
            });
    });

    addBatchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const courseId = formData.get('course_id');
        const batchName = formData.get('batch_name');

        if (!courseId) {
            showAlert('Course is missing. Close the modal and click Add Batch again.', 'error');
            return;
        }
        if (!batchName || !String(batchName).trim()) {
            showAlert('Batch name is required', 'error');
            return;
        }

        const data = {
            course_id: courseId,
            batch_name: batchName,
            date_started: formData.get('date_started') || null,
            date_ended: formData.get('date_ended') || null,
            action: 'add'
        };

        axios.post(BATCHES_API, data)
            .then(response => {
                if (response.data.success) {
                    showAlert(response.data.message || 'Batch added successfully', 'success');
                    this.reset();
                    hideModal(addBatchModal);
                    loadCourses();
                } else {
                    showAlert(response.data.message || 'Failed to add batch', 'error');
                }
            })
            .catch(error => {
                showAlert(error.response?.data?.message || 'Error adding batch', 'error');
            });
    });

    addSectionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const batchId = formData.get('batch_id');
        const sectionName = formData.get('section_name');

        if (!batchId) {
            showAlert('Batch is missing. Close the modal and click Add Section again.', 'error');
            return;
        }
        if (!sectionName || !String(sectionName).trim()) {
            showAlert('Section name is required', 'error');
            return;
        }

        const data = {
            batch_id: batchId,
            section_name: sectionName,
            capacity: formData.get('capacity') || 40,
            action: 'add'
        };

        axios.post(SECTIONS_API, data)
            .then(response => {
                if (response.data.success) {
                    showAlert(response.data.message || 'Section added successfully', 'success');
                    this.reset();
                    hideModal(addSectionModal);
                    loadCourses();
                } else {
                    showAlert(response.data.message || 'Failed to add section', 'error');
                }
            })
            .catch(error => {
                showAlert(error.response?.data?.message || 'Error adding section', 'error');
            });
    });
}

function bindCourseActions() {
    const container = document.getElementById('coursesContainer');
    if (!container) {
        return;
    }

    container.addEventListener('click', function(e) {
        const button = e.target.closest('[data-action]');
        if (!button) {
            return;
        }

        const action = button.dataset.action;

        if (action === 'add-batch') {
            e.preventDefault();
            addBatch(button.dataset.courseId);
            return;
        }

        if (action === 'add-section') {
            e.preventDefault();
            addSection(button.dataset.batchId);
            return;
        }

        if (action === 'delete-course') {
            e.preventDefault();
            deleteCourse(button.dataset.courseId);
            return;
        }

        if (action === 'delete-batch') {
            e.preventDefault();
            deleteBatch(button.dataset.batchId);
            return;
        }

        if (action === 'delete-section') {
            e.preventDefault();
            deleteSection(button.dataset.sectionId, button.dataset.courseId);
            return;
        }

        if (action === 'view-section-users') {
            e.preventDefault();
            viewSectionUsers(button.dataset.sectionId);
            return;
        }
    });
}

// Dropdown handlers
document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const submenu = this.nextElementSibling;
        this.classList.toggle('active');
        submenu.classList.toggle('show');
    });
});

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

// View/assign users to section
function viewSectionUsers(sectionId) {
    const container = document.getElementById(`section-users-${sectionId}`);
    if (!container) {
        return;
    }

    container.innerHTML = '<div class="text-muted small">Loading...</div>';

    axios.get(`${BATCHES_API}?action=users_by_section&section_id=${sectionId}`)
        .then(response => {
            if (response.data.success) {
                const users = response.data.data || [];

                if (users.length === 0) {
                    container.innerHTML = '<div class="text-muted small">No users assigned</div>';
                } else {
                    container.innerHTML = users.map(user => `
                        <div class="small mb-1">
                            ${escapeHtml(user.Fullname)} (${escapeHtml(user.Email)})
                            <button class="btn btn-sm btn-outline-danger ms-2" data-action="remove-section-user" data-user-id="${user.User_ID}" data-section-id="${sectionId}" title="Remove">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    `).join('');
                }

                container.innerHTML += `
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-action="assign-section-users" data-section-id="${sectionId}">
                        <i class="bi bi-person-plus"></i> Assign Examinees
                    </button>
                `;

                container.querySelector('[data-action="assign-section-users"]')?.addEventListener('click', () => {
                    assignUserToSection(sectionId);
                });
                container.querySelectorAll('[data-action="remove-section-user"]').forEach(btn => {
                    btn.addEventListener('click', () => {
                        removeFromSection(btn.dataset.userId, btn.dataset.sectionId);
                    });
                });
            } else {
                container.innerHTML = '<div class="text-danger small">Failed to load assigned users</div>';
            }
        })
        .catch(error => {
            console.error('Error loading section users:', error);
            container.innerHTML = '<div class="text-danger small">Error loading assigned users</div>';
        });
}

// Assign users to section (multi-select with checkboxes via SweetAlert)
function assignUserToSection(sectionId) {
    Promise.all([
        axios.get(`${BATCHES_API}?action=users_by_section&section_id=${sectionId}`),
        axios.get(`${BATCHES_API}?action=section_capacity&section_id=${sectionId}`),
        axios.get(`${USERS_API}?action=list`)
    ])
        .then(([assignedResponse, capacityResponse, usersResponse]) => {
            if (!usersResponse.data.success) {
                showAlert('Error loading examinees', 'error');
                return;
            }

            const capacityInfo = capacityResponse.data.success ? capacityResponse.data.data : null;
            if (!capacityInfo) {
                showAlert('Unable to load section capacity information', 'error');
                return;
            }

            if (capacityInfo.available_slots === 0) {
                Swal.fire(
                    'Section Full',
                    `Section capacity exceeded. Maximum: ${capacityInfo.capacity}, Currently Assigned: ${capacityInfo.assigned_count}.`,
                    'warning'
                );
                return;
            }

            const assignedIds = new Set(
                (assignedResponse.data.success ? assignedResponse.data.data || [] : [])
                    .map(u => String(u.User_ID))
            );

            const sectionUserIds = new Set(
                (capacityInfo.assigned_user_ids || []).map(id => String(id))
            );

            const examinees = usersResponse.data.data
                .filter(u => u.Role_Name === 'Examinee' && !assignedIds.has(String(u.User_ID)));

            if (examinees.length === 0) {
                Swal.fire('No examinees available', 'All examinees are already assigned to this section, or no examinees exist.', 'info');
                return;
            }

            const checkboxes = examinees.map(e => `
                <div class="form-check text-start">
                    <input class="form-check-input examinee-checkbox" type="checkbox"
                        value="${e.User_ID}" id="examinee-${e.User_ID}">
                    <label class="form-check-label" for="examinee-${e.User_ID}">
                        ${escapeHtml(e.Fullname)} (${escapeHtml(e.Email)})
                    </label>
                </div>
            `).join('');

            Swal.fire({
                title: 'Assign Examinees to Section',
                html: `
                    <div class="text-start mb-2">
                        <small class="text-muted">
                            Section: <strong>${escapeHtml(capacityInfo.section_name)}</strong><br>
                            Capacity: <strong>${capacityInfo.capacity}</strong> |
                            Assigned: <strong>${capacityInfo.assigned_count}</strong> |
                            Available slots: <strong>${capacityInfo.available_slots}</strong>
                        </small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Select examinee(s)</label>
                        <div>
                            <button type="button" class="btn btn-sm btn-link p-0 me-2" id="selectAllExaminees">Select All</button>
                            <button type="button" class="btn btn-sm btn-link p-0" id="clearAllExaminees">Clear</button>
                        </div>
                    </div>
                    <div id="examineeCheckboxList" class="border rounded p-2 text-start"
                        style="max-height: 240px; overflow-y: auto;">
                        ${checkboxes}
                    </div>
                    <small class="text-muted d-block mt-2 text-start" id="examineeSelectionCount">0 selected</small>
                `,
                showCancelButton: true,
                confirmButtonText: 'Assign Selected',
                width: '34rem',
                didOpen: () => {
                    const updateCount = () => {
                        const count = document.querySelectorAll('.examinee-checkbox:checked').length;
                        const countEl = document.getElementById('examineeSelectionCount');
                        if (countEl) {
                            countEl.textContent = `${count} selected`;
                        }
                    };

                    document.querySelectorAll('.examinee-checkbox').forEach(cb => {
                        cb.addEventListener('change', updateCount);
                    });

                    document.getElementById('selectAllExaminees')?.addEventListener('click', (e) => {
                        e.preventDefault();
                        document.querySelectorAll('.examinee-checkbox').forEach(cb => { cb.checked = true; });
                        updateCount();
                    });

                    document.getElementById('clearAllExaminees')?.addEventListener('click', (e) => {
                        e.preventDefault();
                        document.querySelectorAll('.examinee-checkbox').forEach(cb => { cb.checked = false; });
                        updateCount();
                    });
                },
                preConfirm: () => {
                    const selected = Array.from(document.querySelectorAll('.examinee-checkbox:checked'))
                        .map(cb => cb.value);
                    if (selected.length === 0) {
                        Swal.showValidationMessage('Please select at least one examinee');
                        return false;
                    }

                    const newAssignments = selected.filter(id => !sectionUserIds.has(String(id))).length;
                    if (newAssignments > capacityInfo.available_slots) {
                        Swal.showValidationMessage(
                            `Section capacity exceeded. Maximum: ${capacityInfo.capacity}, Currently Assigned: ${capacityInfo.assigned_count}.`
                        );
                        return false;
                    }

                    return selected;
                }
            }).then(result => {
                if (result.isConfirmed) {
                    axios.post(`${BATCHES_API}`, {
                        action: 'assign_users',
                        section_id: sectionId,
                        user_ids: result.value,
                        status: 'Active'
                    })
                    .then(response => {
                        const count = response.data.assigned_count ?? result.value.length;
                        showAlert(response.data.message || `${count} examinee(s) assigned successfully`, 'success');
                        viewSectionUsers(sectionId);
                    })
                    .catch(error => {
                        showAlert(error.response?.data?.message || 'Error assigning examinees', 'error');
                    });
                }
            });
        })
        .catch(error => {
            console.error('Error loading examinees:', error);
            showAlert('Error loading examinees', 'error');
        });
}

// Remove user from section
function removeFromSection(userId, sectionId) {
    Swal.fire({
        title: 'Remove from Section?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel'
    }).then(result => {
        if (result.isConfirmed) {
            axios.post(`${BATCHES_API}`, {
                action: 'remove_user',
                user_id: userId,
                section_id: sectionId
            })
            .then(() => {
                showAlert('User removed from section', 'success');
                viewSectionUsers(sectionId);
            })
            .catch(error => {
                showAlert(error.response?.data?.message || 'Error removing user', 'error');
            });
        }
    });
}

// Load courses on page load
function bootstrapCoursesPage() {
    initModals();
    bindCourseForms();
    bindCourseActions();
    loadCourses();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapCoursesPage);
} else {
    bootstrapCoursesPage();
}
