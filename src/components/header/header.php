<?php
$user = currentUser();
$userInitial = $user ? strtoupper(substr($user->name, 0, 1)) : 'G';
$userName = $user ? htmlspecialchars($user->name) : 'Guest';
$userRole = $user ? $user->role : '';
$requestUri = currentUri();
$isAuthPage = in_array($requestUri, ['/login', '/signup']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TimeSheet - Modern time tracking application for teams and individuals">
    <title><?= $pageTitle ?? 'TimeSheet' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if (isset($cssFiles) && is_array($cssFiles)): ?>
        <?php foreach ($cssFiles as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="theme-dark">
<script>
    if (localStorage.getItem('app-theme') === 'light') {
        document.body.classList.remove('theme-dark');
    }
</script>
<?php if (!$isAuthPage): ?>
<header>
    <div class="header-left">
        <a href="/" class="logo-link" title="TimeSheet Dashboard">
            <img src="/assets/images/logo.png" alt="TimeSheet Logo" class="site-logo-img" width="20" height="20">
            <span class="logo-title">TimeSheet</span>
        </a>
        <nav class="header-nav">
            <ul>
                <li><a href="/" class="<?= ($requestUri ?? '') === '/' ? 'nav-active' : '' ?>">Dashboard</a></li>
                <?php if ($user && $user->isAdmin()): ?>
                    <li><a href="/admin" class="<?= ($requestUri ?? '') === '/admin' ? 'nav-active' : '' ?>">Admin</a></li>
                    <li><a href="/settings" class="<?= ($requestUri ?? '') === '/settings' ? 'nav-active' : '' ?>">Settings</a></li>
                <?php endif; ?>
                <li><a href="/reports" class="<?= ($requestUri ?? '') === '/reports' ? 'nav-active' : '' ?>">Reports</a></li>
            </ul>
        </nav>
    </div>
    <div class="header-right">
        <!-- Timezone Switcher (Local vs UTC) -->
        <div class="tz-switcher" id="tzSwitcher" role="group" aria-label="Display Timezone">
            <button type="button" class="tz-btn active" id="tzLocalBtn" data-tz="local" title="Display in your local browser timezone">Local</button>
            <button type="button" class="tz-btn" id="tzUtcBtn" data-tz="utc" title="Display in UTC (Universal Time)">UTC</button>
        </div>

        <!-- Theme Toggle Button -->
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme" title="Toggle dark/light theme">
            <span class="theme-icon light-icon" title="Switch to light theme">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </span>
            <span class="theme-icon dark-icon" title="Switch to dark theme">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </span>
        </button>

        <div class="header-vdivider"></div>

        <?php if ($user): ?>
            <div class="header-user-wrapper">
                <a href="/profile" class="header-user-btn <?= ($requestUri ?? '') === '/profile' ? 'header-user-btn-active' : '' ?>" title="My Profile & Account">
                    <div class="profile-avatar" title="<?= $userName ?>">
                        <?= $userInitial ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name" id="headerUserName"><?= $userName ?></span>
                        <span class="role-badge role-<?= $userRole ?>"><?= ucfirst($userRole) ?></span>
                    </div>
                </a>
                <a href="/logout" class="nav-logout-btn" title="Sign out of TimeSheet">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </a>
            </div>
        <?php endif; ?>
    </div>
</header>
<?php endif; ?>