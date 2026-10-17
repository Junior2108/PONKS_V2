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

$categories = [
    'works'      => 'Works',
    'public_art' => 'Public art',
    'films'      => 'Films',
];

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
        'slug' => '', 'title' => '', 'category' => 'works', 'year' => date('Y'),
        'info' => '', 'intro' => '', 'description' => '', 'thumbnail' => '',
        'position' => 0, 'published' => 0,
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
    $work['slug']        = trim($_POST['slug'] ?? '');
    $work['category']    = $_POST['category'] ?? '';
    $work['year']        = trim($_POST['year'] ?? '');
    $work['info']        = trim($_POST['info'] ?? '');
    $work['intro']       = trim($_POST['intro'] ?? '');
    $work['description'] = trim($_POST['description'] ?? '');
    $work['thumbnail']   = trim($_POST['thumbnail'] ?? '');
    $work['position']    = trim($_POST['position'] ?? '0');
    // Une case à cocher non cochée n'est PAS envoyée du tout : isset suffit
    $work['published']   = isset($_POST['published']) ? 1 : 0;

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
    } else {
        // Le slug doit être unique... sauf qu'il peut être identique à lui-même (id <> ?)
        $stmt = db()->prepare('SELECT COUNT(*) FROM works WHERE slug = ? AND id <> ?');
        $stmt->execute([$work['slug'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['slug'] = 'Une autre œuvre utilise déjà ce slug.';
        }
    }

    // Liste blanche, comme pour le filtre de index.php
    if (!array_key_exists($work['category'], $categories)) {
        $errors['category'] = 'Rubrique invalide.';
    }

    // L'année est facultative, mais si elle est remplie elle doit être plausible
    if ($work['year'] !== '' && !filter_var($work['year'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1900, 'max_range' => 2100]])) {
        $errors['year'] = 'Année invalide (entre 1900 et 2100).';
    }

    if (filter_var($work['position'], FILTER_VALIDATE_INT) === false) {
        $errors['position'] = 'La position doit être un nombre entier.';
    }

    if ($work['thumbnail'] !== '' && !preg_match('#^img/\S+$#', $work['thumbnail'])) {
        $errors['thumbnail'] = 'Le chemin doit commencer par img/ et ne pas contenir d\'espace.';
    }

    // --- Aucune erreur : on enregistre ---
    if (!$errors) {
        $values = [
            $work['slug'],
            $work['title'],
            $work['category'],
            // Champ vide -> NULL en base (et pas une chaîne vide)
            $work['year'] === '' ? null : (int) $work['year'],
            $work['info'],
            $work['intro'],
            $work['description'],
            $work['thumbnail'] === '' ? null : $work['thumbnail'],
            (int) $work['position'],
            $work['published'],
        ];

        if ($id) {
            $stmt = db()->prepare(
                'UPDATE works
                 SET slug = ?, title = ?, category = ?, year = ?, info = ?,
                     intro = ?, description = ?, thumbnail = ?, position = ?, published = ?
                 WHERE id = ?'
            );
            $values[] = $id;   // le dernier "?" : WHERE id = ?
            $stmt->execute($values);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO works (slug, title, category, year, info, intro, description, thumbnail, position, published)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute($values);
            $id = (int) db()->lastInsertId();   // l'id attribué par MariaDB
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
                <a class="button" href="../oeuvre.php?slug=<?= e(urlencode($work['slug'])) ?>" target="_blank">Voir sur le site</a>
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

            <label for="slug">Slug (adresse de la page)</label>
            <input type="text" id="slug" name="slug" value="<?= e($work['slug']) ?>" maxlength="120">
            <p class="field-hint">Laisser vide pour le générer depuis le titre. Exemple : le_tiers_instruit</p>
            <?= field_error($errors, 'slug') ?>

            <div class="form-row">
                <div>
                    <label for="category">Rubrique</label>
                    <select id="category" name="category">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?= e($key) ?>" <?= $work['category'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= field_error($errors, 'category') ?>
                </div>
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
            <p class="field-hint">Les retours à la ligne sont conservés tels quels sur le site.</p>

            <label for="thumbnail">Image de couverture (chemin)</label>
            <input type="text" id="thumbnail" name="thumbnail" value="<?= e($work['thumbnail']) ?>" placeholder="img/DJERBISM/gallery_img/djerbism2.jpg">
            <p class="field-hint">Provisoire : l'envoi d'images depuis l'admin arrive à l'étape 8.</p>
            <?= field_error($errors, 'thumbnail') ?>

            <label class="checkbox">
                <input type="checkbox" name="published" value="1" <?= $work['published'] ? 'checked' : '' ?>>
                Publié (visible sur le site)
            </label>

            <button type="submit">Enregistrer</button>
        </form>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
