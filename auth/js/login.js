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

    function validateForm(form) {
        const email = form.querySelector('#email');
        const password = form.querySelector('#password');
        let valid = true;

        clearFieldError(email);
        clearFieldError(password);

        if (!email.value.trim()) {
            setInvalidField(email, 'Email is required.');
            valid = false;
        } else if (!email.checkValidity()) {
            setInvalidField(email, 'Enter a valid email address.');
            valid = false;
        } else {
            const emailValue = email.value.trim().toLowerCase();
            const isAllowedDomain = emailValue.endsWith('@gmail.com') || emailValue.endsWith('@denr.gov.ph');
            if (!isAllowedDomain) {
                setInvalidField(email, 'Use a Gmail or DENR email address.');
                valid = false;
            }
        }

        if (!password.value.trim()) {
            setInvalidField(password, 'Password is required.');
            valid = false;
        }

        return valid;
    }

    function setLoadingState(button, isLoading) {
        const label = button.querySelector('.btn-text');
        button.disabled = isLoading;
        if (label) {
            label.textContent = isLoading ? 'Signing In...' : 'Sign In';
        }
    }

    window.togglePassword = function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleBtn = document.querySelector('.password-toggle');

        if (!passwordInput || !toggleBtn) {
            return;
        }

        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        toggleBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        toggleBtn.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
    };

    document.addEventListener('DOMContentLoaded', function () {
        const loginForm = document.getElementById('loginForm');
        const signInBtn = document.getElementById('signInBtn');

        if (!loginForm || !signInBtn) {
            return;
        }

        loginForm.addEventListener('input', function (event) {
            if (event.target.matches('input') && event.target.closest('.form-group')) {
                clearFieldError(event.target);
            }
        });

        loginForm.addEventListener('submit', function (event) {
            if (!validateForm(loginForm)) {
                event.preventDefault();
                return;
            }

            setLoadingState(signInBtn, true);
        });
    });
})();
