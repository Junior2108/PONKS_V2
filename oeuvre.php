<?php
// =====================================================================
// Page d'une œuvre, générée à partir de la base de données.
// Adresse : oeuvre.php?slug=djerbism
// =====================================================================

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';

// ---------------------------------------------------------------------
// 1. Lire le slug dans l'URL
// ---------------------------------------------------------------------
// $_GET contient les paramètres après le "?" de l'adresse.
// ?? '' : si le paramètre est absent, on prend une chaîne vide.
$slug = $_GET['slug'] ?? '';

// ---------------------------------------------------------------------
// 2. Chercher l'œuvre en base - REQUÊTE PRÉPARÉE
// ---------------------------------------------------------------------
// Le "?" est un emplacement réservé. La valeur est envoyée SÉPARÉMENT
// de la requête : MariaDB la traite toujours comme une simple donnée,
// jamais comme du SQL. C'est ce qui empêche l'injection SQL.
$stmt = db()->prepare('SELECT * FROM works WHERE slug = ? AND published = 1');
$stmt->execute([$slug]);
$work = $stmt->fetch();   // une ligne, ou false si rien trouvé

// ---------------------------------------------------------------------
// 3. Œuvre introuvable : page 404
// ---------------------------------------------------------------------
if (!$work) {
    // Code 404 = "n'existe pas". Important pour Google : il n'indexera pas cette page.
    http_response_code(404);
    $pageTitle = 'PONKS - Not found';
    require __DIR__ . '/includes/header.php';
    echo '<p>This work does not exist.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ---------------------------------------------------------------------
// 4. Les images de l'œuvre, dans l'ordre
// ---------------------------------------------------------------------
$stmt = db()->prepare('SELECT path, caption FROM work_images WHERE work_id = ? ORDER BY position, id');
$stmt->execute([$work['id']]);
$images = $stmt->fetchAll();

// Grande image : la première de la galerie, sinon la miniature de l'œuvre
$mainImage = $images[0]['path'] ?? $work['thumbnail'];

// ---------------------------------------------------------------------
// 5. Les autres œuvres de la même rubrique (galerie du bas)
// ---------------------------------------------------------------------
$stmt = db()->prepare(
    'SELECT slug, title, thumbnail FROM works
     WHERE published = 1 AND category = ? AND id <> ?
     ORDER BY position, id'
);
$stmt->execute([$work['category'], $work['id']]);
$otherWorks = $stmt->fetchAll();

// ---------------------------------------------------------------------
// 6. Préparer le bloc d'infos : une ligne de texte = un <p>
// ---------------------------------------------------------------------
// \R = n'importe quel retour à la ligne (Windows, Mac ou Linux).
// array_filter retire les lignes vides.
$infoLines = array_filter(array_map('trim', preg_split('/\R/', $work['info'] ?? '')));

// ---------------------------------------------------------------------
// 7. Affichage
// ---------------------------------------------------------------------
$pageTitle  = 'PONKS - ' . $work['title'];
$activePage = $work['category'];
require __DIR__ . '/includes/header.php';
?>
            <div class="hero_work">
                <div class="hero_work_gallery">
                    <div class="hero_work_image">
                        <img
                            height="768"
                            width="768"
                            src="<?= e($mainImage) ?>"
                            alt="<?= e($work['title']) ?>"
                            id="MainImage"
                        >
                    </div>
                    <div class="hero_work_thumb_image">
                        <?php foreach ($images as $image): ?>
                            <div class="thumbnail_block">
                                <img
                                    height="200"
                                    width="200"
                                    src="<?= e($image['path']) ?>"
                                    alt="<?= e($image['caption'] ?: $work['title']) ?>"
                                    class="thumbnail"
                                >
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="hero_work_text">
                    <h2><?= e($work['title']) ?></h2>
                    <div class="hero_work_info">
                        <?php foreach ($infoLines as $line): ?>
                            <p><?= e($line) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="hero_work_description">
                        <?php if ($work['intro']): ?>
                            <p class="p_title_description"><?= e($work['intro']) ?></p>
                            <p class="slashes_description">
                                ///////////////////////////////////////////////////////////////////////////
                            </p>
                        <?php endif; ?>
                        <?php if ($work['description']): ?>
                            <!-- ORDRE IMPORTANT : d'abord e() pour neutraliser le HTML,
                                 ensuite nl2br() qui transforme les retours à la ligne en <br> -->
                            <p><?= nl2br(e($work['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <section class="gallery gallery_works_page">
                <?php foreach ($otherWorks as $other): ?>
                    <div class="gallery_block">
                        <!-- urlencode : rend le slug sûr à l'intérieur d'une adresse -->
                        <a href="oeuvre.php?slug=<?= e(urlencode($other['slug'])) ?>">
                            <div class="gallery_img">
                                <img
                                    height="768"
                                    width="768"
                                    src="<?= e($other['thumbnail']) ?>"
                                    alt=""
                                >
                                <div class="pink-filter"></div>
                            </div>
                            <div class="gallery_block_title">
                                <p><?= e($other['title']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
