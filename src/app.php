<?php
$pageTitle = "Home - CorePHP";

// 1. Start capturing the output (Output Buffering)
ob_start();
?>

<main>
    <?php
    require_once __DIR__ . '/router.php';
    ?>
</main>

<?php
// 2. Save the captured main content
$mainContent = ob_get_clean();

// 3. Load Header (It will output <head> with ALL discovered CSS)
loadComponent('header', ['pageTitle' => $pageTitle]);

// 4. Output the main content we captured earlier
echo $mainContent;

// 5. Load Footer (It will output before </body> with ALL discovered JS)
loadComponent('footer');
?>