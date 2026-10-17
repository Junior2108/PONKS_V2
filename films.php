<?php
// =====================================================================
// Page Films : un film en vedette (vidéo) + la galerie des films
// Adresse publique : films.html (via le .htaccess)
// =====================================================================

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/works.php';

// Le film en vedette : le premier film (par position) qui a une vidéo
$featured = first_published_work('in_films = 1 AND video_url IS NOT NULL');

$pageTitle  = 'PONKS - Films';
$activePage = 'films';
require __DIR__ . '/includes/header.php';
?>
            <?php if ($featured): ?>
                <div class="hero_public_art">
                    <?php $videoWork = $featured; require __DIR__ . '/includes/video.php'; ?>
                    <div class="hero_public_art_txt">
                        <h2 class="p_title">
                            <a href="<?= e(work_url($featured['slug'])) ?>"><?= e($featured['title']) ?></a>
                        </h2>
                        <?php foreach (info_lines($featured['info']) as $line): ?>
                            <h3><?= linkify(e($line)) ?></h3>
                        <?php endforeach; ?>
                        <?= text_to_html($featured['intro'] ?: $featured['description']) ?>
                    </div>
                </div>
            <?php endif; ?>
<?php
$galleryWorks = published_works('films');
$galleryTag   = 'div';
$galleryClass = 'gallery';
require __DIR__ . '/includes/gallery.php';

require __DIR__ . '/includes/footer.php';
