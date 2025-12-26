<?php
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #fafbfc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            line-height: 1.5;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 48px 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02), 0 8px 16px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo {
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
        }

        .login-header h1 {
            color: #1a1f36;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        .login-header p {
            color: #8792a2;
            font-size: 14px;
            font-weight: 400;
        }

        /* Input Groups with Floating Labels */
        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        .input-group input {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e3e8ee;
            border-radius: 6px;
            padding: 16px 14px 8px 14px;
            color: #1a1f36;
            font-size: 16px;
            font-weight: 400;
            outline: none;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .input-group input:focus {
            border-color: #635BFF;
        }

        .input-group input::placeholder {
            color: transparent;
        }

        .input-group label {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8792a2;
            font-size: 16px;
            font-weight: 400;
            pointer-events: none;
            transition: all 0.2s ease;
            background: #ffffff;
            padding: 0 2px;
        }

        .input-group input:focus+label,
        .input-group input:not(:placeholder-shown)+label {
            top: 0;
            font-size: 12px;
            font-weight: 500;
            color: #635BFF;
            transform: translateY(-50%);
        }

        .input-group input:not(:focus):not(:placeholder-shown)+label {
            color: #6b7385;
        }

        /* Custom Border Animation */
        .input-border {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #635BFF;
            transition: width 0.3s ease;
        }

        .input-group input:focus~.input-border {
            width: 100%;
        }

        /* Password Toggle */
        .input-group:has(.password-toggle) input {
            padding-right: 42px;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #8792a2;
            padding: 6px;
            border-radius: 4px;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: #635BFF;
        }

        /* Form Options */
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #6b7385;
            font-weight: 500;
        }

        .checkbox-container input[type="checkbox"] {
            display: none;
        }

        .checkmark {
            width: 18px;
            height: 18px;
            border: 1.5px solid #d1d9e0;
            border-radius: 4px;
            margin-right: 10px;
            position: relative;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: transparent;
        }

        .checkbox-container input[type="checkbox"]:checked+.checkmark {
            background: #635BFF;
            border-color: #635BFF;
            color: white;
        }

        .forgot-link {
            color: #635BFF;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #4c44d4;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            background: #635BFF;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 14px 20px;
            cursor: pointer;
            font-family: inherit;
            font-size: 16px;
            font-weight: 500;
            position: relative;
            margin-bottom: 24px;
            transition: all 0.2s ease;
            overflow: hidden;
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .submit-btn:hover {
            background: #4c44d4;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 91, 255, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn:disabled {
            background: #a2a7b5;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-text {
            transition: opacity 0.2s ease;
        }

        .btn-loader {
            position: absolute;
            opacity: 0;
            transition: opacity 0.2s ease;
            color: #ffffff;
        }

        .submit-btn.loading .btn-text {
            opacity: 0;
        }

        .submit-btn.loading .btn-loader {
            opacity: 1;
        }

        /* Divider */
        .divider {
            text-align: center;
            margin: 24px 0;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e3e8ee;
        }

        .divider span {
            background: #ffffff;
            color: #8792a2;
            padding: 0 16px;
            font-size: 13px;
            font-weight: 500;
            position: relative;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Social Buttons */
        .social-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .social-btn {
            flex: 1;
            background: #ffffff;
            color: #6b7385;
            border: 1px solid #e3e8ee;
            border-radius: 6px;
            padding: 12px 16px;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            min-height: 44px;
        }

        .social-btn:hover {
            border-color: #d1d9e0;
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .social-btn:active {
            transform: translateY(0);
        }

        /* Signup Link */
        .signup-link {
            text-align: center;
            font-size: 14px;
            color: #8792a2;
        }

        .signup-link a {
            color: #635BFF;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .signup-link a:hover {
            color: #4c44d4;
            text-decoration: underline;
        }

        /* Error States */
        .error-message {
            color: #f56565;
            font-size: 12px;
            font-weight: 500;
            margin-top: 6px;
            opacity: 0;
            transform: translateY(-4px);
            transition: all 0.2s ease;
        }

        .error-message.show {
            opacity: 1;
            transform: translateY(0);
        }

        .input-group.error input {
            border-color: #f56565;
            background: #fef5f5;
        }

        .input-group.error input:focus {
            border-color: #f56565;
        }

        .input-group.error label {
            color: #f56565;
        }

        .input-group.error .input-border {
            background: #f56565;
        }

        /* Success Message */
        .success-message {
            display: none;
            text-align: center;
            padding: 32px 20px;
            opacity: 0;
            transform: translateY(16px);
            transition: all 0.3s ease;
        }

        .success-message.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .success-icon {
            margin: 0 auto 16px;
            animation: successPop 0.5s ease-out;
        }

        @keyframes successPop {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .success-message h3 {
            color: #1a1f36;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .success-message p {
            color: #8792a2;
            font-size: 14px;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .login-card {
                padding: 36px 28px;
                border-radius: 8px;
            }

            .login-header h1 {
                font-size: 1.375rem;
            }

            .form-options {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .social-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">

        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <svg version="1.1"  width="40" height="40" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <circle style="fill:#1b28e4;" cx="256.005" cy="256.005" r="219.73"></circle> <g style="opacity:0.25;"> <path style="fill:#666666;" d="M256,16c132.549,0,240,107.451,240,240S388.549,496,256,496S16,388.549,16,256 C16.15,123.514,123.514,16.15,256,16 M256,0C114.616,0,0,114.616,0,256s114.616,256,256,256s256-114.615,256-256S397.385,0,256,0z"></path> </g> <g> <path style="fill:#FFFFFF;" d="M261.424,91.752l-123.24,82.648h246.48L261.424,91.752z M261.424,152.744 c-7.512,0-13.6-6.089-13.6-13.6s6.089-13.6,13.6-13.6s13.6,6.089,13.6,13.6c-0.004,7.508-6.092,13.592-13.6,13.592V152.744z"></path> <rect x="128.935" y="322.357" style="fill:#FFFFFF;" width="264.965" height="25.825"></rect> <path style="fill:#FFFFFF;" d="M257.673,290.016v-10.584c-5.671-0.019-11.226-1.617-16.04-4.616l2.512-7.032 c4.495,2.922,9.735,4.489,15.096,4.512c7.44,0,12.48-4.296,12.48-10.272c0-5.768-4.088-9.328-11.856-12.48 c-10.696-4.192-17.296-9.016-17.296-18.136c0.129-8.872,6.987-16.191,15.832-16.896v-10.576h6.496v10.168 c4.772,0.045,9.454,1.309,13.6,3.672l-2.632,6.92c-3.992-2.38-8.561-3.615-13.208-3.568c-8.08,0-11.12,4.8-11.12,9.016 c0,5.456,3.88,8.185,13.008,11.952c10.799,4.408,16.248,9.856,16.248,19.2c-0.173,9.296-7.31,16.973-16.568,17.824v10.904h-6.6 L257.673,290.016z"></path> <rect x="156.003" y="196.994" style="fill:#FFFFFF;" width="25.824" height="101.492"></rect> <rect x="196.027" y="196.994" style="fill:#FFFFFF;" width="25.824" height="101.492"></rect> <rect x="301.003" y="196.994" style="fill:#FFFFFF;" width="25.825" height="101.492"></rect> <rect x="341.018" y="196.994" style="fill:#FFFFFF;" width="25.825" height="101.492"></rect> </g> </g></svg>
                </div>
                <h1>Connexion Ebanking</h1>
                <p>Bon retour ! S'il vous plait veuillez vous connectez</p>
            </div>

            <form class="login-form" id="loginForm" novalidate method="POST" action="<?= BASE_URL ?>?controller=Auth&action=login">

                <?php if (isset($errorMessage) && $errorMessage): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($errorMessage) ?>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <input type="text" id="identifiant" name="identifiant" autofocus required autocomplete="email" placeholder=" ">
                    <label for="email">Identifiant</label>
                    <span class="input-border"></span>
                    <span class="error-message" id="emailError"></span>
                </div>

                <div class="input-group">
                    <input  type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password" placeholder=" ">
                    <label for="mot_de_passe">Mot de passe</label>
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                        <svg class="eye-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M8 3C4.5 3 1.6 5.6 1 8c.6 2.4 3.5 5 7 5s6.4-2.6 7-5c-.6-2.4-3.5-5-7-5zm0 8.5A3.5 3.5 0 118 4.5a3.5 3.5 0 010 7zm0-5.5a2 2 0 100 4 2 2 0 000-4z" fill="currentColor" />
                        </svg>
                    </button>
                    <span class="input-border"></span>
                    <span class="error-message" id="passwordError"></span>
                </div>

                <div class="form-options">
                    <label class="checkbox-container">
                        <input type="checkbox" id="remember" name="remember">
                        <span class="checkmark">
                            <svg width="10" height="8" viewBox="0 0 10 8" fill="none">
                                <path d="M1 4l2.5 2.5L9 1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        Se souvenir de moi
                    </label>
                    <a href="#" class="forgot-link"></a>
                </div>

                <button type="submit" class="submit-btn">
                    <span class="btn-text">Se connecter</span>
                    <div class="btn-loader">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none">
                            <circle cx="9" cy="9" r="7" stroke="currentColor" stroke-width="2" opacity="0.25" />
                            <path d="M16 9a7 7 0 01-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <animateTransform attributeName="transform" type="rotate" dur="1s" values="0 9 9;360 9 9" repeatCount="indefinite" />
                            </path>
                        </svg>
                    </div>
                </button>
            </form>

            <div class="signup-link">
                <span>Vous êtes client ?</span>
                <a href="<?= BASE_URL ?>?controller=ClientFront&action=login" style="font-weight: bold; color: #007bff;">
                    Accéder à l'Espace Client
                </a>
            </div>

            <div class="success-message" id="successMessage">
                <div class="success-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="12" fill="#635BFF" />
                        <path d="M8 12l3 3 5-5" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <h3>Bienvenu parmi nous !</h3>
                <p>Redirection vers le tableau de bord...</p>
            </div>
        </div>
    </div>

    <script>
        // Shared Form Utilities
        // This file contains common functionality used across all login forms

        class FormUtils {
            static validateEmail(value) {
                if (!value) {
                    return {
                        isValid: false,
                        message: 'Email address is required'
                    };
                }
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    return {
                        isValid: false,
                        message: 'Please enter a valid email address'
                    };
                }
                return {
                    isValid: true
                };
            }

            static validatePassword(value) {
                if (!value) {
                    return {
                        isValid: false,
                        message: 'Password is required'
                    };
                }
                if (value.length < 8) {
                    return {
                        isValid: false,
                        message: 'Password must be at least 8 characters long'
                    };
                }
                if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
                    return {
                        isValid: false,
                        message: 'Password must contain uppercase, lowercase, and number'
                    };
                }
                return {
                    isValid: true
                };
            }

            static showError(fieldName, message) {
                const formGroup = document.getElementById(fieldName).closest('.form-group');
                const errorElement = document.getElementById(fieldName + 'Error');

                if (formGroup && errorElement) {
                    formGroup.classList.add('error');
                    errorElement.textContent = message;
                    errorElement.classList.add('show');

                    // Add shake animation to the field
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.style.animation = 'shake 0.5s ease-in-out';
                        setTimeout(() => {
                            field.style.animation = '';
                        }, 500);
                    }
                }
            }

            static clearError(fieldName) {
                const formGroup = document.getElementById(fieldName).closest('.form-group');
                const errorElement = document.getElementById(fieldName + 'Error');

                if (formGroup && errorElement) {
                    formGroup.classList.remove('error');
                    errorElement.classList.remove('show');
                    setTimeout(() => {
                        errorElement.textContent = '';
                    }, 300);
                }
            }

            static showSuccess(fieldName) {
                const field = document.getElementById(fieldName);
                const wrapper = field?.closest('.input-wrapper');

                if (wrapper) {
                    // Add subtle success indication
                    wrapper.style.borderColor = '#22c55e';
                    setTimeout(() => {
                        wrapper.style.borderColor = '';
                    }, 2000);
                }
            }

            static showNotification(message, type = 'info', container = null) {
                const targetContainer = container || document.querySelector('form');
                if (!targetContainer) return;

                // Create notification element
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;

                let backgroundColor, borderColor, textColor;
                switch (type) {
                    case 'error':
                        backgroundColor = 'rgba(239, 68, 68, 0.1)';
                        borderColor = 'rgba(239, 68, 68, 0.3)';
                        textColor = '#ef4444';
                        break;
                    case 'success':
                        backgroundColor = 'rgba(34, 197, 94, 0.1)';
                        borderColor = 'rgba(34, 197, 94, 0.3)';
                        textColor = '#22c55e';
                        break;
                    default:
                        backgroundColor = 'rgba(6, 182, 212, 0.1)';
                        borderColor = 'rgba(6, 182, 212, 0.3)';
                        textColor = '#06b6d4';
                }

                notification.innerHTML = `
            <div style="
                background: ${backgroundColor}; 
                backdrop-filter: blur(10px); 
                border: 1px solid ${borderColor}; 
                border-radius: 12px; 
                padding: 12px 16px; 
                margin-top: 16px; 
                color: ${textColor}; 
                text-align: center;
                font-size: 14px;
                animation: slideIn 0.3s ease;
            ">
                ${message}
            </div>
        `;

                targetContainer.appendChild(notification);

                // Remove notification after 3 seconds
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 3000);
            }

            static setupFloatingLabels(form) {
                const inputs = form.querySelectorAll('input');
                inputs.forEach(input => {
                    // Check if field has value on page load
                    if (input.value.trim() !== '') {
                        input.classList.add('has-value');
                    }

                    input.addEventListener('input', () => {
                        if (input.value.trim() !== '') {
                            input.classList.add('has-value');
                        } else {
                            input.classList.remove('has-value');
                        }
                    });
                });
            }

            static setupPasswordToggle(passwordInput, toggleButton) {
                if (toggleButton && passwordInput) {
                    toggleButton.addEventListener('click', () => {
                        const isPassword = passwordInput.type === 'password';
                        const eyeIcon = toggleButton.querySelector('.eye-icon');

                        passwordInput.type = isPassword ? 'text' : 'password';
                        if (eyeIcon) {
                            eyeIcon.classList.toggle('show-password', isPassword);
                        }

                        // Add smooth transition effect
                        toggleButton.style.transform = 'scale(0.9)';
                        setTimeout(() => {
                            toggleButton.style.transform = 'scale(1)';
                        }, 150);

                        // Keep focus on password input
                        passwordInput.focus();
                    });
                }
            }

            static addEntranceAnimation(element, delay = 100) {
                if (element) {
                    element.style.opacity = '0';
                    element.style.transform = 'translateY(30px)';

                    setTimeout(() => {
                        element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }, delay);
                }
            }

            static addSharedAnimations() {
                // Add CSS animations to document head if not already present
                if (!document.getElementById('shared-animations')) {
                    const style = document.createElement('style');
                    style.id = 'shared-animations';
                    style.textContent = `
                @keyframes slideIn {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                
                @keyframes slideOut {
                    from { opacity: 1; transform: translateY(0); }
                    to { opacity: 0; transform: translateY(-10px); }
                }
                
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
                
                @keyframes checkmarkPop {
                    0% { transform: scale(0); }
                    50% { transform: scale(1.3); }
                    100% { transform: scale(1); }
                }
                
                @keyframes successPulse {
                    0% { transform: scale(0); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                
                @keyframes spin {
                    0% { transform: translate(-50%, -50%) rotate(0deg); }
                    100% { transform: translate(-50%, -50%) rotate(360deg); }
                }
            `;
                    document.head.appendChild(style);
                }
            }
        }
    </script>
    <script>
        // Modern SaaS Login Form JavaScript
        class ModernSaaSLoginForm {
            constructor() {
                this.form = document.getElementById('loginForm');
                this.emailInput = document.getElementById('email');
                this.passwordInput = document.getElementById('password');
                this.passwordToggle = document.getElementById('passwordToggle');
                this.submitButton = this.form.querySelector('.submit-btn');
                this.successMessage = document.getElementById('successMessage');
                this.socialButtons = document.querySelectorAll('.social-btn');

                this.init();
            }

            init() {
                this.setupPasswordToggle();
            }

            bindEvents() {
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));
                this.emailInput.addEventListener('blur', () => this.validateEmail());
                this.passwordInput.addEventListener('blur', () => this.validatePassword());
                this.emailInput.addEventListener('input', () => this.clearError('email'));
                this.passwordInput.addEventListener('input', () => this.clearError('password'));
            }

            setupPasswordToggle() {
                this.passwordToggle.addEventListener('click', () => {
                    const type = this.passwordInput.type === 'password' ? 'text' : 'password';
                    this.passwordInput.type = type;

                    // Simple visual feedback
                    this.passwordToggle.style.color = type === 'text' ? '#635BFF' : '#8792a2';
                });
            }

            setupSocialButtons() {
                this.socialButtons.forEach(button => {
                    button.addEventListener('click', (e) => {
                        const provider = button.textContent.trim();
                        this.handleSocialLogin(provider, button);
                    });
                });
            }

            validateEmail() {
                const email = this.emailInput.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                if (!email) {
                    this.showError('email', 'Email is required');
                    return false;
                }

                if (!emailRegex.test(email)) {
                    this.showError('email', 'Please enter a valid email address');
                    return false;
                }

                this.clearError('email');
                return true;
            }

            validatePassword() {
                const password = this.passwordInput.value;

                if (!password) {
                    this.showError('password', 'Password is required');
                    return false;
                }

                if (password.length < 6) {
                    this.showError('password', 'Password must be at least 6 characters');
                    return false;
                }

                this.clearError('password');
                return true;
            }

            showError(field, message) {
                const inputGroup = document.getElementById(field).closest('.input-group');
                const errorElement = document.getElementById(`${field}Error`);

                inputGroup.classList.add('error');
                errorElement.textContent = message;
                errorElement.classList.add('show');
            }

            clearError(field) {
                const inputGroup = document.getElementById(field).closest('.input-group');
                const errorElement = document.getElementById(`${field}Error`);

                inputGroup.classList.remove('error');
                errorElement.classList.remove('show');
                setTimeout(() => {
                    errorElement.textContent = '';
                }, 200);
            }


            async handleSocialLogin(provider, button) {
                console.log(`Signing in with ${provider}...`);

                // Simple loading state
                const originalHTML = button.innerHTML;
                button.style.pointerEvents = 'none';
                button.style.opacity = '0.7';
                button.innerHTML = `
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                <circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5" opacity="0.25"/>
                <path d="M12.5 7a5.5 5.5 0 01-5.5 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
                    <animateTransform attributeName="transform" type="rotate" dur="1s" values="0 7 7;360 7 7" repeatCount="indefinite"/>
                </path>
            </svg>
            Connecting...
        `;

                try {
                    await new Promise(resolve => setTimeout(resolve, 1500));
                    console.log(`Redirecting to ${provider} authentication...`);
                    // window.location.href = `/auth/${provider.toLowerCase()}`;
                } catch (error) {
                    console.error(`${provider} sign in failed: ${error.message}`);
                } finally {
                    button.style.pointerEvents = 'auto';
                    button.style.opacity = '1';
                    button.innerHTML = originalHTML;
                }
            }

            setLoading(loading) {
                this.submitButton.classList.toggle('loading', loading);
                this.submitButton.disabled = loading;

                // Disable social buttons during loading
                this.socialButtons.forEach(button => {
                    button.style.pointerEvents = loading ? 'none' : 'auto';
                    button.style.opacity = loading ? '0.6' : '1';
                });
            }

            showSuccess() {
                // Hide form with smooth transition
                this.form.style.transform = 'scale(0.95)';
                this.form.style.opacity = '0';

                setTimeout(() => {
                    this.form.style.display = 'none';
                    document.querySelector('.social-buttons').style.display = 'none';
                    document.querySelector('.signup-link').style.display = 'none';
                    document.querySelector('.divider').style.display = 'none';

                    // Show success message
                    this.successMessage.classList.add('show');

                }, 300);

                // Redirect after success display
                setTimeout(() => {
                    console.log('Redirecting to dashboard...');
                    // window.location.href = '/dashboard';
                }, 2500);
            }
        }

        // Initialize the form when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new ModernSaaSLoginForm();
        });
    </script>
</body>

</html>