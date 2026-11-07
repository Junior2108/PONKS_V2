<?php
// =====================================================================
// Créer ou modifier une œuvre
//   edit.php       -> nouvelle œuvre (INSERT)
//   edit.php?id=3  -> modifier l'œuvre n°3 (UPDATE)
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

require_login();

// Les cases à cocher "où afficher l'œuvre" : colonne en base => texte affiché
$flags = [
    'in_public_art' => 'Rubrique Public art',
    'in_films'      => 'Rubrique Films',
    'on_home'       => 'Galerie de la page d\'accueil',
    'home_hero'     => 'À la une : en grand en haut de l\'accueil (une seule œuvre à la fois)',
];

// Ces adresses sont déjà prises par des pages du site (voir le .htaccess) :
// une œuvre avec ce slug serait inaccessible.
$reservedSlugs = ['index', 'works', 'public_art', 'films', 'news', 'cv_bio', 'privacy_policy', 'page', 'oeuvre'];

// ---------------------------------------------------------------------
// 1. Création ou modification ?
// ---------------------------------------------------------------------
// (int) : "3" -> 3, "abc" -> 0, absent -> 0. 0 = nouvelle œuvre.
$id = (int) ($_GET['id'] ?? 0);

if ($id) {
    $stmt = db()->prepare('SELECT * FROM works WHERE id = ?');
    $stmt->execute([$id]);
    $work = $stmt->fetch();

    if (!$work) {
        http_response_code(404);
        exit('Œuvre introuvable.');
    }
} else {
    // Valeurs par défaut d'une nouvelle œuvre.
    // Brouillon par défaut : on la publie quand elle est prête.
    $work = [
        'slug' => '', 'title' => '', 'subtitle' => '', 'year' => date('Y'),
        'info' => '', 'intro' => '', 'description' => '', 'video_url' => '', 'thumbnail' => '',
        'position' => 0, 'published' => 0,
        'in_public_art' => 0, 'in_films' => 0, 'on_home' => 0, 'home_hero' => 0,
    ];
}

$errors = [];   // 'nom_du_champ' => 'message d'erreur'

// ---------------------------------------------------------------------
// 2. Traitement du formulaire
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // On récupère CHAQUE champ un par un (jamais "tout $_POST" d'un coup :
    // un pirate pourrait ajouter des champs qu'on n'a pas prévus).
    $work['title']       = trim($_POST['title'] ?? '');
    $work['subtitle']    = trim($_POST['subtitle'] ?? '');
    $work['slug']        = trim($_POST['slug'] ?? '');
    $work['year']        = trim($_POST['year'] ?? '');
    $work['info']        = trim($_POST['info'] ?? '');
    $work['intro']       = trim($_POST['intro'] ?? '');
    $work['description'] = trim($_POST['description'] ?? '');
    $work['video_url']   = trim($_POST['video_url'] ?? '');
    $work['thumbnail']   = trim($_POST['thumbnail'] ?? '');
    $work['position']    = trim($_POST['position'] ?? '0');
    // Une case à cocher non cochée n'est PAS envoyée du tout : isset suffit
    $work['published']   = isset($_POST['published']) ? 1 : 0;
    foreach (array_keys($flags) as $flag) {
        $work[$flag] = isset($_POST[$flag]) ? 1 : 0;
    }

    // Slug laissé vide : on le fabrique à partir du titre
    if ($work['slug'] === '') {
        $work['slug'] = slugify($work['title']);
    }

    // --- Validation : on NE fait JAMAIS confiance aux données reçues ---
    // (les attributs "required" ou "maxlength" du HTML se contournent facilement :
    //  ils servent au confort, la vraie vérification se fait ici, sur le serveur)

    if ($work['title'] === '') {
        $errors['title'] = 'Le titre est obligatoire.';
    } elseif (mb_strlen($work['title']) > 200) {
        $errors['title'] = 'Le titre ne doit pas dépasser 200 caractères.';
    }

    // Lettres minuscules et chiffres, séparés par des "_" ou des "-"
    if ($work['slug'] === '' && isset($errors['title'])) {
        // Slug vide uniquement parce que le titre l'est : l'erreur du titre suffit
    } elseif (!preg_match('/^[a-z0-9]+([_-][a-z0-9]+)*$/', $work['slug']) || strlen($work['slug']) > 120) {
        $errors['slug'] = 'Uniquement des minuscules sans accents, des chiffres, "_" ou "-".';
    } elseif (in_array($work['slug'], $reservedSlugs, true)) {
        $errors['slug'] = 'Ce slug est réservé à une page du site.';
    } else {
        // Le slug doit être unique... sauf qu'il peut être identique à lui-même (id <> ?)
        $stmt = db()->prepare('SELECT COUNT(*) FROM works WHERE slug = ? AND id <> ?');
        $stmt->execute([$work['slug'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['slug'] = 'Une autre œuvre utilise déjà ce slug.';
        }
    }

    if (mb_strlen($work['subtitle']) > 200) {
        $errors['subtitle'] = 'Le sous-titre ne doit pas dépasser 200 caractères.';
    }

    // youtube_id() renvoie null si le lien n'est pas un lien YouTube reconnu
    if ($work['video_url'] !== '' && !youtube_id($work['video_url'])) {
        $errors['video_url'] = 'Lien YouTube non reconnu (ex : https://www.youtube.com/watch?v=...).';
    }

    // L'année est facultative, mais si elle est remplie elle doit être plausible
    if ($work['year'] !== '' && !filter_var($work['year'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1900, 'max_range' => 2100]])) {
        $errors['year'] = 'Année invalide (entre 1900 et 2100).';
    }

    if (filter_var($work['position'], FILTER_VALIDATE_INT) === false) {
        $errors['position'] = 'La position doit être un nombre entier.';
    }

    if ($work['thumbnail'] !== '' && !preg_match('#^(img|uploads)/\S+$#', $work['thumbnail'])) {
        $errors['thumbnail'] = 'Le chemin doit commencer par img/ ou uploads/ et ne pas contenir d\'espace.';
    }

    // --- Aucune erreur : on enregistre ---
    if (!$errors) {
        $values = [
            $work['slug'],
            $work['title'],
            // Champ vide -> NULL en base (et pas une chaîne vide)
            $work['subtitle'] === '' ? null : $work['subtitle'],
            $work['year'] === '' ? null : (int) $work['year'],
            $work['info'],
            $work['intro'],
            $work['description'],
            $work['video_url'] === '' ? null : $work['video_url'],
            $work['thumbnail'] === '' ? null : $work['thumbnail'],
            (int) $work['position'],
            $work['published'],
            $work['in_public_art'],
            $work['in_films'],
            $work['on_home'],
            $work['home_hero'],
        ];

        // Transaction : l'enregistrement ET le retrait de "à la une" des autres
        // œuvres doivent réussir ensemble (sinon : deux œuvres à la une).
        db()->beginTransaction();
        try {
            if ($id) {
                $stmt = db()->prepare(
                    'UPDATE works
                     SET slug = ?, title = ?, subtitle = ?, year = ?, info = ?, intro = ?,
                         description = ?, video_url = ?, thumbnail = ?, position = ?, published = ?,
                         in_public_art = ?, in_films = ?, on_home = ?, home_hero = ?
                     WHERE id = ?'
                );
                $values[] = $id;   // le dernier "?" : WHERE id = ?
                $stmt->execute($values);
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO works (slug, title, subtitle, year, info, intro, description, video_url,
                                        thumbnail, position, published, in_public_art, in_films, on_home, home_hero)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute($values);
                $id = (int) db()->lastInsertId();   // l'id attribué par MariaDB
            }

            // Une seule œuvre "à la une" : si celle-ci l'est, on décoche toutes les autres
            if ($work['home_hero']) {
                db()->prepare('UPDATE works SET home_hero = 0 WHERE id <> ?')->execute([$id]);
            }

            db()->commit();
        } catch (Throwable $e) {
            db()->rollBack();
            throw $e;
        }

        // POST -> REDIRECT -> GET : après un enregistrement, on redirige.
        // Si l'utilisateur recharge ensuite la page (F5), le navigateur refait
        // un simple GET au lieu de renvoyer le formulaire une 2e fois.
        flash('Œuvre « ' . $work['title'] . ' » enregistrée.');
        header('Location: edit.php?id=' . $id);
        exit;
    }
}

// ---------------------------------------------------------------------
// 3. Affichage du formulaire
// ---------------------------------------------------------------------
// S'il y a des erreurs, $work contient ce que l'utilisateur a tapé :
// le formulaire est réaffiché avec ses saisies, rien n'est perdu.

// Les images (uniquement pour une œuvre déjà enregistrée : il faut son id)
$images = [];
if ($id) {
    $stmt = db()->prepare('SELECT id, path FROM work_images WHERE work_id = ? ORDER BY position, id');
    $stmt->execute([$id]);
    $images = $stmt->fetchAll();
}

$pageTitle = $id ? 'Modifier ' . $work['title'] : 'Nouvelle œuvre';
require __DIR__ . '/../includes/admin_header.php';

// Petite fonction locale pour afficher le message d'erreur d'un champ
function field_error(array $errors, string $field): string
{
    return isset($errors[$field]) ? '<p class="field-error">' . e($errors[$field]) . '</p>' : '';
}
?>
        <p><a href="index.php">← Retour à la liste</a></p>

        <div class="admin-toolbar">
            <h1><?= $id ? 'Modifier' : 'Nouvelle œuvre' ?></h1>
            <?php if ($id && $work['published']): ?>
                <a class="button" href="../<?= e(work_url($work['slug'])) ?>" target="_blank">Voir sur le site</a>
            <?php endif; ?>
        </div>

        <?php if ($errors): ?>
            <p class="admin-error">Le formulaire contient des erreurs : rien n'a été enregistré.</p>
        <?php endif; ?>

        <!-- Pas d'attribut action : le formulaire est renvoyé à la même adresse (avec le ?id=) -->
        <form method="post" class="admin-form">
            <?= csrf_field() ?>

            <label for="title">Titre *</label>
            <input type="text" id="title" name="title" value="<?= e($work['title']) ?>" required maxlength="200">
            <?= field_error($errors, 'title') ?>

            <label for="subtitle">Sous-titre</label>
            <input type="text" id="subtitle" name="subtitle" value="<?= e($work['subtitle']) ?>" maxlength="200" placeholder="(self-portrait of Ponks)">
            <?= field_error($errors, 'subtitle') ?>

            <label for="slug">Slug (adresse de la page)</label>
            <input type="text" id="slug" name="slug" value="<?= e($work['slug']) ?>" maxlength="120">
            <p class="field-hint">Laisser vide pour le générer depuis le titre. Le slug « le_tiers_instruit » donne l'adresse le_tiers_instruit.html</p>
            <?= field_error($errors, 'slug') ?>

            <div class="form-row">
                <div>
                    <label for="year">Année</label>
                    <input type="number" id="year" name="year" value="<?= e((string) $work['year']) ?>" min="1900" max="2100">
                    <?= field_error($errors, 'year') ?>
                </div>
                <div>
                    <label for="position">Position</label>
                    <input type="number" id="position" name="position" value="<?= e((string) $work['position']) ?>">
                    <?= field_error($errors, 'position') ?>
                </div>
            </div>
            <p class="field-hint">Position : les plus petits nombres s'affichent en premier.</p>

            <label for="info">Infos (une par ligne)</label>
            <textarea id="info" name="info" rows="5" placeholder="1400X280X120CM&#10;INDUSTRIAL PAPER ROLLS&#10;2025"><?= e($work['info']) ?></textarea>

            <label for="intro">Phrase d'accroche</label>
            <textarea id="intro" name="intro" rows="2"><?= e($work['intro']) ?></textarea>

            <label for="description">Texte / poème</label>
            <textarea id="description" name="description" rows="14"><?= e($work['description']) ?></textarea>
            <p class="field-hint">Les retours à la ligne sont conservés. Une ligne vide commence un nouveau paragraphe. Les adresses https://… deviennent des liens.</p>

            <label for="video_url">Vidéo YouTube</label>
            <input type="url" id="video_url" name="video_url" value="<?= e($work['video_url']) ?>" placeholder="https://www.youtube.com/watch?v=...">
            <p class="field-hint">Le premier film (par position) qui a une vidéo est mis en vedette sur l'accueil et la page Films.</p>
            <?= field_error($errors, 'video_url') ?>

            <label for="thumbnail">Image de couverture (chemin)</label>
            <input type="text" id="thumbnail" name="thumbnail" value="<?= e($work['thumbnail']) ?>" placeholder="img/DJERBISM/gallery_img/djerbism2.jpg">
            <p class="field-hint">Rempli automatiquement avec le bouton « Couverture » de la section Images.</p>
            <?= field_error($errors, 'thumbnail') ?>

            <fieldset class="admin-flags">
                <legend>Où afficher l'œuvre ?</legend>
                <p class="field-hint">Toutes les œuvres publiées apparaissent dans la rubrique Works.</p>
                <?php foreach ($flags as $flag => $label): ?>
                    <label class="checkbox">
                        <input type="checkbox" name="<?= $flag ?>" value="1" <?= $work[$flag] ? 'checked' : '' ?>>
                        <?= e($label) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <label class="checkbox">
                <input type="checkbox" name="published" value="1" <?= $work['published'] ? 'checked' : '' ?>>
                Publié (visible sur le site)
            </label>

            <button type="submit">Enregistrer</button>
        </form>

        <!-- id="images" : la page images.php nous renvoie directement ici (edit.php?id=3#images) -->
        <section id="images" class="admin-images">
            <h2>Images</h2>

            <?php if (!$id): ?>
                <p class="field-hint">Enregistre d'abord l'œuvre : tu pourras ensuite lui ajouter des images.</p>
            <?php else: ?>
                <div class="image-grid">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="image-card">
                            <!-- ../ : les chemins en base partent de la racine du site, et on est dans admin/ -->
                            <img src="../<?= e($image['path']) ?>" alt="" loading="lazy">

                            <div class="image-move">
                                <?php foreach (['up' => '↑', 'down' => '↓'] as $direction => $arrow): ?>
                                    <form method="post" action="images.php?id=<?= $id ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="move">
                                        <input type="hidden" name="direction" value="<?= $direction ?>">
                                        <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                        <!-- disabled : pas de "monter" pour la 1re image, ni de "descendre" pour la dernière -->
                                        <button type="submit" class="button-small" title="<?= $direction === 'up' ? 'Monter' : 'Descendre' ?>"
                                            <?= ($direction === 'up' && $index === 0) || ($direction === 'down' && $index === count($images) - 1) ? 'disabled' : '' ?>><?= $arrow ?></button>
                                    </form>
                                <?php endforeach; ?>
                                <?php if ($index === 0): ?>
                                    <span class="field-hint">Image principale</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($image['path'] === $work['thumbnail']): ?>
                                <span class="badge badge-published">Couverture</span>
                            <?php else: ?>
                                <form method="post" action="images.php?id=<?= $id ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cover">
                                    <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                    <button type="submit" class="button-small">Couverture</button>
                                </form>
                            <?php endif; ?>

                            <!-- onsubmit + confirm() : boîte de dialogue "Es-tu sûr ?" avant d'envoyer -->
                            <form method="post" action="images.php?id=<?= $id ?>" onsubmit="return confirm('Supprimer cette image ?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="image_id" value="<?= (int) $image['id'] ?>">
                                <button type="submit" class="button-small button-danger">Supprimer</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- enctype="multipart/form-data" : OBLIGATOIRE pour envoyer des fichiers.
                     Sans lui, le navigateur n'envoie que le nom du fichier, pas son contenu. -->
                <form method="post" action="images.php?id=<?= $id ?>" enctype="multipart/form-data" class="admin-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="upload">

                    <label for="images-input">Ajouter des images</label>
                    <!-- name="images[]" + multiple : plusieurs fichiers d'un coup.
                         accept : filtre la fenêtre de choix (confort seulement, pas une sécurité) -->
                    <input type="file" id="images-input" name="images[]" multiple accept="image/jpeg,image/png,image/webp">
                    <p class="field-hint">JPEG, PNG ou WebP, 15 Mo maximum par image. La première image de la liste s'affiche en grand sur le site.</p>

                    <button type="submit">Envoyer</button>
                </form>
            <?php endif; ?>
        </section>

        <?php if ($id): ?>
            <section class="admin-danger-zone">
                <h2>Zone dangereuse</h2>
                <p>Supprime définitivement l'œuvre, ses textes et toutes ses images envoyées depuis l'admin.
                   <strong>Impossible à annuler.</strong> Pour la retirer du site sans la perdre, décoche plutôt « Publié ».</p>
                <form method="post" action="delete.php?id=<?= $id ?>"
                      onsubmit="return confirm('Supprimer définitivement « <?= e(addslashes($work['title'])) ?> » ?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="button-danger">Supprimer cette œuvre</button>
                </form>
            </section>
        <?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
