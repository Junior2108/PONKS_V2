<?php
// =====================================================================
// Page d'accueil
//   - en haut : l'œuvre "à la une" (case cochée dans l'admin)
//   - une galerie : les œuvres cochées "Sur la page d'accueil"
//   - en bas : le film en vedette (premier film qui a une vidéo)
// =====================================================================

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/works.php';

$hero = first_published_work('home_hero = 1');
$film = first_published_work('in_films = 1 AND video_url IS NOT NULL');

$pageTitle = 'PONKS';
require __DIR__ . '/includes/header.php';
?>
            <?php if ($hero): ?>
                <section class="home_page_head">
                    <div class="image_head_index">
                        <a href="<?= e(work_url($hero['slug'])) ?>">
                            <img src="<?= e($hero['thumbnail']) ?>" alt="<?= e($hero['title']) ?>">
                        </a>
                    </div>
                    <div class="text_head_index">
                        <h2>
                            <?= e($hero['title']) ?>
                            <?php if ($hero['subtitle']): ?>
                                <span class="title_self"><?= e($hero['subtitle']) ?></span>
                            <?php endif; ?>
                        </h2>
                        <?php foreach (info_lines($hero['info']) as $line): ?>
                            <p><?= linkify(e($line)) ?></p>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

<?php
$galleryWorks = published_works('home');
require __DIR__ . '/includes/gallery.php';
?>

            <section class="film_home_page">
                <div class="hero_film_title">
                    <div class="title_allworks">
                        <a href="works.html">
                            ALL WORKS
                            <svg
                                viewBox="0 0 24 24"
                                stroke-width="1.5"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                                color="#000000"
                            >
                                <path
                                    d="M13 6L19 12L13 18"
                                    stroke="#fe0137"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                                <path
                                    d="M5 6L11 12L5 18"
                                    stroke="#fe0137"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                ></path>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php if ($film): ?>
                    <div class="hero_film_background">
                        <div class="hero_film">
                            <?php $videoWork = $film; require __DIR__ . '/includes/video.php'; ?>
                            <div class="hero_film_txt">
                                <p>Film highlight</p>
                                <h2><a href="<?= e(work_url($film['slug'])) ?>"><?= e($film['title']) ?></a></h2>
                                <?php foreach (info_lines($film['info']) as $line): ?>
                                    <p><?= linkify(e($line)) ?></p>
                                <?php endforeach; ?>
                                <div class="hero_film_description">
                                    <?php if ($film['intro']): ?>
                                        <p class="p_description"><?= e($film['intro']) ?></p>
                                    <?php endif; ?>
                                    <?= text_to_html($film['description'], 'p_description') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
