<?php
require_once __DIR__ . '/../../config/database.php';
requireAdmin();
$pageTitle = $pageTitle ?? 'Admin dashboard';
$adminName = $_SESSION['admin_name'] ?? 'Administrator';
$currentAdminPage = $currentAdminPage ?? 'dashboard';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> · StayLocal admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="admin-body">
<div class="admin-app">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="index.php"><strong>StayLocal</strong><small>property desk</small></a>
        <div class="admin-user"><div><strong><?= e($adminName) ?></strong><small>Administrator</small></div></div>
        <nav class="admin-nav" aria-label="Admin navigation">
            <p class="admin-nav-label">Core workspace</p>
            <a class="<?= $currentAdminPage === 'dashboard' ? 'active' : '' ?>" href="index.php"><span>01</span>Overview</a>
            <a class="<?= $currentAdminPage === 'apartments' ? 'active' : '' ?>" href="apartments.php"><span>02</span>Apartments</a>
            <a class="<?= $currentAdminPage === 'units' ? 'active' : '' ?>" href="units.php"><span>03</span>Units</a>
            <a class="<?= $currentAdminPage === 'reservations' ? 'active' : '' ?>" href="reservations.php"><span>04</span>Reservations</a>
            <a class="<?= $currentAdminPage === 'visits' ? 'active' : '' ?>" href="visits.php"><span>05</span>Visit calendar</a>
            <p class="admin-nav-note">Payments are demo-only until a real gateway is connected.</p>
        </nav>
        <div class="admin-sidebar-bottom"><a href="../index.php">View public website ↗</a><a href="logout.php">Sign out</a></div>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar"><div><p class="eyebrow">STAYLOCAL ADMIN</p><h1><?= e($pageTitle) ?></h1></div><div class="admin-topbar-actions"><a class="admin-public-link" href="../index.php">Public site ↗</a></div></header>
