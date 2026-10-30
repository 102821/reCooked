(function () {
    const script = document.currentScript;
    const endpoint = script && script.dataset.endpoint;
    const authLinks = document.querySelectorAll('.login-link, .auth-link');

    if (!endpoint || !authLinks.length) {
        return;
    }

    fetch(endpoint, { credentials: 'same-origin' })
        .then((response) => response.ok ? response.json() : null)
        .then((user) => {
            if (!user || !user.authenticated) {
                return;
            }

            authLinks.forEach((link) => {
                const icon = document.createElement('span');
                icon.className = 'profile-icon';
                icon.setAttribute('aria-hidden', 'true');
                icon.innerHTML = '&#128100;';
                link.replaceChildren(icon, document.createTextNode(user.username));
                link.href = endpoint.replace('current_user.php', 'logout.php');
                link.setAttribute('aria-label', 'Log out ' + user.username);
                link.classList.add('profile-link');
            });

            document.querySelectorAll('.add-recipe').forEach((link) => {
                link.href = link.dataset.recipeUrl || '../invoerpagina.html';
            });
        })
        .catch(() => {
            // Static pages remain usable when the auth endpoint is unavailable.
        });
}());