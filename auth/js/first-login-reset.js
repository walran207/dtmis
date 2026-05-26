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

    function validatePasswordPolicy(value) {
        if (value.length < 10) {
            return false;
        }

        if (!/[A-Z]/.test(value)) {
            return false;
        }

        if (!/[a-z]/.test(value)) {
            return false;
        }

        if (!/\d/.test(value)) {
            return false;
        }

        if (!/[^A-Za-z0-9]/.test(value)) {
            return false;
        }

        return true;
    }

    function validateResetForm(form) {
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');
        let valid = true;

        [password, confirmPassword].forEach(clearFieldError);

        if (!password.value.trim()) {
            setInvalidField(password, 'New password is required.');
            valid = false;
        } else if (!validatePasswordPolicy(password.value)) {
            setInvalidField(password, 'Use at least 10 characters with uppercase, lowercase, number, and symbol.');
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
        const form = document.getElementById('firstLoginResetForm');
        const button = document.getElementById('resetPasswordBtn');

        bindPasswordToggles();

        if (!form) {
            return;
        }

        form.addEventListener('input', function (event) {
            if (event.target.matches('input') && event.target.closest('.form-group')) {
                clearFieldError(event.target);
            }
        });

        form.addEventListener('submit', function (event) {
            if (!validateResetForm(form)) {
                event.preventDefault();
                return;
            }

            if (button) {
                setLoadingState(button, true);
            }
        });
    });
})();
