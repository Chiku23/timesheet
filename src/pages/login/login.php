<div class="login-page">
    <div class="login-card">
        <div class="login-header">
            <div class="login-logo">
                <img src="/assets/images/logo.png" alt="TimeSheet Logo" class="auth-logo-img" width="32" height="32">
            </div>
            <h1>TimeSheet</h1>
            <p class="login-subtitle">Track your time, boost your productivity</p>
        </div>
        
        <div id="loginError" class="login-error" style="display: none;"></div>
        
        <form id="loginForm" class="login-form" autocomplete="off">
            <div class="form-group">
                <label for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" name="email" placeholder="you@example.com" required autofocus>
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login" id="loginSubmitBtn">
                <span class="btn-text">Sign In</span>
                <span class="btn-loader" style="display:none;">Signing in...</span>
            </button>
        </form>
        
        <div class="auth-switch">
            <p>Don't have an account? <a href="/signup">Sign up</a></p>
        </div>
    </div>
</div>
