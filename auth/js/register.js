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
        const firstName = form.querySelector('#first_name');
        const lastName = form.querySelector('#last_name');
        const email = form.querySelector('#email');
        const office = form.querySelector('#office_id');
        const role = form.querySelector('#role_id');
        const password = form.querySelector('#password');
        const confirmPassword = form.querySelector('#confirm_password');
        let valid = true;

        [firstName, lastName, email, office, role, password, confirmPassword].forEach(clearFieldError);

        if (!firstName.value.trim()) {
            setInvalidField(firstName, 'First name is required.');
            valid = false;
        }

        if (!lastName.value.trim()) {
            setInvalidField(lastName, 'Last name is required.');
            valid = false;
        }

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

        if (!office.value) {
            setInvalidField(office, 'Select your office.');
            valid = false;
        }

        if (!role.value) {
            setInvalidField(role, 'Select your role.');
            valid = false;
        }

        if (!password.value.trim()) {
            setInvalidField(password, 'Password is required.');
            valid = false;
        } else if (password.value.length < 8) {
            setInvalidField(password, 'Password must be at least 8 characters.');
            valid = false;
        }

        if (!confirmPassword.value.trim()) {
            setInvalidField(confirmPassword, 'Confirm your password.');
            valid = false;
        } else if (password.value !== confirmPassword.value) {
            setInvalidField(confirmPassword, 'Passwords do not match.');
            valid = false;
        }

        return valid;
    }

    function setLoadingState(button, isLoading) {
        const label = button.querySelector('.btn-text');
        button.disabled = isLoading;
        if (label) {
            label.textContent = isLoading ? 'Creating Account...' : 'Create Account';
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

    document.addEventListener('DOMContentLoaded', function () {
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');

        if (!registerForm || !registerBtn) {
            return;
        }

        bindPasswordToggles();

        registerForm.addEventListener('input', function (event) {
            if (event.target.matches('input, select') && event.target.closest('.form-group')) {
                clearFieldError(event.target);
            }
        });

        registerForm.addEventListener('change', function (event) {
            if (event.target.matches('select') && event.target.closest('.form-group')) {
                clearFieldError(event.target);
            }
        });

        registerForm.addEventListener('submit', function (event) {
            if (!validateForm(registerForm)) {
                event.preventDefault();
                return;
            }

            setLoadingState(registerBtn, true);
        });
    });
})();
