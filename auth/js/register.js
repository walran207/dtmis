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
        } else {
            const selectedRole = role.options[role.selectedIndex];
            const fixedOfficeId = selectedRole ? String(selectedRole.getAttribute('data-fixed-office-id') || '').trim() : '';
            const selectedRoleName = selectedRole ? String(selectedRole.textContent || '') : '';
            const selectedOffice = office.options[office.selectedIndex];
            const selectedOfficeLevel = selectedOffice ? String(selectedOffice.getAttribute('data-office-level') || '') : '';
            const selectedOfficeName = selectedOffice ? String(selectedOffice.textContent || '') : '';
            if (fixedOfficeId && office.value && office.value !== fixedOfficeId) {
                setInvalidField(office, 'This role is assigned to one office only.');
                valid = false;
            } else if (office.value && !isOfficeAllowedForRole(selectedRoleName, selectedOfficeLevel, selectedOfficeName)) {
                setInvalidField(office, 'Select an office that matches the selected role.');
                valid = false;
            }
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

    function resolveRoleOfficeGroup(roleName) {
        const normalized = String(roleName || '').trim().toUpperCase();
        if (!normalized) {
            return '';
        }

        if (normalized.startsWith('PAMO_') || normalized.startsWith('PASU_')) {
            return 'PAMO';
        }

        if (normalized.startsWith('CENRO_')) {
            return 'CENRO';
        }

        if (normalized.startsWith('PENRO_')) {
            return 'PENRO';
        }

        return 'Regional';
    }

    function isOfficeAllowedForRole(roleName, officeLevel, officeName) {
        const role = String(roleName || '').trim().toUpperCase();
        const level = String(officeLevel || '').trim().toUpperCase();
        const name = String(officeName || '').trim().toUpperCase();

        if (!role) {
            return true;
        }

        if (role === 'CENRO_ADMIN_RECORD') {
            return level === 'CENRO_ADMIN_RECORD' || (name.includes('CENRO') && name.includes('ADMIN RECORD'));
        }

        if (role === 'CENRO_OFFICER') {
            return level === 'CENRO_OFFICER' || (name.includes('CENRO') && name.includes('OFFICER'));
        }

        if (role === 'CENRO_SECTION') {
            return level === 'CENRO_SECTION';
        }

        if (role === 'CENRO_UNIT') {
            return level === 'CENRO_UNIT';
        }

        if (role === 'PENRO_ADMIN_RECORD') {
            return level === 'PENRO_ADMIN_RECORD' || (name.includes('PENRO') && name.includes('ADMIN RECORD'));
        }

        if (role === 'PENRO_OFFICER') {
            return level === 'PENRO_OFFICER' || (name.includes('PENRO') && name.includes('OFFICER'));
        }

        if (role === 'PENRO_DIVISION') {
            return level === 'PENRO_DIVISION';
        }

        if (role === 'PENRO_SECTION') {
            return level === 'PENRO_SECTION';
        }

        if (role === 'PENRO_SECTION_UNIT') {
            return level === 'PENRO_SECTION' || level === 'PENRO_UNIT';
        }

        if (role === 'PAMO_ADMIN') {
            return level === 'PAMO_ADMIN' || (name.includes('PAMO') && name.includes('ADMIN'));
        }

        if (role === 'PASU_OFFICER') {
            return level === 'PASU_OFFICER' || (name.includes('PASU') && name.includes('OFFICER'));
        }

        if (role === 'PAMO_UNIT') {
            return level === 'PAMO_UNIT';
        }

        if (role === 'DIVISION_CHIEF') {
            return level === 'DIVISION' || level === 'PENRO_DIVISION';
        }

        if (role === 'SECTION_STAFF') {
            return level === 'SECTION' || level === 'CENRO_SECTION' || level === 'PENRO_SECTION' || level === 'PENRO_UNIT';
        }

        return true;
    }

    function bindRoleOfficeFilter(roleSelect, officeSelect) {
        if (!roleSelect || !officeSelect) {
            return;
        }

        const defaultOption = officeSelect.querySelector('option[value=""]');
        const defaultOptionText = defaultOption ? defaultOption.textContent : 'Select office';
        const groupedOptions = {};

        Array.from(officeSelect.querySelectorAll('optgroup')).forEach(function (group) {
            const groupLabel = String(group.getAttribute('label') || '').trim();
            if (!groupLabel) {
                return;
            }

            groupedOptions[groupLabel] = Array.from(group.querySelectorAll('option')).map(function (option) {
                return {
                    value: option.value,
                    text: option.textContent || '',
                    group: String(option.getAttribute('data-office-group') || groupLabel).trim(),
                    level: String(option.getAttribute('data-office-level') || '').trim(),
                };
            });
        });

        function renderOfficeOptionsByRole() {
            const selectedRole = roleSelect.options[roleSelect.selectedIndex];
            const selectedRoleName = selectedRole ? selectedRole.textContent : '';
            const fixedOfficeId = selectedRole ? String(selectedRole.getAttribute('data-fixed-office-id') || '').trim() : '';
            const allowedGroup = resolveRoleOfficeGroup(selectedRoleName);
            const previousOfficeValue = officeSelect.value;

            officeSelect.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = defaultOptionText;
            officeSelect.appendChild(placeholder);

            let availableCount = 0;
            let firstOfficeValue = '';
            const order = ['PAMO', 'CENRO', 'PENRO', 'Regional'];
            order.forEach(function (groupLabel) {
                if (allowedGroup && groupLabel !== allowedGroup) {
                    return;
                }

                const options = groupedOptions[groupLabel] || [];
                if (!options.length) {
                    return;
                }

                const filteredOptions = fixedOfficeId
                    ? options.filter(function (optionData) {
                        return optionData.value === fixedOfficeId;
                    })
                    : options;

                const refinedOptions = (!fixedOfficeId)
                    ? filteredOptions.filter(function (optionData) {
                        return isOfficeAllowedForRole(selectedRoleName, optionData.level, optionData.text);
                    })
                    : filteredOptions;

                if (!refinedOptions.length) {
                    return;
                }

                const optgroup = document.createElement('optgroup');
                optgroup.label = groupLabel;
                refinedOptions.forEach(function (optionData) {
                    const option = document.createElement('option');
                    option.value = optionData.value;
                    option.textContent = optionData.text;
                    option.setAttribute('data-office-group', optionData.group);
                    option.setAttribute('data-office-level', optionData.level);
                    optgroup.appendChild(option);

                    availableCount += 1;
                    if (firstOfficeValue === '') {
                        firstOfficeValue = optionData.value;
                    }
                });
                officeSelect.appendChild(optgroup);
            });

            const stillAvailable = previousOfficeValue !== ''
                && Array.from(officeSelect.options).some(function (option) {
                    return option.value === previousOfficeValue;
                });

            if (stillAvailable) {
                officeSelect.value = previousOfficeValue;
            } else if (availableCount === 1 && firstOfficeValue !== '') {
                officeSelect.value = firstOfficeValue;
            } else {
                officeSelect.value = '';
            }
        }

        roleSelect.addEventListener('change', renderOfficeOptionsByRole);
        renderOfficeOptionsByRole();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');
        const roleSelect = document.getElementById('role_id');
        const officeSelect = document.getElementById('office_id');

        if (!registerForm || !registerBtn) {
            return;
        }

        bindPasswordToggles();
        bindRoleOfficeFilter(roleSelect, officeSelect);

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
