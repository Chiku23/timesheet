<?php

// If using PHP's built-in development server, route static files directly
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file(__DIR__ . $path)) {
        return false;
    }
}

// Load autoload file
require_once __DIR__ . '/vendor/autoload.php';

// ─── Set Default Timezone to UTC ───
date_default_timezone_set('UTC');

// ─── Start Session ───
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Bootstrap Eloquent Database ───
require_once __DIR__ . '/src/config/database.php';

// Global arrays for assets
$cssFiles = ['/assets/css/global.css'];
$jsFiles = [
    '/assets/js/global.js'
];

/**
 * Dynamically loads a component and auto-registers its CSS/JS if they exist.
 */
function loadComponent($name, $data = [], $baseDir = 'components') {
    global $cssFiles, $jsFiles;
    
    $componentDir = __DIR__ . '/src/' . $baseDir . '/' . $name;
    
    // Auto-discover CSS
    if (file_exists($componentDir . '/' . $name . '.css')) {
        $cssUrl = '/src/' . $baseDir . '/' . $name . '/' . $name . '.css';
        if (!in_array($cssUrl, $cssFiles)) $cssFiles[] = $cssUrl;
    }
    
    // Auto-discover JS
    if (file_exists($componentDir . '/' . $name . '.js')) {
        $jsUrl = '/src/' . $baseDir . '/' . $name . '/' . $name . '.js';
        if (!in_array($jsUrl, $jsFiles)) $jsFiles[] = $jsUrl;
    }
    
    // Extract passed data so it's available as variables in the component file
    if (!empty($data)) extract($data);
    
    // Include the component's PHP template
    if (file_exists($componentDir . '/' . $name . '.php')) {
        require $componentDir . '/' . $name . '.php';
    }
}

/**
 * Helper: Get the currently logged-in user from session.
 * Returns the User model instance or null.
 */
function currentUser(): ?\Chiku\TimeSheet\Models\User {
    static $cachedUser = null;
    static $loaded = false;
    
    if (!$loaded) {
        $loaded = true;
        if (!empty($_SESSION['user_id'])) {
            $cachedUser = \Chiku\TimeSheet\Models\User::find($_SESSION['user_id']);
        }
    }
    return $cachedUser;
}

/**
 * Helper: Check if a user is logged in.
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Helper: Check if the current user is admin.
 */
function isAdmin(): bool {
    $user = currentUser();
    return $user && $user->isAdmin();
}

/**
 * Helper: Get current request URI path.
 */
function currentUri(): string {
    return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
}

/**
 * Helper: Redirect to a URL.
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Helper: Set a flash message.
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Helper: Get and clear flash message.
 */
function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

// ─── Handle API routes BEFORE the layout wrapper ───
// (API endpoints return JSON and exit, so they should not be wrapped in HTML)
require_once __DIR__ . '/src/api.php';

// Load the application main file app.php (HTML layout wrapper)
require_once __DIR__ . '/src/app.php';

?>
