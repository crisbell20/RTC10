<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PNP Regional Training Center X - Web based Examination System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.min.css">
</head>
<body>
    <div class="container-fluid p-0 vh-100">
        <div class="row g-0 h-100">
            <div class="col-md-5 d-flex flex-column justify-content-center align-items-center p-5 position-relative" style="background-color: #1a3a52;">
                <div class="text-center">
                    <div class="mb-4" style="position: relative; width: 200px; height: 220px; margin: 0 auto;">
                        <div style="width: 200px; height: 220px; border-radius: 50%; background-color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2); padding: 10px;">
                            <img src="assets/images/pnp-badge.png" alt="PNP Badge" style="height: 168px; object-fit: contain;">
                        </div>
                    </div>
                    <h2 class="fw-bold text-white">PNP Regional Training Center X</h2>
                    <p class="text-white-50 mt-2">Web-Based Examination System</p>
                </div>
            </div>
            <div class="col-md-7 bg-white d-flex align-items-center justify-content-center">
                <div class="login-form-container p-5" style="max-width: 380px; width: 100%;"> 
                    <h5 class="mb-4 text-left text-dark fw-bold">LOGIN</h5>
                    
                    <form id="loginForm">
                        <div class="mb-3">
                            <input type="email" class="form-control border-0 border-bottom" id="email" name="email" placeholder="Username" required title="Please enter a valid email address" style="border-radius: 0; border-bottom: 2px solid #dee2e6; font-size: 0.95rem;">
                        </div>
                        
                        <div class="mb-4">
                            <div class="input-group" style="border-bottom: 2px solid #dee2e6;">
                                <input type="password" class="form-control border-0" id="password" name="password" placeholder="Password" required style="border-radius: 0; font-size: 0.95rem;" autocomplete="current-password">
                                <button class="btn btn-outline-none border-0" id="togglePassword" type="button" style="background-color: transparent; color: #6c757d;">
                                    <i class="bi bi-eye-slash-fill"></i>
                                </button>
                            </div>
                            <small class="text-muted">Debug: <span id="passwordDebug">Type password to see length</span></small>
                        </div>
                        <div class="d-grid gap-2 mt-5">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold" style="background-color: #1a3a52; border-color: #1a3a52;">LOGIN</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.3/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Debug password input
        document.getElementById('password').addEventListener('input', function() {
            document.getElementById('passwordDebug').textContent = 'Length: ' + this.value.length;
        });
        
        document.getElementById('togglePassword').addEventListener('click', function (e) {
            e.preventDefault();
            const passwordInput = document.getElementById('password');
            const toggleIcon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('bi-eye-slash-fill');
                toggleIcon.classList.add('bi-eye-fill');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('bi-eye-fill');
                toggleIcon.classList.add('bi-eye-slash-fill');
            }
        });
    </script>
    
    <script src="js/auth/app.js"></script>
</body>
</html>
