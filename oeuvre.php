<?php
// =====================================================================
// Page d'une œuvre, générée à partir de la base de données.
// Adresse publique : flaneur.html  (le .htaccess appelle en interne oeuvre.php?slug=flaneur)
// =====================================================================

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/works.php';

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
    echo '<section class="cv_bio"><p class="p_title">This page does not exist.</p><p><a href="works.html">See all works</a></p></section>';
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

// Mêmes images pour le composant React WorkGallery (grande image + lightbox)
$galleryProps = [
    'title'  => $work['title'],
    'images' => $images
        ? array_map(fn($image) => ['src' => $image['path'], 'alt' => $image['caption']], $images)
        : [['src' => $mainImage, 'alt' => '']],
];

// ---------------------------------------------------------------------
// 5. Affichage
// ---------------------------------------------------------------------
$pageTitle  = 'PONKS - ' . $work['title'];
$activePage = 'works';
require __DIR__ . '/includes/header.php';
?>
            <div class="hero_work">
                <?php
                // data-react : React remplace ce bloc par le composant WorkGallery.
                // Le HTML ci-dessous reste affiché pour Google et sans JavaScript.
                ?>
                <div class="hero_work_gallery" data-react="WorkGallery" data-props="<?= e(json_encode($galleryProps, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
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
                    <h2>
                        <?= e($work['title']) ?>
                        <?php if ($work['subtitle']): ?>
                            <span class="title_self"><?= e($work['subtitle']) ?></span>
                        <?php endif; ?>
                    </h2>
                    <div class="hero_work_info">
                        <?php foreach (info_lines($work['info']) as $line): ?>
                            <?php // linkify : une adresse https://... dans les infos devient un lien ?>
                            <p><?= linkify(e($line)) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <div class="hero_work_description">
                        <?php if ($work['intro']): ?>
                            <p class="p_title_description"><?= e($work['intro']) ?></p>
                            <p class="slashes_description">
                                ///////////////////////////////////////////////////////////////////////////
                            </p>
                        <?php endif; ?>
                        <?php
                        // text_to_html : une ligne vide = un nouveau paragraphe,
                        // et le texte est échappé AVANT d'ajouter les balises.
                        // (Commentaires PHP et non <!-- HTML --> sur les pages publiques :
                        //  un commentaire HTML est envoyé à chaque visiteur.)
                        ?>
                        <?= text_to_html($work['description']) ?>
                    </div>
                    <?php if ($work['video_url']): ?>
                        <?php $videoWork = $work; require __DIR__ . '/includes/video.php'; ?>
                    <?php endif; ?>
                </div>
            </div>
<?php
// Galerie du bas : toutes les autres œuvres publiées
$galleryWorks = published_works(null, $work['id']);
$galleryClass = 'gallery gallery_works_page';
require __DIR__ . '/includes/gallery.php';

require __DIR__ . '/includes/footer.php';
