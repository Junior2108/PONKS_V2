<?php
// =====================================================================
// Authentification : sessions, connexion obligatoire, protection CSRF
// =====================================================================

// ---------------------------------------------------------------------
// Démarrer la session
// ---------------------------------------------------------------------
// Une SESSION = un petit espace de stockage sur le serveur, propre à chaque
// visiteur. Le navigateur ne reçoit qu'un identifiant (le cookie PHPSESSID) ;
// les données ($_SESSION) restent sur le serveur, le visiteur ne peut pas les modifier.
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,            // le cookie disparaît à la fermeture du navigateur
            'httponly' => true,         // JavaScript ne peut pas lire le cookie (limite les dégâts d'un XSS)
            'samesite' => 'Lax',        // le cookie n'est pas envoyé par les formulaires d'autres sites
            'secure'   => !empty($_SERVER['HTTPS']), // en HTTPS : cookie jamais envoyé en clair
        ]);
        session_start();
    }
}

// ---------------------------------------------------------------------
// Bloquer l'accès aux visiteurs non connectés
// ---------------------------------------------------------------------
// À appeler TOUT EN HAUT de chaque page de l'admin (sauf login.php).
function require_login(): void
{
    start_session();

    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');  // redirection vers la page de connexion
        exit;                            // INDISPENSABLE : sinon la suite de la page s'exécute quand même !
    }
}

// ---------------------------------------------------------------------
// Protection CSRF (Cross-Site Request Forgery)
// ---------------------------------------------------------------------
// Danger : un site pirate peut contenir un formulaire caché qui envoie
// "supprimer l'œuvre 3" vers TON admin. Si tu es connecté, ton navigateur
// joint ton cookie de session... et la suppression a lieu.
//
// Parade : chaque formulaire contient un jeton secret aléatoire, stocké
// aussi en session. Le site pirate ne peut pas connaître ce jeton.

// Renvoie le jeton (le crée au premier appel)
function csrf_token(): string
{
    start_session();

    if (empty($_SESSION['csrf'])) {
        // random_bytes : hasard cryptographique, impossible à deviner
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

// Champ caché à placer dans CHAQUE <form method="post">
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// À appeler au début du traitement de CHAQUE formulaire POST
function csrf_check(): void
{
    start_session();

    // hash_equals : comparaison à durée constante (ne révèle rien par le temps de réponse)
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        exit('Formulaire expiré ou invalide. Recharge la page et réessaie.');
    }
}

// ---------------------------------------------------------------------
// Messages "flash" : affichés UNE seule fois, sur la page suivante
// ---------------------------------------------------------------------
// Après un enregistrement, on redirige vers une autre page : le message
// "Œuvre enregistrée" doit donc survivre à la redirection. On le range
// en session, et on l'efface dès qu'il a été lu.

// $type : 'success' (vert) ou 'error' (rouge)
function flash(string $message, string $type = 'success'): void
{
    start_session();
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

// Renvoie ['message' => ..., 'type' => ...] ou null s'il n'y a rien
function take_flash(): ?array
{
    start_session();
    $message = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $message;
}
