document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('signupForm');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const errorEl = document.getElementById('signupError');
        const submitBtn = document.getElementById('signupSubmitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');

        const password = document.getElementById('signupPassword').value;
        const confirmPassword = document.getElementById('signupConfirmPassword').value;

        if (password !== confirmPassword) {
            errorEl.textContent = 'Passwords do not match.';
            errorEl.style.display = 'block';
            return;
        }

        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline';
        errorEl.style.display = 'none';

        const formData = new FormData(form);

        try {
            const res = await fetch('/api/signup', {
                method: 'POST',
                body: formData,
            });

            const data = await res.json();

            if (data.success) {
                window.location.href = data.redirect || '/';
            } else {
                errorEl.textContent = data.message || 'Registration failed.';
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
