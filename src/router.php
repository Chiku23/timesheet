<?php
/**
 * Page Router
 * 
 * Handles page (HTML view) routing with auth guards.
 * API routing is handled separately in api.php.
 */

// 1. Get the current URL path requested by the browser
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// 2. Define page routes
$routes = [
    '/'         => ['component' => 'home',     'title' => 'Dashboard - TimeSheet',   'directory' => 'pages', 'auth' => true],
    '/login'    => ['component' => 'login',    'title' => 'Login - TimeSheet',       'directory' => 'pages', 'auth' => false],
    '/signup'   => ['component' => 'signup',   'title' => 'Sign Up - TimeSheet',     'directory' => 'pages', 'auth' => false],
    '/admin'    => ['component' => 'admin',    'title' => 'Admin Panel - TimeSheet', 'directory' => 'pages', 'auth' => true, 'adminOnly' => true],
    '/settings' => ['component' => 'settings', 'title' => 'Settings - TimeSheet',    'directory' => 'pages', 'auth' => true, 'adminOnly' => true],
    '/reports'  => ['component' => 'reports',  'title' => 'Reports - TimeSheet',     'directory' => 'pages', 'auth' => true],
    '/profile'  => ['component' => 'profile',  'title' => 'My Profile - TimeSheet',  'directory' => 'pages', 'auth' => true],
];

// 3. Match the route, or fallback to a '404' component
$route = $routes[$requestUri] ?? ['component' => '404', 'title' => 'Page Not Found - TimeSheet', 'directory' => 'pages', 'auth' => false];

// 4. Auth Guard — redirect to /login if page requires auth and user is not logged in
if (!empty($route['auth']) && !isLoggedIn()) {
    header('Location: /login');
    exit;
}

// 5. Admin Guard — redirect to / if page requires admin role
if (!empty($route['adminOnly']) && !isAdmin()) {
    setFlash('error', 'Access denied. Admin privileges required.');
    header('Location: /');
    exit;
}

// 6. If logged in and visiting /login or /signup, redirect to home
if (in_array($requestUri, ['/login', '/signup']) && isLoggedIn()) {
    header('Location: /');
    exit;
}

// 7. Update the global $pageTitle and load the matched component
$pageTitle = $route['title'];
loadComponent($route['component'], ['pageTitle' => $pageTitle], $route['directory']);
