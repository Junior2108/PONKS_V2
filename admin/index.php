<?php
// =====================================================================
// Tableau de bord : liste de toutes les œuvres (brouillons compris)
// Filtre facultatif par rubrique : index.php?filter=films
// =====================================================================

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

// Première ligne de CHAQUE page protégée : sans connexion, on est renvoyé vers login.php
require_login();

// Les filtres possibles : clé dans l'URL => [colonne en base, texte affiché]
// LISTE BLANCHE : un nom de colonne ne peut pas être passé avec "?" dans
// une requête préparée. On ne met donc dans le SQL que des noms de ce tableau.
$filters = [
    'public_art' => ['in_public_art', 'Public art'],
    'films'      => ['in_films', 'Films'],
    'home'       => ['on_home', 'Accueil'],
    'drafts'     => ['NOT published', 'Brouillons'],
];

// ---------------------------------------------------------------------
// 1. Lire et VALIDER le filtre
// ---------------------------------------------------------------------
// Tout ce qui n'est pas dans la liste (faute de frappe, tentative de piratage...) = pas de filtre.
$filter = $_GET['filter'] ?? '';
if (!array_key_exists($filter, $filters)) {
    $filter = '';
}

// ---------------------------------------------------------------------
// 2. Construire la requête
// ---------------------------------------------------------------------
// La SOUS-REQUÊTE entre parenthèses est exécutée pour chaque œuvre :
// elle compte les lignes de work_images qui lui appartiennent.
// "AS image_count" donne un nom à ce résultat calculé.
$sql = 'SELECT id, slug, title, year, published, in_public_art, in_films, on_home, home_hero,
               (SELECT COUNT(*) FROM work_images WHERE work_images.work_id = works.id) AS image_count
        FROM works';

if ($filter !== '') {
    $sql .= ' WHERE ' . $filters[$filter][0];   // texte venant UNIQUEMENT de $filters
}

$sql .= ' ORDER BY position, id';

$works = db()->query($sql)->fetchAll();

$pageTitle = 'Œuvres';
require __DIR__ . '/../includes/admin_header.php';
?>
        <div class="admin-toolbar">
            <h1>Œuvres <small>(<?= count($works) ?>)</small></h1>
            <!-- edit.php sans id = création d'une nouvelle œuvre -->
            <a class="button" href="edit.php">+ Nouvelle œuvre</a>
        </div>

        <nav class="admin-filters">
            <a href="index.php" class="<?= $filter === '' ? 'active' : '' ?>">Toutes</a>
            <?php foreach ($filters as $key => [, $label]): ?>
                <a href="index.php?filter=<?= e($key) ?>" class="<?= $filter === $key ? 'active' : '' ?>"><?= e($label) ?></a>
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
                            <th>Rubriques</th>
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
                                <td>
                                    <?php if ($work['in_public_art']): ?><span class="badge">Public art</span><?php endif; ?>
                                    <?php if ($work['in_films']): ?><span class="badge">Films</span><?php endif; ?>
                                    <?php if ($work['on_home']): ?><span class="badge">Accueil</span><?php endif; ?>
                                    <?php if ($work['home_hero']): ?><span class="badge badge-published">À la une</span><?php endif; ?>
                                </td>
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
                                        <a href="../<?= e(work_url($work['slug'])) ?>" target="_blank">Voir</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
