<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <img src="/assets/images/logo.png" alt="TimeSheet Logo" class="auth-logo-img" width="32" height="32">
            </div>
            <h1>Create Account</h1>
            <p class="login-subtitle">Join TimeSheet to start tracking your time</p>
        </div>
        
        <div id="signupError" class="login-error" style="display: none;"></div>
        
        <form id="signupForm" class="login-form" autocomplete="off">
            <div class="form-group">
                <label for="signupName">Full Name</label>
                <input type="text" id="signupName" name="name" placeholder="John Doe" required autofocus>
            </div>
            <div class="form-group">
                <label for="signupEmail">Email Address</label>
                <input type="email" id="signupEmail" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label for="signupPassword">Password</label>
                <input type="password" id="signupPassword" name="password" placeholder="At least 6 characters" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="signupConfirmPassword">Confirm Password</label>
                <input type="password" id="signupConfirmPassword" name="confirm_password" placeholder="Re-enter your password" minlength="6" required>
            </div>
            <button type="submit" class="btn-login" id="signupSubmitBtn">
                <span class="btn-text">Create Account</span>
                <span class="btn-loader" style="display:none;">Creating account...</span>
            </button>
        </form>
        
        <div class="auth-switch">
            <p>Already have an account? <a href="/login">Sign in</a></p>
        </div>
    </div>
</div>
