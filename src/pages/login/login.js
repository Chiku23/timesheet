document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const errorEl = document.getElementById('loginError');
        const submitBtn = document.getElementById('loginSubmitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');

        // Show loading state
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';
        errorEl.style.display = 'none';

        const formData = new FormData(form);

        try {
            const res = await fetch('/api/login', {
                method: 'POST',
                body: formData,
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect || '/';
            } else {
                errorEl.textContent = data.message || 'Login failed.';
                errorEl.style.display = 'block';
                submitBtn.disabled = false;
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
            }
        } catch (err) {
            errorEl.textContent = 'Something went wrong. Please try again.';
            errorEl.style.display = 'block';
            submitBtn.disabled = false;
            btnText.style.display = 'inline';
            btnLoader.style.display = 'none';
        }
    });
});
