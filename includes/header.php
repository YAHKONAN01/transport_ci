<?php
require_once __DIR__ . '/../includes/functions.php';
$root = getDepuisRoot();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? clean($pageTitle).' — ' : '' ?>Transport CI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= $root ?>assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="nav-brand" href="<?= $root ?>index.php">
        <div class="brand-icon">🚌</div>
        Transport<span class="brand-dot">CI</span>
    </a>
    <div class="nav-links">
        <a href="<?= $root ?>index.php" <?= basename($_SERVER['SCRIPT_FILENAME'])==='index.php'?'class="active"':'' ?>>Accueil</a>
        <a href="<?= $root ?>compagnies.php" <?= basename($_SERVER['SCRIPT_FILENAME'])==='compagnies.php'?'class="active"':'' ?>>Compagnies</a>
        <a href="<?= $root ?>index.php#destinations">Destinations</a>
        <a href="<?= $root ?>admin/index.php" class="btn-nav">⚙️ Admin</a>
    </div>
</nav>
