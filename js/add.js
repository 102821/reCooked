(function () {
    const script = document.currentScript;
    const authEndpoint = script && script.dataset.authEndpoint;
    const form = document.querySelector('.recipe-form');

    if (!authEndpoint || !form) {
        return;
    }

    const status = document.createElement('p');
    status.className = 'form-message';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.hidden = true;
    form.before(status);

    fetch(authEndpoint, { credentials: 'same-origin' })
        .then((response) => {
            if (!response.ok) {
                throw new Error('Authentication could not be checked (HTTP ' + response.status + ').');
            }
            return response.json();
        })
        .then((user) => {
            if (!user || !user.authenticated) {
                window.location.replace('user/login.html?error=login-required');
            }
        })
        .catch((error) => {
            console.error(error);
            status.textContent = 'Your login could not be checked. Please try again later.';
            status.hidden = false;
        });

    form.addEventListener('submit', () => {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving recipe...';
        }
    });
}());
