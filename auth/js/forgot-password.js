(function () {
    function setInvalidField(input, message) {
        const wrapper = input.closest('.form-group');
        if (!wrapper) {
            return;
        }

        const existing = wrapper.querySelector('.field-error');
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');

        if (existing) {
            existing.textContent = message;
            return;
        }

        const error = document.createElement('p');
        error.className = 'field-error';
        error.textContent = message;
        wrapper.appendChild(error);
    }

    function clearFieldError(input) {
        const wrapper = input.closest('.form-group');
        if (!wrapper) {
            return;
        }

        const error = wrapper.querySelector('.field-error');
        input.classList.remove('is-invalid');
        input.removeAttribute('aria-invalid');

        if (error) {
            error.remove();
        }
    }

    function bindPasswordToggles() {
        const toggles = document.querySelectorAll('.password-toggle[data-target]');
        toggles.forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const targetId = toggle.getAttribute('data-target');
                const input = targetId ? document.getElementById(targetId) : null;
                if (!input) {
                    return;
                }

                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                toggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            });
        });
    }

    function setLoadingState(button, isLoading) {
        const label = button.querySelector('.btn-text');
        const defaultText = button.getAttribute('data-default-text') || 'Submit';
        const loadingText = button.getAttribute('data-loading-text') || 'Submitting...';
        button.disabled = isLoading;

        if (label) {
            label.textContent = isLoading ? loadingText : defaultText;
        }
    }

    function validateEmail(email) {
        if (!email.value.trim()) {
            setInvalidField(email, 'Email is required.');
            return false;
        }

        if (!email.checkValidity()) {
            setInvalidField(email, 'Enter a valid email address.');
            return false;
        }

        const emailValue = email.value.trim().toLowerCase();
        const isAllowedDomain = emailValue.endsWith('@gmail.com') || emailValue.endsWith('@denr.gov.ph');
        if (!isAllowedDomain) {
            setInvalidField(email, 'Use a Gmail or DENR email address.');
            return false;
        }

        return true;
    }

    function validateRequestForm(form) {
        const email = form.querySelector('#email');
        let valid = true;

        clearFieldError(email);
        if (!validateEmail(email)) {
            valid = false;
        }

        return valid;
    }

    function validateResetForm(form) {
        const email = form.querySelector('#email');
        const otp = form.querySelector('#otp_code');
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');
        let valid = true;

        [email, otp, password, confirmPassword].forEach(clearFieldError);

        if (!validateEmail(email)) {
            valid = false;
        }

        if (!otp.value.trim()) {
            setInvalidField(otp, 'Verification code is required.');
            valid = false;
        } else if (!/^\d{6}$/.test(otp.value.trim())) {
            setInvalidField(otp, 'Enter a valid 6-digit code.');
            valid = false;
        }

        if (!password.value.trim()) {
            setInvalidField(password, 'New password is required.');
            valid = false;
        } else if (password.value.length < 8) {
            setInvalidField(password, 'Password must be at least 8 characters.');
            valid = false;
        }

        if (!confirmPassword.value.trim()) {
            setInvalidField(confirmPassword, 'Confirm your new password.');
            valid = false;
        } else if (password.value !== confirmPassword.value) {
            setInvalidField(confirmPassword, 'Passwords do not match.');
            valid = false;
        }

        return valid;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const requestForm = document.getElementById('requestResetForm');
        const resetForm = document.getElementById('resetForm');

        bindPasswordToggles();

        if (requestForm) {
            const button = document.getElementById('sendCodeBtn');

            requestForm.addEventListener('input', function (event) {
                if (event.target.matches('input') && event.target.closest('.form-group')) {
                    clearFieldError(event.target);
                }
            });

            requestForm.addEventListener('submit', function (event) {
                if (!validateRequestForm(requestForm)) {
                    event.preventDefault();
                    return;
                }

                if (button) {
                    setLoadingState(button, true);
                }
            });
        }

        if (resetForm) {
            const button = document.getElementById('resetPasswordBtn');

            resetForm.addEventListener('input', function (event) {
                if (event.target.matches('input') && event.target.closest('.form-group')) {
                    clearFieldError(event.target);
                }
            });

            resetForm.addEventListener('submit', function (event) {
                if (!validateResetForm(resetForm)) {
                    event.preventDefault();
                    return;
                }

                if (button) {
                    setLoadingState(button, true);
                }
            });
        }
    });
})();
