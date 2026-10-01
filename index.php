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

// Global arrays for assets
$cssFiles = ['/assets/css/global.css']; // Add global CSS here
$jsFiles = [
    'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/pdfmake.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.10/vfs_fonts.min.js',
    '/assets/js/global.js'
];    // Add global JS here

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

// Load the application main file app.php
require_once __DIR__ . '/src/app.php';

?>
