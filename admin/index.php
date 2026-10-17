<?php
// =====================================================================
// Tableau de bord : liste de toutes les œuvres (brouillons compris)
// Filtre facultatif par rubrique : index.php?category=films
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

// Première ligne de CHAQUE page protégée : sans connexion, on est renvoyé vers login.php
require_login();

// Les rubriques possibles (les mêmes que l'ENUM de la colonne works.category)
$categories = [
    'works'      => 'Works',
    'public_art' => 'Public art',
    'films'      => 'Films',
];

// ---------------------------------------------------------------------
// 1. Lire et VALIDER le filtre
// ---------------------------------------------------------------------
// LISTE BLANCHE : on n'accepte que les valeurs qu'on connaît.
// Tout le reste (faute de frappe, tentative de piratage...) = pas de filtre.
$category = $_GET['category'] ?? '';
if (!array_key_exists($category, $categories)) {
    $category = '';
}

// ---------------------------------------------------------------------
// 2. Construire la requête
// ---------------------------------------------------------------------
// La SOUS-REQUÊTE entre parenthèses est exécutée pour chaque œuvre :
// elle compte les lignes de work_images qui lui appartiennent.
// "AS image_count" donne un nom à ce résultat calculé.
$sql = 'SELECT id, slug, title, category, year, published,
               (SELECT COUNT(*) FROM work_images WHERE work_images.work_id = works.id) AS image_count
        FROM works';
$params = [];

if ($category !== '') {
    $sql .= ' WHERE category = ?';   // .= ajoute du texte à la fin de la chaîne
    $params[] = $category;           // [] = ajoute un élément à la fin du tableau
}

$sql .= ' ORDER BY category, position, id';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$works = $stmt->fetchAll();

$pageTitle = 'Œuvres';
require __DIR__ . '/../includes/admin_header.php';
?>
        <div class="admin-toolbar">
            <h1>Œuvres <small>(<?= count($works) ?>)</small></h1>
            <!-- edit.php sans id = création d'une nouvelle œuvre (étape 7) -->
            <a class="button" href="edit.php">+ Nouvelle œuvre</a>
        </div>

        <nav class="admin-filters">
            <a href="index.php" class="<?= $category === '' ? 'active' : '' ?>">Toutes</a>
            <?php foreach ($categories as $key => $label): ?>
                <a href="index.php?category=<?= e($key) ?>" class="<?= $category === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if (!$works): ?>
            <p class="admin-empty">Aucune œuvre pour le moment.</p>
        <?php else: ?>
            <!-- Le conteneur permet de faire défiler le tableau horizontalement sur téléphone -->
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Rubrique</th>
                            <th>Année</th>
                            <th>Images</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($works as $work): ?>
                            <tr>
                                <td><strong><?= e($work['title']) ?></strong></td>
                                <td><?= e($categories[$work['category']]) ?></td>
                                <!-- (string) : e() attend du texte, l'année est un nombre (ou null) -->
                                <td><?= e((string) $work['year']) ?></td>
                                <td><?= (int) $work['image_count'] ?></td>
                                <td>
                                    <?php if ($work['published']): ?>
                                        <span class="badge badge-published">Publié</span>
                                    <?php else: ?>
                                        <span class="badge badge-draft">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-actions">
                                    <a href="edit.php?id=<?= (int) $work['id'] ?>">Modifier</a>
                                    <?php if ($work['published']): ?>
                                        <!-- target="_blank" : ouvre le site dans un nouvel onglet -->
                                        <a href="../oeuvre.php?slug=<?= e(urlencode($work['slug'])) ?>" target="_blank">Voir</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
