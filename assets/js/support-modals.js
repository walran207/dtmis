(function () {
    function initSupportModals() {
        const modal = document.getElementById('supportModal');
        const titleNode = document.getElementById('supportModalTitle');
        const introNode = document.getElementById('supportModalIntro');
        const contentNode = document.getElementById('supportModalContent');
        const fallbackLink = document.getElementById('supportModalFallbackLink');
        const templateHost = document.getElementById('supportModalTemplates');
        const closeButtons = document.querySelectorAll('[data-support-modal-close="true"]');
        const triggers = document.querySelectorAll('[data-support-modal]');

        if (!modal || !titleNode || !introNode || !contentNode || !fallbackLink || !templateHost || !triggers.length) {
            return;
        }

        function getTemplateSection(key) {
            return templateHost.querySelector('[data-support-modal-section="' + key + '"]');
        }

        function closeSupportModal() {
            modal.hidden = true;
            modal.classList.remove('is-open');
            document.body.classList.remove('support-modal-open');
            contentNode.innerHTML = '';
        }

        function openSupportModal(key, fallbackHref) {
            const section = getTemplateSection(key);
            if (!section) {
                if (fallbackHref) {
                    window.location.href = fallbackHref;
                }
                return;
            }

            titleNode.textContent = section.getAttribute('data-support-modal-title') || 'Support';
            introNode.textContent = section.getAttribute('data-support-modal-intro') || '';
            contentNode.innerHTML = section.innerHTML;
            fallbackLink.href = fallbackHref || fallbackLink.href;
            modal.hidden = false;
            modal.classList.add('is-open');
            document.body.classList.add('support-modal-open');
        }

        triggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                const key = String(trigger.getAttribute('data-support-modal') || '').trim();
                if (key === '') {
                    return;
                }
                event.preventDefault();
                openSupportModal(key, trigger.getAttribute('href') || '');
            });
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeSupportModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal && !modal.hidden) {
                closeSupportModal();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSupportModals);
    } else {
        initSupportModals();
    }
})();
