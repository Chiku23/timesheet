<?php

// 1. Get the current URL path requested by the browser
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// 2. Define routes (URL Path => ['component' => 'Component Folder Name', 'title' => 'Page Title'])
$routes = [
    '/' => ['component' => 'home', 'title' => 'Home - TimeSheet', 'directory' => 'pages'],
    '/about' => ['component' => 'about', 'title' => 'About Us - TimeSheet', 'directory' => 'pages'],
    '/contact' => ['component' => 'contact', 'title' => 'Contact - TimeSheet', 'directory' => 'pages']
];

// 3. Match the route, or fallback to a '404' component if not found
$route = $routes[$requestUri] ?? ['component' => '404', 'title' => 'Page Not Found - TimeSheet', 'directory' => ''];

// 4. Update the global $pageTitle and load the matched component
$pageTitle = $route['title'];
loadComponent($route['component'], ['pageTitle' => $pageTitle], $route['directory']);
