<?php
// =====================================================================
// Gestion des images d'une œuvre (formulaires envoyés depuis edit.php)
//   images.php?id=3   + POST action=upload  -> ajouter des images
//                     + POST action=cover   -> choisir l'image de couverture
//                     + POST action=delete  -> supprimer une image
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

require_login();

// Taille maximale d'UNE image : 15 Mo
const MAX_IMAGE_SIZE = 15 * 1024 * 1024;

// Types acceptés : type réel du fichier => extension qu'on lui donnera
const ALLOWED_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

// Dossier sur le disque, et chemin équivalent pour le site (stocké en base)
const UPLOAD_DIR  = __DIR__ . '/../uploads/works/';
const UPLOAD_PATH = 'uploads/works/';

// ---------------------------------------------------------------------
// 1. Quelle œuvre ?
// ---------------------------------------------------------------------
// L'id est dans l'URL (et pas dans le formulaire) : on le connaît même
// si PHP a dû jeter le contenu du formulaire (voir étape 2).
$id = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare('SELECT id, thumbnail FROM works WHERE id = ?');
$stmt->execute([$id]);
$work = $stmt->fetch();

if (!$work || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$backUrl = 'edit.php?id=' . $id . '#images';

// ---------------------------------------------------------------------
// 2. Envoi trop volumineux ?
// ---------------------------------------------------------------------
// Si le total dépasse post_max_size, PHP jette TOUT : $_POST et $_FILES
// arrivent vides... y compris le jeton CSRF. Sans ce test, l'utilisateur
// verrait "Formulaire expiré", ce qui serait très déroutant.
if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    flash('Envoi trop volumineux : envoie moins d\'images à la fois.', 'error');
    header('Location: ' . $backUrl);
    exit;
}

csrf_check();

$action = $_POST['action'] ?? '';

// ---------------------------------------------------------------------
// 3. Ajouter des images
// ---------------------------------------------------------------------
if ($action === 'upload') {
    // Avec <input name="images[]" multiple>, $_FILES est organisé "à l'envers" :
    //   $_FILES['images']['name'][0], $_FILES['images']['name'][1]...
    //   $_FILES['images']['tmp_name'][0], ...
    $files = $_FILES['images'] ?? ['name' => []];
    $count = is_array($files['name']) ? count($files['name']) : 0;

    // finfo lit les premiers octets du fichier pour savoir ce qu'il EST vraiment.
    // On ne fait JAMAIS confiance à l'extension ni au type annoncé par le navigateur :
    // il suffit de renommer "virus.php" en "photo.jpg" pour les tromper.
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    // Les nouvelles images se placent après les existantes.
    // COALESCE(x, -1) : si l'œuvre n'a encore aucune image, MAX vaut NULL -> -1
    $stmt = db()->prepare('SELECT COALESCE(MAX(position), -1) + 1 FROM work_images WHERE work_id = ?');
    $stmt->execute([$id]);
    $position = (int) $stmt->fetchColumn();

    $insert = db()->prepare('INSERT INTO work_images (work_id, path, position) VALUES (?, ?, ?)');

    $added  = [];
    $errors = [];

    for ($i = 0; $i < $count; $i++) {
        $name  = $files['name'][$i];
        $error = $files['error'][$i];
        $tmp   = $files['tmp_name'][$i];

        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;   // champ laissé vide
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE || $files['size'][$i] > MAX_IMAGE_SIZE) {
            $errors[] = "$name : trop lourde (15 Mo maximum)";
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = "$name : envoi interrompu";
            continue;
        }

        $type = $finfo->file($tmp);
        if (!isset(ALLOWED_TYPES[$type])) {
            $errors[] = "$name : format refusé (JPEG, PNG ou WebP uniquement)";
            continue;
        }

        // Deuxième vérification : PHP arrive-t-il à lire les dimensions de l'image ?
        // Un faux fichier qui imite juste le début d'une image échoue ici.
        if (getimagesize($tmp) === false) {
            $errors[] = "$name : fichier image illisible";
            continue;
        }

        // NOM ALÉATOIRE : on n'utilise JAMAIS le nom d'origine. Il pourrait contenir
        // "../" (sortir du dossier), écraser une autre image, ou finir par ".php".
        // L'extension vient du type RÉEL détecté, pas de ce qu'a envoyé l'utilisateur.
        $filename = bin2hex(random_bytes(16)) . '.' . ALLOWED_TYPES[$type];

        // move_uploaded_file vérifie en plus que $tmp est bien un fichier
        // qui vient d'être envoyé (et pas, par exemple, /etc/passwd)
        if (!move_uploaded_file($tmp, UPLOAD_DIR . $filename)) {
            $errors[] = "$name : impossible d'enregistrer le fichier (droits du dossier ?)";
            continue;
        }

        $insert->execute([$id, UPLOAD_PATH . $filename, $position++]);
        $added[] = UPLOAD_PATH . $filename;
    }

    // Pas encore d'image de couverture ? On prend la première ajoutée.
    if ($added && !$work['thumbnail']) {
        db()->prepare('UPDATE works SET thumbnail = ? WHERE id = ?')->execute([$added[0], $id]);
    }

    if ($errors) {
        flash(count($added) . ' image(s) ajoutée(s). Refusées : ' . implode(' ; ', $errors), 'error');
    } elseif ($added) {
        flash(count($added) . ' image(s) ajoutée(s).');
    } else {
        flash('Aucune image sélectionnée.', 'error');
    }
}

// ---------------------------------------------------------------------
// 4. Choisir l'image de couverture, ou supprimer une image
// ---------------------------------------------------------------------
if ($action === 'cover' || $action === 'delete') {
    // "AND work_id = ?" : on vérifie que l'image appartient bien à CETTE œuvre
    $stmt = db()->prepare('SELECT id, path FROM work_images WHERE id = ? AND work_id = ?');
    $stmt->execute([(int) ($_POST['image_id'] ?? 0), $id]);
    $image = $stmt->fetch();

    if (!$image) {
        flash('Image introuvable.', 'error');
    } elseif ($action === 'cover') {
        db()->prepare('UPDATE works SET thumbnail = ? WHERE id = ?')->execute([$image['path'], $id]);
        flash('Image de couverture modifiée.');
    } else {
        db()->prepare('DELETE FROM work_images WHERE id = ?')->execute([$image['id']]);

        // On supprime le fichier UNIQUEMENT s'il a été envoyé depuis l'admin.
        // Les images de img/ font partie du site (et de Git) : on n'y touche pas.
        if (str_starts_with($image['path'], UPLOAD_PATH)) {
            // basename : ne garde que le nom du fichier, par sécurité
            $file = UPLOAD_DIR . basename($image['path']);
            if (is_file($file)) {
                unlink($file);
            }
        }

        // C'était la couverture ? On la retire.
        if ($work['thumbnail'] === $image['path']) {
            db()->prepare('UPDATE works SET thumbnail = NULL WHERE id = ?')->execute([$id]);
        }

        flash('Image supprimée.');
    }
}

header('Location: ' . $backUrl);
exit;
