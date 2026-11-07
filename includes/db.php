<?php
// =====================================================================
// Connexion à la base de données
//
// Utilisation dans n'importe quelle page :
//   require __DIR__ . '/includes/db.php';
//   $works = db()->query('SELECT * FROM works')->fetchAll();
// =====================================================================

function db(): PDO
{
    // "static" : la variable garde sa valeur entre deux appels de la fonction.
    // On ne se connecte donc qu'UNE fois par page, même si db() est appelée 10 fois.
    static $pdo = null;

    if ($pdo === null) {
        // __DIR__ = le dossier de CE fichier (includes/), quel que soit
        // l'endroit d'où il est inclus.
        $config = require __DIR__ . '/config.php';

        // DSN = "Data Source Name" : l'adresse de la base, au format attendu par PDO.
        // charset=utf8mb4 : indispensable pour que les accents arrivent intacts.
        $dsn = 'mysql:host=' . $config['db_host']
             . ';dbname=' . $config['db_name']
             . ';charset=utf8mb4';

        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            // En cas d'erreur SQL, PDO lance une exception (au lieu de rien dire)
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Chaque ligne est renvoyée comme un tableau ['title' => 'FLÂNEUR', ...]
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Les requêtes préparées sont faites par MariaDB lui-même (plus sûr)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}
