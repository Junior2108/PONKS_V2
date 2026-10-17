<?php
// =====================================================================
// Tableau de bord de l'administration
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

// Première ligne de CHAQUE page protégée : sans connexion, on est renvoyé vers login.php
require_login();

$pageTitle = 'Tableau de bord';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h1>Bienvenue</h1>
        <p>Tu es connecté. La liste des œuvres arrivera ici à l'étape 6.</p>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
