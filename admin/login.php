<?php
// =====================================================================
// Page de connexion à l'administration
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

start_session();

// Déjà connecté ? Inutile de revoir le formulaire.
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$email = '';

// ---------------------------------------------------------------------
// Le formulaire a été envoyé
// ---------------------------------------------------------------------
// GET  = on demande la page (affichage du formulaire)
// POST = on envoie des données (traitement du formulaire)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';   // pas de trim : un espace peut faire partie du mot de passe

    $stmt = db()->prepare('SELECT id, email, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // password_verify recalcule l'empreinte du mot de passe tapé
    // et la compare à celle stockée en base.
    if ($user && password_verify($password, $user['password_hash'])) {
        // Nouvel identifiant de session après connexion : empêche un pirate
        // qui aurait fixé l'ancien identifiant de récupérer la session ("session fixation")
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email']   = $user['email'];

        header('Location: index.php');
        exit;
    }

    // Une seconde d'attente à chaque échec : rend très lent l'essai
    // automatique de milliers de mots de passe ("force brute")
    sleep(1);

    // Même message que l'email existe ou non : on ne révèle pas
    // aux pirates quelles adresses ont un compte.
    $error = 'Email ou mot de passe incorrect.';
}

$pageTitle = 'Connexion';
require __DIR__ . '/../includes/admin_header.php';
?>
        <form method="post" class="admin-form admin-login">
            <h1>Connexion</h1>

            <?php if ($error): ?>
                <p class="admin-error"><?= e($error) ?></p>
            <?php endif; ?>

            <?= csrf_field() ?>

            <label for="email">Email</label>
            <!-- value : on réaffiche l'email tapé pour ne pas le ressaisir (jamais le mot de passe !) -->
            <input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus autocomplete="username">

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">

            <button type="submit">Se connecter</button>
        </form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
