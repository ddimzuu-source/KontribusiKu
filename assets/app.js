// assets/js/app.js
(() => {
    'use strict';

    // 1. Validasi form Bootstrap (form dengan class .needs-validation)
    document.querySelectorAll('.needs-validation').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // 2. Tampilkan / sembunyikan password
    //    Pakai: <button data-toggle-password="#id-input">
    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.querySelector(button.dataset.togglePassword);
            if (!input) return;

            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Sembunyikan' : 'Tampilkan';
            button.setAttribute('aria-pressed', String(isHidden));
        });
    });

    // 3. Konfirmasi password harus sama (halaman register)
    const password = document.querySelector('#password');
    const confirm = document.querySelector('#password_confirm');

    if (password && confirm) {
        const checkMatch = () => {
            confirm.setCustomValidity(
                confirm.value !== password.value ? 'Tidak sama' : ''
            );
        };
        password.addEventListener('input', checkMatch);
        confirm.addEventListener('input', checkMatch);
    }
})();