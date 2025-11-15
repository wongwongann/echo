<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'logs/php_errors.log');

if (ob_get_level()) ob_end_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #4a5568;
            --primary-dark: #2d3748;
            --accent-color: #718096;
            --accent-light: #a0aec0;
            --background: #f7fafc;
            --card-bg: #ffffff;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --border-color: #e2e8f0;
            --success-color: #48bb78;
            --error-color: #f56565;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1), 0 2px 4px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1), 0 6px 10px rgba(0,0,0,0.08);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            position: relative;
            overflow: hidden;
        }

        .bg-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0.04;
            background-image: 
                repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(0,0,0,.05) 35px, rgba(0,0,0,.05) 70px),
                repeating-linear-gradient(-45deg, transparent, transparent 35px, rgba(0,0,0,.03) 35px, rgba(0,0,0,.03) 70px);
            animation: patternMove 20s linear infinite;
            z-index: 1;
        }

        @keyframes patternMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .floating-shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(7, 36, 81, 1), rgba(0, 94, 255, 0.1));
            filter: blur(28px);
            z-index: 5; 
            pointer-events: none; 
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 400px;
            height: 400px;
            bottom: -200px;
            right: -200px;
            animation-delay: 5s;
        }

        .shape-3 {
            width: 250px;
            height: 250px;
            top: 50%;
            left: -125px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 40px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .logo-section {
            text-align: center;
            margin-bottom: 35px;
        }

        .logo {
            width: 100px;
            height: 100px;
            /* background: linear-gradient(135deg, var(--primary-color), var(--accent-color)); */
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            /* box-shadow: var(--shadow-md); */
            transition: var(--transition);
        }

        .logo:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .logo i {
            font-size: 28px;
            color: white;
        }

        .company-name {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .tagline {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 400;
        }

        .form-section {
            margin-top: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--accent-light);
            font-size: 16px;
            transition: var(--transition);
        }

        .form-input {
            width: 100%;
            padding: 12px 16px 12px 44px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            font-weight: 400;
            color: var(--text-primary);
            background: var(--card-bg);
            transition: var(--transition);
            outline: none;
        }

        .form-input:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(113, 128, 150, 0.1);
        }

        .form-input:focus + .input-icon {
            color: var(--accent-color);
        }

        .form-input::placeholder {
            color: var(--accent-light);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--accent-light);
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--accent-color);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
        }

        .checkbox-input {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
            accent-color: var(--accent-color);
        }

        .checkbox-label {
            font-size: 14px;
            color: var(--text-secondary);
            cursor: pointer;
            user-select: none;
        }

        .forgot-link {
            font-size: 14px;
            color: var(--accent-color);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }

        .login-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .login-button:hover::before {
            width: 300px;
            height: 300px;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button span {
            position: relative;
            z-index: 1;
        }

        .security-info {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
            text-align: center;
        }

        .security-text {
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .security-text i {
            color: var(--success-color);
        }

        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-button.loading-state .button-text {
            display: none;
        }

        .login-button.loading-state .loading {
            display: block;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 15px;
            }

            .login-card {
                padding: 30px 25px;
            }

            .company-name {
                font-size: 20px;
            }

            .floating-shape {
                display: none;
            }
        }

        @media (max-width: 380px) {
            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
        }

        @media (min-width: 768px) and (max-width: 1024px) {
            .login-container {
                max-width: 380px;
            }
        }

        .error-message {
            display: none;
            background: rgba(245, 101, 101, 0.1);
            border: 1px solid rgba(245, 101, 101, 0.3);
            color: var(--error-color);
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <!-- <div class="bg-pattern"></div> -->
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>
    <div class="floating-shape shape-3"></div>

    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo">
                    <img src="assets/logointra.png" alt="Logo" style="width: 100%; height: 100%; object-fit: contain; padding: 4px;">
                </div>
                <h1 class="company-name">MMI</h1>
                <p class="tagline">Txt</p>
            </div>

            <div class="form-section">
                <div class="error-message" id="errorMessage">
                    <i class="fas fa-exclamation-circle"></i> Invalid credentials. Please try again.
                </div>

                <form id="loginForm">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="username" 
                                class="form-input" 
                                placeholder="Masukkan username"
                                required
                                autocomplete="username"
                            >
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                class="form-input" 
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                            >
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" id="passwordToggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- <div class="form-options">
                        <div class="checkbox-wrapper">
                            <input type="checkbox" id="remember" class="checkbox-input">
                            <label for="remember" class="checkbox-label">Ingat login</label>
                        </div>
                        <a href="#" class="forgot-link">Lupa password?</a>
                    </div> -->

                    <button type="submit" class="login-button" id="loginButton">
                        <span class="button-text">Sign In</span>
                        <div class="loading"></div>
                    </button>
                </form>

                <div class="security-info">
                    <p class="security-text">
                        <i class="fas fa-shield-alt"></i>
                        Your activity is monitored
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const passwordIcon = passwordToggle.querySelector('i');

        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            if (type === 'text') {
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        });

        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');
        const errorMessage = document.getElementById('errorMessage');
        const rememberEl = document.getElementById('remember');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            errorMessage.classList.remove('show');
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const remember = rememberEl ? rememberEl.checked : false;
            
            loginButton.classList.add('loading-state');
            loginButton.disabled = true;

            try {
                const res = await fetch('auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, remember })
                });
                const data = await res.json();

                if (data.success) {
                    loginButton.innerHTML = '<i class="fas fa-check"></i>';
                    loginButton.style.background = 'linear-gradient(135deg, #48bb78, #38a169)';
                    setTimeout(() => {
                        window.location.href = data.redirect || 'cast.php';
                    }, 600);
                } else {
                    errorMessage.textContent = data.message || 'Invalid credentials. Please try again.';
                    errorMessage.classList.add('show');
                    loginButton.classList.remove('loading-state');
                    loginButton.disabled = false;
                    
                    loginButton.style.animation = 'shake 0.5s';
                    setTimeout(() => {
                        loginButton.style.animation = '';
                    }, 500);
                }
            } catch (err) {
                errorMessage.textContent = 'Server error. Try again later.';
                errorMessage.classList.add('show');
                loginButton.classList.remove('loading-state');
                loginButton.disabled = false;
            }
        });

        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        window.addEventListener('load', () => {
            const rememberedUser = localStorage.getItem('rememberUser');
            if (rememberedUser) {
                document.getElementById('username').value = rememberedUser;
                if (rememberEl) rememberEl.checked = true;
            }
        });

        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                const icon = input.parentElement.querySelector('.input-icon');
                if (icon) icon.style.color = 'var(--accent-color)';
            });
            
            input.addEventListener('blur', () => {
                if (!input.value) {
                    const icon = input.parentElement.querySelector('.input-icon');
                    if (icon) icon.style.color = 'var(--accent-light)';
                }
            });
        });
    </script>
</body>
</html>