<?php
// =====================================================================
// Déconnexion (accepte uniquement un formulaire POST avec jeton CSRF)
// =====================================================================

require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Vider les données de session
    $_SESSION = [];

    // 2. Supprimer le cookie dans le navigateur (date d'expiration dans le passé)
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);

    // 3. Détruire la session côté serveur
    session_destroy();
}

header('Location: login.php');
exit;
