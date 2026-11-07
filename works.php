<?php
// =====================================================================
// Liste des œuvres : toutes, ou une rubrique
//   works.html       -> works.php                      (toutes les œuvres)
//   public_art.html  -> works.php?category=public_art   (via le .htaccess)
// =====================================================================

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/works.php';

// Liste blanche : seule la valeur 'public_art' est acceptée, tout le reste = toutes les œuvres
$category = ($_GET['category'] ?? '') === 'public_art' ? 'public_art' : null;

$galleryWorks = published_works($category);
$galleryClass = 'gallery gallery_works_page';

$pageTitle  = $category ? 'PONKS - Public art' : 'PONKS - Works';
$activePage = $category ?? 'works';

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/gallery.php';
require __DIR__ . '/includes/footer.php';
