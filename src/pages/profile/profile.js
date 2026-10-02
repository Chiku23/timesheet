// ==========================================
// Profile Page JavaScript
// Self-service account credentials & security
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    const profileForm = document.getElementById('profileForm');
    const profileAlert = document.getElementById('profileAlert');
    const profileName = document.getElementById('profileName');
    const profileEmail = document.getElementById('profileEmail');
    const profileCurrentPassword = document.getElementById('profileCurrentPassword');
    const profileNewPassword = document.getElementById('profileNewPassword');
    const profileConfirmPassword = document.getElementById('profileConfirmPassword');
    const saveProfileBtn = document.getElementById('saveProfileBtn');
    const headerUserName = document.getElementById('headerUserName');

    function showAlert(message, type = 'error') {
        if (!profileAlert) return;
        profileAlert.className = `profile-alert profile-alert-${type}`;
        profileAlert.textContent = message;
        profileAlert.style.display = 'block';
        profileAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        if (!profileAlert) return;
        profileAlert.style.display = 'none';
        profileAlert.textContent = '';
    }

    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideAlert();

            const name = profileName ? profileName.value.trim() : '';
            const email = profileEmail ? profileEmail.value.trim() : '';
            const currentPass = profileCurrentPassword ? profileCurrentPassword.value : '';
            const newPass = profileNewPassword ? profileNewPassword.value : '';
            const confirmPass = profileConfirmPassword ? profileConfirmPassword.value : '';

            if (!name) {
                showAlert('Full name is required.');
                if (profileName) profileName.focus();
                return;
            }

            if (!email) {
                showAlert('A valid email address is required.');
                if (profileEmail) profileEmail.focus();
                return;
            }

            if (newPass || confirmPass || currentPass) {
                if (!currentPass) {
                    showAlert('Please enter your current password to confirm security changes.');
                    if (profileCurrentPassword) profileCurrentPassword.focus();
                    return;
                }

                if (newPass.length < 6) {
                    showAlert('New password must be at least 6 characters.');
                    if (profileNewPassword) profileNewPassword.focus();
                    return;
                }

                if (newPass !== confirmPass) {
                    showAlert('New password and confirmation do not match.');
                    if (profileConfirmPassword) profileConfirmPassword.focus();
                    return;
                }
            }

            if (saveProfileBtn) {
                saveProfileBtn.disabled = true;
                saveProfileBtn.textContent = 'Saving Changes...';
            }

            try {
                const res = await fetch('/api/user/profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name,
                        email,
                        current_password: currentPass,
                        new_password: newPass,
                        confirm_password: confirmPass,
                    }),
                });

                const data = await res.json();

                if (data.success) {
                    showAlert(data.message || 'Profile updated successfully.', 'success');

                    // Update header name and avatar dynamically
                    if (data.user && data.user.name) {
                        if (headerUserName) {
                            headerUserName.textContent = data.user.name;
                        }
                        const avatar = document.querySelector('.header-user-btn .profile-avatar');
                        if (avatar) {
                            avatar.textContent = data.user.name.charAt(0).toUpperCase();
                        }
                    }

                    // Reset password fields
                    if (profileCurrentPassword) profileCurrentPassword.value = '';
                    if (profileNewPassword) profileNewPassword.value = '';
                    if (profileConfirmPassword) profileConfirmPassword.value = '';
                } else {
                    showAlert(data.message || 'Failed to update profile.');
                }
            } catch (err) {
                console.error('Profile update error:', err);
                showAlert('A network error occurred while updating your profile. Please try again.');
            } finally {
                if (saveProfileBtn) {
                    saveProfileBtn.disabled = false;
                    saveProfileBtn.textContent = 'Save Profile Changes';
                }
            }
        });
    }
});
