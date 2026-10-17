<?php
// =====================================================================
// Galerie de vignettes d'œuvres (utilisée sur plusieurs pages)
//
// Variables à définir AVANT d'inclure ce fichier :
//   $galleryWorks : tableau renvoyé par published_works()
//   $galleryTag   : 'section' ou 'div' (facultatif, 'section' par défaut)
//   $galleryClass : classes CSS du conteneur (facultatif)
// =====================================================================

$galleryTag   = $galleryTag   ?? 'section';
$galleryClass = $galleryClass ?? 'gallery';
?>
            <<?= $galleryTag ?> class="<?= e($galleryClass) ?>">
                <?php foreach ($galleryWorks as $galleryWork): ?>
                    <div class="gallery_block">
                        <a href="<?= e(work_url($galleryWork['slug'])) ?>">
                            <div class="gallery_img">
                                <img
                                    height="768"
                                    width="768"
                                    src="<?= e($galleryWork['cover']) ?>"
                                    alt="<?= e($galleryWork['title']) ?>"
                                    loading="lazy"
                                >
                                <div class="pink-filter"></div>
                            </div>
                            <div class="gallery_block_title">
                                <p><?= e($galleryWork['title']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </<?= $galleryTag ?>>
