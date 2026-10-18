<?php
// =====================================================================
// Début commun à toutes les pages : <head>, logo et menu.
//
// Variables à définir AVANT d'inclure ce fichier (facultatives) :
//   $pageTitle  : texte de l'onglet du navigateur
//   $activePage : lien du menu à surligner ('works', 'public_art', 'films', 'news', 'cv_bio')
// =====================================================================

$pageTitle  = $pageTitle  ?? 'PONKS';
$activePage = $activePage ?? '';

// Liens du menu : 'clé' => ['adresse', 'texte affiché']
$menu = [
    'works'      => ['works.html',      'works'],
    'public_art' => ['public_art.html', 'public art'],
    'films'      => ['films.html',      'films'],
    'news'       => ['news.html',       'news'],
    'cv_bio'     => ['cv_bio.html',     'cv/bio'],
];
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Titillium+Web:ital,wght@0,200;0,300;0,400;0,600;0,700;0,900;1,200;1,300;1,400;1,600;1,700&display=swap" rel="stylesheet">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="stylesheet" href="css/style375.css">
        <link rel="stylesheet" href="css/style425.css">
        <link rel="stylesheet" href="css/style720.css">
        <link rel="stylesheet" href="css/style768.css">
        <link rel="stylesheet" href="css/style1024.css">
        <link rel="stylesheet" href="css/style1200.css">
        <link rel="stylesheet" href="css/style1440.css">
        <link rel="stylesheet" href="css/style1600.css">
        <link rel="stylesheet" href="css/style1920.css">
        <link rel="stylesheet" href="css/style4k.css">
        <title><?= e($pageTitle) ?></title>
    </head>
    <body>
        <div class="container">
            <header class="header">
                <div class="logo">
                    <a href="./">
                        <img src="img/logo.png" alt="PONKS">
                    </a>
                </div>
            </header>
            <nav>
                <ul>
                    <?php foreach ($menu as $key => [$href, $label]): ?>
                        <li>
                            <a class="nav-link<?= $key === $activePage ? ' active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
