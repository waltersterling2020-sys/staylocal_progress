<?php
$pageTitle = $pageTitle ?? 'StayLocal';
$currentPage = $currentPage ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | StayLocal</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="wordmark" href="index.php" aria-label="StayLocal home"><span class="wordmark-name"><span>Stay</span>Local</span></a>
        <nav class="site-nav" aria-label="Primary navigation">
            <a class="<?= $currentPage === 'homes' ? 'active' : '' ?>" href="index.php">Find a home</a>
            <a href="index.php#how-it-works">How it works</a>
            <a href="index.php#visit">In-person visits</a>
        </nav>
        <div class="header-note">Long-term rentals, made clearer.</div>
    </div>
</header>
<main>
