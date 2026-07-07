document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const emailField = document.getElementById('email');
    const passwordField = document.getElementById('password');
    
    const email = emailField.value.trim();
    const password = passwordField.value;

    console.log('Login attempt:', { 
        email, 
        passwordLength: password.length,
        passwordFieldExists: !!passwordField,
        passwordFieldValue: password ? 'HAS_VALUE' : 'EMPTY'
    });

    if (!email || !password) {
        Swal.fire('Error', 'Please enter both email and password', 'error');
        return;
    }

    // Additional validation
    if (password.length === 0) {
        console.error('Password field is empty despite passing validation!');
        Swal.fire('Error', 'Password field appears empty. Please try typing your password again.', 'error');
        return;
    }

    try {
        // Log what we're sending
        const payload = {
            email: email,
            password: password
        };
        console.log('Sending payload:', { 
            email: payload.email, 
            passwordLength: payload.password.length,
            passwordExists: !!payload.password 
        });
        
        const response = await axios.post('api/auth/auth.php', payload, {
            headers: {
                'Content-Type': 'application/json'
            }
        });

        if (response.data.success) {
            // Check if CAPTCHA is required (Admin/CCMD)
            if (response.data.requires_captcha) {
                // Generate simple math CAPTCHA
                const num1 = Math.floor(Math.random() * 10) + 1;
                const num2 = Math.floor(Math.random() * 10) + 1;
                const correctAnswer = num1 + num2;
                
                Swal.fire({
                    title: 'Security Verification',
                    html: `
                        <div class="text-center mb-3">
                            <i class="bi bi-shield-check" style="font-size: 3rem; color: #2563eb;"></i>
                        </div>
                        <p class="mb-3">Please solve this simple math problem to continue:</p>
                        <div class="alert alert-info">
                            <h3 class="mb-0">${num1} + ${num2} = ?</h3>
                        </div>
                        <input type="number" id="captchaAnswer" class="swal2-input" placeholder="Enter your answer" autofocus>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Verify',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#2563eb',
                    allowOutsideClick: false,
                    preConfirm: () => {
                        const answer = document.getElementById('captchaAnswer').value;
                        if (!answer) {
                            Swal.showValidationMessage('Please enter an answer');
                            return false;
                        }
                        if (parseInt(answer) !== correctAnswer) {
                            Swal.showValidationMessage('Incorrect answer. Please try again.');
                            return false;
                        }
                        return true;
                    }
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        // CAPTCHA passed, complete login
                        try {
                            await axios.post('api/auth/verify-captcha.php', {
                                user_id: response.data.user.id
                            });
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Verified!',
                                text: 'Login successful',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                const role = response.data.user.role;
                                switch (role) {
                                    case 'Admin':
                                        window.location.href = 'html/admin/dashboard.php';
                                        break;
                                    case 'CCMD':
                                        window.location.href = 'html/ccmd/dashboard.php';
                                        break;
                                    default:
                                        window.location.href = 'html/examinee/dashboard.php';
                                }
                            });
                        } catch (error) {
                            Swal.fire('Error', 'Verification failed', 'error');
                        }
                    } else {
                        // User cancelled
                        Swal.fire('Cancelled', 'Login cancelled', 'info');
                    }
                });
            } else {
                // No CAPTCHA required (Examinee)
                Swal.fire('Success', 'Login successful', 'success').then(() => {
                    window.location.href = 'html/examinee/dashboard.php';
                });
            }
        } else {
            Swal.fire('Error', response.data.message || 'Login failed', 'error');
        }
    } catch (error) {
        const msg = error.response?.data?.message || error.message || 'An error occurred during login';
        Swal.fire('Error', msg, 'error');
        console.error('Login error:', error.response?.data || error);
    }
});
