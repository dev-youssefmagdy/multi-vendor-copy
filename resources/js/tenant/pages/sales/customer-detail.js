const generateBtn = document.querySelector('[data-generate-password]');

function randomPassword(length = 12) {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    return Array.from(crypto.getRandomValues(new Uint32Array(length)))
        .map((n) => chars[n % chars.length])
        .join('');
}

generateBtn?.addEventListener('click', () => {
    const form = generateBtn.closest('form');
    const password = randomPassword();
    const passwordInput = form?.querySelector('[name="password"]');
    const confirmInput = form?.querySelector('[name="password_confirmation"]');

    if (passwordInput) {
        passwordInput.value = password;
        passwordInput.type = 'text';
    }
    if (confirmInput) {
        confirmInput.value = password;
        confirmInput.type = 'text';
    }
});
