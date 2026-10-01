<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'TimeSheet' ?></title>
    <?php if (isset($cssFiles) && is_array($cssFiles)): ?>
        <?php foreach ($cssFiles as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="theme-dark">
<header>
    <div class="header-left">
        <h1>TimeSheet</h1>
    </div>
    <div class="header-right">
        <ul>
            <li><a href="/">Home</a></li>
        </ul>
        <a href="/logout" class="nav-logout">Logout</a>
        <div class="profile">
            P
        </div>
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" aria-label="Toggle theme">
            <span class="theme-icon light-icon">&#9788;</span>
            <span class="theme-icon dark-icon">&#9790;</span>
        </button>
    </div>
</header>