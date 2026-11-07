<?php
// =====================================================================
// Début commun aux pages de l'administration
//
// Variable à définir AVANT d'inclure ce fichier (facultative) :
//   $pageTitle : texte de l'onglet du navigateur
// =====================================================================

$pageTitle = $pageTitle ?? 'Administration';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- noindex : demande aux moteurs de recherche de ne pas référencer l'admin -->
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="admin.css">
    <title><?= e($pageTitle) ?> - PONKS admin</title>
</head>
<body>
    <header class="admin-header">
        <a class="admin-logo" href="index.php">PONKS admin</a>

        <?php if (!empty($_SESSION['user_id'])): ?>
            <nav class="admin-nav">
                <a href="index.php">Œuvres</a>
                <a href="pages.php">Pages</a>
                <a href="../" target="_blank">Voir le site</a>
            </nav>
            <!-- La déconnexion est un formulaire POST (et pas un simple lien) :
                 un site pirate ne peut donc pas te déconnecter avec une image <img src="logout.php"> -->
            <form method="post" action="logout.php" class="admin-logout">
                <?= csrf_field() ?>
                <span><?= e($_SESSION['email']) ?></span>
                <button type="submit">Se déconnecter</button>
            </form>
        <?php endif; ?>
    </header>
    <main class="admin-main">
        <?php if ($flash = take_flash()): ?>
            <p class="<?= $flash['type'] === 'error' ? 'admin-error' : 'admin-success' ?>"><?= e($flash['message']) ?></p>
        <?php endif; ?>
