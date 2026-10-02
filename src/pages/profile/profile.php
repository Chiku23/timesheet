<?php
$user = currentUser();
$userInitial = $user ? strtoupper(substr($user->name, 0, 1)) : 'U';
$userName = $user ? htmlspecialchars($user->name) : '';
$userEmail = $user ? htmlspecialchars($user->email) : '';
$userRole = $user ? $user->role : 'user';
?>

<div class="profile-page">
    <div class="profile-header">
        <div class="profile-title-zone">
            <div class="profile-header-avatar">
                <?= $userInitial ?>
            </div>
            <div>
                <h2>My Profile &amp; Account</h2>
                <p class="profile-subtitle">Manage your personal credentials, email address, and account security</p>
            </div>
        </div>
    </div>

    <!-- Alert / Feedback Notification -->
    <div id="profileAlert" class="profile-alert" style="display: none;"></div>

    <div class="profile-container">
        <form id="profileForm" class="profile-card">
            <!-- Account Overview Banner -->
            <div class="profile-card-section">
                <h3 class="section-title">Account Information</h3>
                <p class="section-desc">Your basic profile information visible across time tracking logs and audit reports.</p>

                <div class="profile-form-grid">
                    <div class="form-group">
                        <label for="profileName">Full Name <span class="required-star">*</span></label>
                        <input type="text" id="profileName" name="name" class="form-input" value="<?= $userName ?>" required autocomplete="name">
                        <span class="field-hint">Your display name shown on your timesheet logs and team reports.</span>
                    </div>

                    <div class="form-group">
                        <label for="profileEmail">Email Address <span class="required-star">*</span></label>
                        <input type="email" id="profileEmail" name="email" class="form-input" value="<?= $userEmail ?>" required autocomplete="email">
                        <span class="field-hint">Used for system authentication and account recovery notifications.</span>
                    </div>

                    <div class="form-group">
                        <label>Assigned System Role</label>
                        <div class="role-display-box">
                            <span class="role-badge role-<?= $userRole ?>"><?= ucfirst($userRole) ?></span>
                            <span class="role-hint">Account roles and permissions are configured by workspace administrators.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security & Password Section -->
            <div class="profile-card-section">
                <h3 class="section-title">Security &amp; Password</h3>
                <p class="section-desc">Leave these fields blank if you do not want to change your password.</p>

                <div class="profile-form-grid">
                    <div class="form-group">
                        <label for="profileCurrentPassword">Current Password</label>
                        <input type="password" id="profileCurrentPassword" name="current_password" class="form-input" placeholder="Enter current password to verify changes" autocomplete="current-password">
                        <span class="field-hint">Required only if updating your password.</span>
                    </div>

                    <div class="form-row-two">
                        <div class="form-group">
                            <label for="profileNewPassword">New Password</label>
                            <input type="password" id="profileNewPassword" name="new_password" class="form-input" placeholder="Minimum 6 characters" autocomplete="new-password">
                        </div>

                        <div class="form-group">
                            <label for="profileConfirmPassword">Confirm New Password</label>
                            <input type="password" id="profileConfirmPassword" name="confirm_password" class="form-input" placeholder="Re-enter new password" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="profile-form-actions">
                <a href="/" class="btn-cancel-link">Cancel</a>
                <button type="submit" class="btn-primary" id="saveProfileBtn">Save Profile Changes</button>
            </div>
        </form>
    </div>
</div>
