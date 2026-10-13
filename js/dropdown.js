// Handles every dropdown on the page (header menu + footer socials)
const dropdowns = document.querySelectorAll('.dropdown');

function closeDropdown(dropdown) {
    dropdown.classList.remove('is-open');
    dropdown.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
}

function closeAll(except) {
    dropdowns.forEach((dropdown) => {
        if (dropdown !== except) closeDropdown(dropdown);
    });
}

dropdowns.forEach((dropdown) => {
    const toggle = dropdown.querySelector('.dropdown-toggle');

    toggle.addEventListener('click', () => {
        const willOpen = !dropdown.classList.contains('is-open');
        closeAll(dropdown);
        dropdown.classList.toggle('is-open', willOpen);
        toggle.setAttribute('aria-expanded', String(willOpen));
    });
});

// Click outside = close
document.addEventListener('click', (event) => {
    if (!event.target.closest('.dropdown')) closeAll();
});

// Escape = close and give focus back to the button
document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    const open = document.querySelector('.dropdown.is-open');
    if (open) {
        closeDropdown(open);
        open.querySelector('.dropdown-toggle').focus();
    }
});