<?php
// =====================================================================
// Supprimer une œuvre (formulaire POST envoyé depuis edit.php)
//   delete.php?id=3
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

require_login();

// Une suppression ne se fait JAMAIS par un simple lien (GET) :
// uniquement par un formulaire POST, protégé par le jeton CSRF.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

csrf_check();

$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT id, title FROM works WHERE id = ?');
$stmt->execute([$id]);
$work = $stmt->fetch();

if (!$work) {
    flash('Œuvre introuvable.', 'error');
    header('Location: index.php');
    exit;
}

// ---------------------------------------------------------------------
// 1. Noter les fichiers à effacer AVANT de supprimer les lignes
// ---------------------------------------------------------------------
// Après le DELETE, ces lignes n'existeront plus : on ne saurait plus
// quels fichiers appartenaient à l'œuvre.
$stmt = db()->prepare('SELECT path FROM work_images WHERE work_id = ?');
$stmt->execute([$id]);
$paths = $stmt->fetchAll(PDO::FETCH_COLUMN);   // FETCH_COLUMN : un simple tableau de chemins

// ---------------------------------------------------------------------
// 2. Supprimer l'œuvre dans la base
// ---------------------------------------------------------------------
// ON DELETE CASCADE (voir database/schema.sql) : MariaDB supprime
// automatiquement toutes les lignes de work_images liées à cette œuvre.
db()->prepare('DELETE FROM works WHERE id = ?')->execute([$id]);

// ---------------------------------------------------------------------
// 3. Effacer les fichiers sur le disque
// ---------------------------------------------------------------------
// La base ne sait RIEN des fichiers : le CASCADE efface des lignes,
// pas des images. C'est donc à nous de le faire.
//
// Pourquoi APRÈS la base ? Si l'effacement d'un fichier échoue, il reste
// juste un fichier orphelin sur le disque (sans conséquence). Dans l'ordre
// inverse, un échec de la base laisserait une œuvre pointant vers des
// images disparues (images cassées sur le site).
foreach ($paths as $path) {
    // Uniquement les images envoyées depuis l'admin, jamais celles de img/
    if (str_starts_with($path, 'uploads/works/')) {
        $file = __DIR__ . '/../uploads/works/' . basename($path);
        if (is_file($file)) {
            unlink($file);
        }
    }
}

flash('Œuvre « ' . $work['title'] . ' » supprimée.');
header('Location: index.php');
exit;
