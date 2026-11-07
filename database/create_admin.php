<?php
// =====================================================================
// Création d'un compte administrateur - en LIGNE DE COMMANDE uniquement :
//   php database/create_admin.php
// =====================================================================

// PHP_SAPI vaut 'cli' quand le script est lancé dans un terminal.
// Si quelqu'un l'appelait depuis un navigateur, on refuse.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

require __DIR__ . '/../includes/db.php';

// readline : pose une question dans le terminal et lit la réponse
$email = trim(readline('Email : '));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Email invalide.\n");
}

// "stty -echo" : le terminal n'affiche plus les caractères tapés (comme pour sudo)
echo 'Mot de passe (12 caractères minimum) : ';
system('stty -echo');
$password = rtrim(fgets(STDIN), "\r\n");
system('stty echo');
echo "\n";

if (mb_strlen($password) < 12) {
    exit("Mot de passe trop court.\n");
}

// password_hash calcule une EMPREINTE à sens unique (algorithme bcrypt) :
// impossible de retrouver le mot de passe à partir d'elle.
// Le "sel" aléatoire est inclus : deux mots de passe identiques
// donnent deux empreintes différentes.
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = db()->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $stmt->execute([$email, $hash]);
    echo "Compte créé pour $email\n";
} catch (PDOException $e) {
    // 23000 = violation de contrainte : ici, la colonne email est UNIQUE
    if ($e->getCode() === '23000') {
        exit("Un compte existe déjà avec cet email.\n");
    }
    throw $e;
}
